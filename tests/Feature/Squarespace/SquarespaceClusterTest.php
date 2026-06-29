<?php

use App\Models\SquarespaceCredential;
use App\Models\SquarespaceWebhookSubscription;
use App\Services\Squarespace\PackageTierMapper;
use App\Services\Squarespace\SquarespaceApiClient;
use App\Services\Squarespace\SquarespaceOAuthService;
use App\Services\Squarespace\SquarespaceWebhookSubscriptionService;
use App\Enums\PackageTier;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'squarespace.client_id' => 'test-client-id',
        'squarespace.client_secret' => 'test-client-secret',
    ]);
});

function fakeSquarespace(): void
{
    Http::fake(function (Request $request) {
        $url = $request->url();
        $method = $request->method();

        if (str_contains($url, 'login.squarespace.com')) {
            return Http::response([
                'access_token' => 'access-token-123',
                'refresh_token' => 'refresh-token-123',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'refresh_token_expires_in' => 1209600,
                'websiteId' => 'web-1',
            ], 200);
        }

        if (str_contains($url, '/webhook_subscriptions')) {
            if (str_contains($url, '/actions/rotateSecret')) {
                return Http::response(['secret' => 'rotated-secret'], 200);
            }
            if (str_contains($url, '/actions/sendTestNotification')) {
                return Http::response(['statusCode' => 200], 200);
            }
            if ($method === 'GET') {
                return Http::response(['webhookSubscriptions' => [
                    ['id' => 'sub_1', 'topics' => ['order.create'], 'endpointUrl' => 'https://portal.test/webhook'],
                ]], 200);
            }
            if ($method === 'DELETE') {
                return Http::response([], 200);
            }

            return Http::response([
                'id' => 'sub_new',
                'endpointUrl' => 'https://portal.test/webhook',
                'topics' => ['order.create'],
                'secret' => 'signing-secret',
                'websiteId' => 'web-1',
                'createdOn' => now()->toIso8601String(),
                'updatedOn' => now()->toIso8601String(),
            ], 200);
        }

        if (str_contains($url, '/commerce/orders/')) {
            return Http::response(['id' => 'order-1', 'customerEmail' => 'c@example.com'], 200);
        }
        if (str_contains($url, '/commerce/contacts/')) {
            return Http::response(['id' => 'contact-1', 'email' => 'c@example.com'], 200);
        }

        return Http::response([], 200);
    });
}

function squarespaceConnect(): SquarespaceCredential
{
    return SquarespaceCredential::query()->create([
        'access_token' => 'access-token-123',
        'refresh_token' => 'refresh-token-123',
        'token_type' => 'bearer',
        'expires_at' => now()->addHour(),
        'connected_at' => now(),
    ]);
}

it('prints the access token when squarespace is connected', function () {
    squarespaceConnect();

    $this->artisan('squarespace:token')
        ->expectsOutput('access-token-123')
        ->assertExitCode(0);

    $this->artisan('squarespace:token', ['--curl' => true])
        ->assertExitCode(0);
});

it('builds an authorization url and reports configuration state', function () {
    $oauth = app(SquarespaceOAuthService::class);

    expect($oauth->isConfigured())->toBeTrue()
        ->and($oauth->isConnected())->toBeFalse();

    $url = $oauth->authorizationUrl($oauth->generateState());
    expect($url)->toContain('test-client-id')->toContain('response_type=code');
});

it('exchanges an authorization code for tokens and returns a valid access token', function () {
    fakeSquarespace();
    $oauth = app(SquarespaceOAuthService::class);

    $credential = $oauth->handleCallback('auth-code');

    expect($credential->access_token)->toBe('access-token-123')
        ->and($oauth->isConnected())->toBeTrue()
        ->and($oauth->canRefresh())->toBeTrue()
        ->and($oauth->validAccessToken())->toBe('access-token-123');
});

it('refreshes an expired access token automatically', function () {
    fakeSquarespace();
    $credential = squarespaceConnect();
    $credential->forceFill(['expires_at' => now()->subMinute()])->save();

    $oauth = app(SquarespaceOAuthService::class);

    expect($oauth->validAccessToken())->toBe('access-token-123')
        ->and($oauth->current()->fresh()->isExpired())->toBeFalse();
});

it('disconnects by clearing stored credentials', function () {
    squarespaceConnect();
    $oauth = app(SquarespaceOAuthService::class);

    $oauth->disconnect();

    expect($oauth->isConnected())->toBeFalse();
});

it('fetches orders and contacts through the api client', function () {
    fakeSquarespace();
    squarespaceConnect();
    $client = app(SquarespaceApiClient::class);

    expect($client->getOrder('order-1'))->toHaveKey('id')
        ->and($client->getContact('contact-1'))->toHaveKey('id');
});

it('manages webhook subscriptions through the subscription service', function () {
    fakeSquarespace();
    squarespaceConnect();
    $service = app(SquarespaceWebhookSubscriptionService::class);

    $subscription = $service->create();
    expect($subscription->subscription_id)->toBe('sub_new')
        ->and($subscription->secret)->toBe('signing-secret');

    expect($service->list())->toHaveCount(1);
    expect($service->sendTest($subscription->subscription_id, 'order.create'))->toBe(200);

    $rotated = $service->rotateSecret($subscription->subscription_id);
    expect($rotated->secret)->toBe('rotated-secret');

    $service->delete($subscription->subscription_id);
    expect(SquarespaceWebhookSubscription::query()->where('subscription_id', 'sub_new')->exists())->toBeFalse();
});

it('maps package tiers from line items', function () {
    $mapper = app(PackageTierMapper::class);

    expect($mapper->mapFromLineItems([['productName' => 'Legacy Move Package']]))->toBe(PackageTier::LEGACY)
        ->and($mapper->mapFromLineItems([['name' => 'Summit Plan']]))->toBe(PackageTier::SUMMIT)
        ->and($mapper->mapFromLineItems([['productName' => 'Essential Bundle']]))->toBe(PackageTier::ESSENTIAL)
        ->and($mapper->mapFromLineItems([['productName' => 'Premium Tier']]))->toBe(PackageTier::LEGACY)
        ->and($mapper->mapFromLineItems([['productName' => 'Standard Tier']]))->toBe(PackageTier::SUMMIT)
        ->and($mapper->mapFromLineItems([['productName' => 'Basic Tier']]))->toBe(PackageTier::ESSENTIAL)
        ->and($mapper->mapFromLineItems([['productName' => 'Mystery']]))->toBe(PackageTier::UNKNOWN);
});

it('runs the connected squarespace webhook management command actions', function () {
    fakeSquarespace();
    squarespaceConnect();
    SquarespaceWebhookSubscription::query()->create([
        'subscription_id' => 'sub_1',
        'endpoint_url' => 'https://portal.test/webhook',
        'topics' => ['order.create'],
    ]);

    $this->artisan('squarespace:webhooks', ['action' => 'list'])->assertExitCode(0);
    $this->artisan('squarespace:webhooks', ['action' => 'register'])->assertExitCode(0);
    $this->artisan('squarespace:webhooks', ['action' => 'test', '--id' => 'sub_1'])->assertExitCode(0);
    $this->artisan('squarespace:webhooks', ['action' => 'rotate', '--id' => 'sub_1'])->assertExitCode(0);
    $this->artisan('squarespace:webhooks', ['action' => 'delete', '--id' => 'sub_1'])->assertExitCode(0);
    $this->artisan('squarespace:webhooks', ['action' => 'bogus'])->assertExitCode(1);
    $this->artisan('squarespace:webhooks', ['action' => 'delete'])->assertExitCode(1);
});

it('prints the squarespace token when connected', function () {
    squarespaceConnect();

    $this->artisan('squarespace:token')->assertExitCode(0);
    $this->artisan('squarespace:token', ['--curl' => true])->assertExitCode(0);
});

it('shows the admin squarespace integration page', function () {
    $this->actingAs(makeAdmin())
        ->get(route('admin.squarespace'))
        ->assertOk();
});

it('disconnects squarespace from the admin panel', function () {
    squarespaceConnect();

    $this->actingAs(makeAdmin())
        ->post(route('admin.squarespace.disconnect'))
        ->assertRedirect(route('admin.squarespace'))
        ->assertSessionHas('status');
});

it('registers a webhook subscription from the admin panel', function () {
    fakeSquarespace();
    squarespaceConnect();

    $this->actingAs(makeAdmin())
        ->post(route('admin.squarespace.webhooks.register'))
        ->assertRedirect(route('admin.squarespace'))
        ->assertSessionHas('status');
});

it('tests, rotates and deletes a webhook subscription from the admin panel', function () {
    fakeSquarespace();
    squarespaceConnect();
    $subscription = SquarespaceWebhookSubscription::query()->create([
        'subscription_id' => 'sub_1',
        'endpoint_url' => 'https://portal.test/webhook',
        'topics' => ['order.create'],
    ]);

    $admin = makeAdmin();

    $this->actingAs($admin)
        ->post(route('admin.squarespace.webhooks.test', $subscription), ['topic' => 'order.create'])
        ->assertSessionHas('status');

    $this->actingAs($admin)
        ->post(route('admin.squarespace.webhooks.rotate', $subscription))
        ->assertSessionHas('status');

    $this->actingAs($admin)
        ->delete(route('admin.squarespace.webhooks.delete', $subscription))
        ->assertSessionHas('status');
});

it('redirects to squarespace consent when configured', function () {
    $this->actingAs(makeAdmin())
        ->get(route('admin.squarespace.connect'))
        ->assertRedirectContains('login.squarespace.com');
});

it('blocks the connect flow when credentials are missing', function () {
    config(['squarespace.client_id' => '', 'squarespace.client_secret' => '']);

    $this->actingAs(makeAdmin())
        ->get(route('admin.squarespace.connect'))
        ->assertRedirect(route('admin.squarespace'))
        ->assertSessionHas('error');
});

it('completes the oauth callback successfully', function () {
    fakeSquarespace();

    $this->actingAs(makeAdmin())
        ->withSession(['squarespace_oauth_state' => 'state-123'])
        ->get(route('squarespace.callback', ['state' => 'state-123', 'code' => 'auth-code']))
        ->assertRedirect(route('admin.squarespace'))
        ->assertSessionHas('status');

    expect(app(SquarespaceOAuthService::class)->isConnected())->toBeTrue();
});

it('rejects the oauth callback on state mismatch, denial and missing code', function () {
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->withSession(['squarespace_oauth_state' => 'state-123'])
        ->get(route('squarespace.callback', ['state' => 'wrong', 'code' => 'x']))
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->get(route('squarespace.callback', ['error' => 'access_denied']))
        ->assertSessionHas('error');

    $this->actingAs($admin)
        ->withSession(['squarespace_oauth_state' => 'state-123'])
        ->get(route('squarespace.callback', ['state' => 'state-123']))
        ->assertSessionHas('error');
});

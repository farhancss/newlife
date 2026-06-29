<?php

use App\Enums\UserRole;
use App\Enums\WebhookEventStatus;
use App\Http\Middleware\EnsureUserHasRole;
use App\Jobs\Squarespace\ProcessSquarespaceContactWebhook;
use App\Jobs\Squarespace\ProcessSquarespaceOrderWebhook;
use App\Models\SquarespaceWebhookEvent;
use App\Models\User;
use App\Services\Squarespace\SquarespaceApiClient;
use App\Services\Squarespace\SquarespaceWebhookDispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('authenticates the api client with a private key when oauth is absent', function () {
    config(['squarespace.api_key' => 'test-key']);
    Http::fake(['*' => Http::response(['id' => 'ord-1', 'orderNumber' => '1001'], 200)]);

    $order = app(SquarespaceApiClient::class)->getOrder('ord-1');

    expect($order['orderNumber'])->toBe('1001');
});

it('throws when the api responds with an error status', function () {
    config(['squarespace.api_key' => 'test-key']);
    Http::fake(['*' => Http::response('boom', 500)]);

    expect(fn () => app(SquarespaceApiClient::class)->getContact('c-1'))
        ->toThrow(RuntimeException::class);
});

it('throws when the api connection fails', function () {
    config(['squarespace.api_key' => 'test-key']);
    Http::fake(fn () => throw new ConnectionException('network down'));

    expect(fn () => app(SquarespaceApiClient::class)->getOrder('ord-1'))
        ->toThrow(RuntimeException::class);
});

it('throws when no squarespace credentials are available', function () {
    config(['squarespace.api_key' => null]);

    expect(fn () => app(SquarespaceApiClient::class)->getOrder('ord-1'))
        ->toThrow(RuntimeException::class);
});

it('dispatches webhook jobs by topic and guards malformed payloads', function () {
    Bus::fake();
    $dispatcher = app(SquarespaceWebhookDispatcher::class);

    expect(fn () => $dispatcher->dispatch(['id' => '', 'topic' => '']))
        ->toThrow(InvalidArgumentException::class);

    $dispatcher->dispatch(['id' => 'n-1', 'topic' => 'contact.created']);
    Bus::assertDispatched(ProcessSquarespaceContactWebhook::class);

    $dispatcher->dispatch(['id' => 'n-2', 'topic' => 'order.create']);
    Bus::assertDispatched(ProcessSquarespaceOrderWebhook::class);

    $event = $dispatcher->dispatch(['id' => 'n-3', 'topic' => 'unknown.thing']);
    expect($event->status)->toBe(WebhookEventStatus::FAILED);
});

it('returns the existing event when already processed', function () {
    $dispatcher = app(SquarespaceWebhookDispatcher::class);
    $processed = SquarespaceWebhookEvent::query()->create([
        'notification_id' => 'n-9',
        'topic' => 'contact.created',
        'payload' => ['id' => 'n-9'],
        'status' => WebhookEventStatus::PROCESSED,
    ]);

    $result = $dispatcher->dispatch(['id' => 'n-9', 'topic' => 'contact.created']);

    expect($result->id)->toBe($processed->id);
});

it('reports errors when squarespace webhook admin actions fail', function () {
    $admin = makeAdmin();
    $subscription = \App\Models\SquarespaceWebhookSubscription::query()->create([
        'subscription_id' => 'sub-1',
        'endpoint_url' => 'https://example.com/hook',
        'topics' => ['order.create'],
        'secret' => 'shh',
        'status' => 'active',
    ]);

    $service = Mockery::mock(\App\Services\Squarespace\SquarespaceWebhookSubscriptionService::class);
    $service->shouldReceive('create')->andThrow(new RuntimeException('register failed'));
    $service->shouldReceive('delete')->andThrow(new RuntimeException('delete failed'));
    $service->shouldReceive('sendTest')->andThrow(new RuntimeException('test failed'));
    $service->shouldReceive('rotateSecret')->andThrow(new RuntimeException('rotate failed'));
    app()->instance(\App\Services\Squarespace\SquarespaceWebhookSubscriptionService::class, $service);

    $this->actingAs($admin)->post(route('admin.squarespace.webhooks.register'))
        ->assertRedirect(route('admin.squarespace'))->assertSessionHas('error');
    $this->actingAs($admin)->delete(route('admin.squarespace.webhooks.delete', $subscription))
        ->assertSessionHas('error');
    $this->actingAs($admin)->post(route('admin.squarespace.webhooks.test', $subscription), ['topic' => 'order.create'])
        ->assertSessionHas('error');
    $this->actingAs($admin)->post(route('admin.squarespace.webhooks.rotate', $subscription))
        ->assertSessionHas('error');
});

it('fails the token command when the access token cannot be refreshed', function () {
    \App\Models\SquarespaceCredential::query()->create([
        'access_token' => 'expired',
        'refresh_token' => 'refresh',
        'token_type' => 'bearer',
        'expires_at' => now()->subHour(),
        'connected_at' => now()->subDay(),
    ]);
    Http::fake(['*' => Http::response('bad', 400)]);

    $this->artisan('squarespace:token')->assertExitCode(1);
});

it('blocks dev-only routes outside local environments', function () {
    $mw = new \App\Http\Middleware\EnsureLocalEnvironment();
    $next = fn ($request) => response('ok');

    app()->instance('env', 'production');
    try {
        $response = $mw->handle(Request::create('/api/dev/x'), $next);
    } finally {
        app()->instance('env', 'testing');
    }

    expect($response->getStatusCode())->toBe(404);
});

it('guards portal routes by role', function () {
    $mw = new EnsureUserHasRole();
    $next = fn ($request) => response('ok');

    $guestRequest = Request::create('/portal');
    $guestRequest->setUserResolver(fn () => null);
    expect($mw->handle($guestRequest, $next, UserRole::ADMIN)->getStatusCode())->toBe(302);

    $student = User::factory()->create(['role' => UserRole::STUDENT]);
    $studentRequest = Request::create('/portal');
    $studentRequest->setUserResolver(fn () => $student);
    expect(fn () => $mw->handle($studentRequest, $next, UserRole::ADMIN))
        ->toThrow(HttpException::class);

    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $adminRequest = Request::create('/portal');
    $adminRequest->setUserResolver(fn () => $admin);
    expect($mw->handle($adminRequest, $next, UserRole::ADMIN)->getContent())->toBe('ok');
});

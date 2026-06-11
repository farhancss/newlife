<?php

use App\Jobs\Squarespace\ProcessSquarespaceContactWebhook;
use App\Jobs\Squarespace\ProcessSquarespaceOrderWebhook;
use App\Models\StudentProfile;
use App\Models\StudentSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'squarespace.webhook_secret' => 'test-webhook-secret',
        'squarespace.skip_signature_verification' => false,
    ]);
});

function squarespaceSignature(string $payload, string $secret): string
{
    return hash_hmac('sha256', $payload, $secret);
}

function fixtureNotification(string $filename): array
{
    $path = base_path('tests/fixtures/squarespace/' . $filename);
    $json = file_get_contents($path);

    return json_decode($json, true);
}

test('squarespace webhook rejects invalid signature', function () {
    $response = $this->postJson('/api/webhooks/squarespace', ['topic' => 'contact.create'], [
        'Squarespace-Signature' => 'invalid',
    ]);

    $response->assertUnauthorized();
});

test('squarespace contact webhook provisions student account', function () {
    Queue::fake();

    $notification = fixtureNotification('contact-create.json');
    $payload = json_encode($notification);
    $signature = squarespaceSignature($payload, 'test-webhook-secret');

    $response = $this->call(
        'POST',
        '/api/webhooks/squarespace',
        [],
        [],
        [],
        [
            'HTTP_Squarespace-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload
    );

    $response->assertOk();
    Queue::assertPushed(ProcessSquarespaceContactWebhook::class);

    $event = \App\Models\SquarespaceWebhookEvent::query()->first();
    expect($event)->not->toBeNull();

    (new ProcessSquarespaceContactWebhook($event->id))->handle(app(\App\Services\AccountProvisioningService::class));

    $user = User::query()->where('email', 'jane.doe@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->must_reset_password)->toBeTrue()
        ->and($user->squarespace_contact_id)->toBe('sq-contact-fixture-001');

    $profile = StudentProfile::query()->where('user_id', $user->id)->first();
    expect($profile)->not->toBeNull()
        ->and($profile->first_name)->toBe('Jane');
});

test('duplicate squarespace notification is idempotent', function () {
    Queue::fake();

    $notification = fixtureNotification('contact-create.json');
    $payload = json_encode($notification);
    $signature = squarespaceSignature($payload, 'test-webhook-secret');
    $headers = [
        'HTTP_Squarespace-Signature' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ];

    $this->call('POST', '/api/webhooks/squarespace', [], [], [], $headers, $payload);
    $this->call('POST', '/api/webhooks/squarespace', [], [], [], $headers, $payload);

    expect(\App\Models\SquarespaceWebhookEvent::query()->count())->toBe(1);
});

test('squarespace order webhook enriches package and subscription', function () {
    $contactNotification = fixtureNotification('contact-create.json');
    app(\App\Services\Squarespace\SquarespaceWebhookDispatcher::class)->dispatch($contactNotification);

    $contactEvent = \App\Models\SquarespaceWebhookEvent::query()->where('topic', 'contact.create')->first();
    (new ProcessSquarespaceContactWebhook($contactEvent->id))->handle(app(\App\Services\AccountProvisioningService::class));

    $orderNotification = fixtureNotification('order-create.json');
    app(\App\Services\Squarespace\SquarespaceWebhookDispatcher::class)->dispatch($orderNotification);

    $orderEvent = \App\Models\SquarespaceWebhookEvent::query()->where('topic', 'order.create')->first();
    (new ProcessSquarespaceOrderWebhook($orderEvent->id))->handle(
        app(\App\Services\AccountProvisioningService::class),
        app(\App\Services\Squarespace\SquarespaceApiClient::class),
    );

    $profile = StudentProfile::query()->where('squarespace_contact_id', 'sq-contact-fixture-001')->first();
    expect($profile)->not->toBeNull()
        ->and($profile->package_tier)->toBe('summit')
        ->and($profile->package_id)->not->toBeNull();

    $subscription = StudentSubscription::query()->where('squarespace_order_id', 'sq-order-fixture-001')->first();
    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe('active');
});

test('simulation api requires bearer token', function () {
    config(['squarespace.simulation_token' => 'sim-token']);

    $this->postJson('/api/dev/squarespace/simulate', ['topic' => 'contact.create'])
        ->assertUnauthorized();
});

test('simulation api dispatches webhook with valid token', function () {
    Queue::fake();
    config(['squarespace.simulation_token' => 'sim-token']);

    $response = $this->postJson(
        '/api/dev/squarespace/simulate',
        ['topic' => 'contact.create', 'payload' => fixtureNotification('contact-create.json')['data']],
        ['Authorization' => 'Bearer sim-token']
    );

    $response->assertOk()->assertJsonPath('simulated', true);
    Queue::assertPushed(ProcessSquarespaceContactWebhook::class);
});

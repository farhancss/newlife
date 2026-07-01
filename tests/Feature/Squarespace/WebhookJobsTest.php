<?php

use App\Enums\WebhookEventStatus;
use App\Jobs\Squarespace\ProcessSquarespaceAddressWebhook;
use App\Jobs\Squarespace\ProcessSquarespaceContactWebhook;
use App\Jobs\Squarespace\ProcessSquarespaceOrderWebhook;
use App\Models\SquarespaceWebhookEvent;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AccountProvisioningService;
use App\Services\ProvisionedAccount;
use App\Services\Squarespace\SquarespaceApiClient;
use App\Services\Squarespace\SquarespaceLogger;

/**
 * @param array<string, mixed> $payload
 */
function makeWebhookEvent(array $payload, string $topic = 'order.create'): SquarespaceWebhookEvent
{
    return SquarespaceWebhookEvent::query()->create([
        'notification_id' => uniqid('n-', true),
        'topic' => $topic,
        'payload' => $payload,
        'status' => WebhookEventStatus::PENDING,
    ]);
}

it('processes and fails the address webhook job', function () {
    $event = makeWebhookEvent(['data' => ['contactId' => 'c-1']], 'address.updated');

    expect((new ProcessSquarespaceAddressWebhook($event->id))->uniqueId())
        ->toBe('squarespace-address-' . $event->id);

    $ok = Mockery::mock(AccountProvisioningService::class);
    $ok->shouldReceive('syncFromAddressNotification')->once();
    (new ProcessSquarespaceAddressWebhook($event->id))->handle($ok);
    expect($event->fresh()->status)->toBe(WebhookEventStatus::PROCESSED);

    $event2 = makeWebhookEvent(['data' => ['contactId' => 'c-2']], 'address.updated');
    $bad = Mockery::mock(AccountProvisioningService::class);
    $bad->shouldReceive('syncFromAddressNotification')->once()->andThrow(new RuntimeException('boom'));
    expect(fn () => (new ProcessSquarespaceAddressWebhook($event2->id))->handle($bad))
        ->toThrow(RuntimeException::class);
    expect($event2->fresh()->status)->toBe(WebhookEventStatus::FAILED)
        ->and($event2->fresh()->error)->toBe('boom');
});

it('processes and fails the contact webhook job', function () {
    $event = makeWebhookEvent(['data' => ['contactId' => 'c-1']], 'contact.created');

    $ok = Mockery::mock(AccountProvisioningService::class);
    $ok->shouldReceive('upsertFromContactNotification')->once();
    (new ProcessSquarespaceContactWebhook($event->id))->handle($ok);
    expect($event->fresh()->status)->toBe(WebhookEventStatus::PROCESSED);

    $event2 = makeWebhookEvent(['data' => ['contactId' => 'c-2']], 'contact.created');
    $bad = Mockery::mock(AccountProvisioningService::class);
    $bad->shouldReceive('upsertFromContactNotification')->once()->andThrow(new RuntimeException('nope'));
    expect(fn () => (new ProcessSquarespaceContactWebhook($event2->id))->handle($bad))
        ->toThrow(RuntimeException::class);
    expect($event2->fresh()->status)->toBe(WebhookEventStatus::FAILED);
});

it('handles the order webhook via embedded order, api lookup and missing data', function () {
    $apiClient = Mockery::mock(SquarespaceApiClient::class);

    // Embedded order payload: no API call.
    $embedded = makeWebhookEvent(['data' => ['order' => ['id' => 'o-1']]]);
    $prov1 = Mockery::mock(AccountProvisioningService::class);
    $prov1->shouldReceive('provisionFromOrder')->once()->andReturn(new ProvisionedAccount(
        profile: new StudentProfile(),
        user: new User(['email' => 'test@example.com']),
        isNewUser: false,
        temporaryPassword: null,
    ));
    (new ProcessSquarespaceOrderWebhook($embedded->id))->handle($prov1, $apiClient, app(SquarespaceLogger::class));
    expect($embedded->fresh()->status)->toBe(WebhookEventStatus::PROCESSED);

    // Order id only: fetched through the API client.
    $byId = makeWebhookEvent(['data' => ['orderId' => 'o-2']]);
    $apiClient->shouldReceive('getOrder')->with('o-2')->once()->andReturn(['id' => 'o-2']);
    $prov2 = Mockery::mock(AccountProvisioningService::class);
    $prov2->shouldReceive('provisionFromOrder')->once()->andReturn(new ProvisionedAccount(
        profile: new StudentProfile(),
        user: new User(['email' => 'test@example.com']),
        isNewUser: false,
        temporaryPassword: null,
    ));
    (new ProcessSquarespaceOrderWebhook($byId->id))->handle($prov2, $apiClient, app(SquarespaceLogger::class));
    expect($byId->fresh()->status)->toBe(WebhookEventStatus::PROCESSED);

    // Missing both: fails.
    $empty = makeWebhookEvent(['data' => []]);
    $prov3 = Mockery::mock(AccountProvisioningService::class);
    $prov3->shouldNotReceive('provisionFromOrder');
    expect(fn () => (new ProcessSquarespaceOrderWebhook($empty->id))->handle($prov3, $apiClient, app(SquarespaceLogger::class)))
        ->toThrow(RuntimeException::class);
    expect($empty->fresh()->status)->toBe(WebhookEventStatus::FAILED);
});

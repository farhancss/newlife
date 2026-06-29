<?php

use App\Enums\WebhookEventStatus;
use App\Jobs\Squarespace\ProcessSquarespaceAddressWebhook;
use App\Models\SquarespaceAddressEntry;
use App\Models\SquarespaceWebhookEvent;

it('syncs a home address from an address webhook and marks the event processed', function () {
    [, $profile] = completeStudent();
    $profile->forceFill(['squarespace_contact_id' => 'contact-xyz'])->save();

    $event = SquarespaceWebhookEvent::query()->create([
        'notification_id' => 'note-1',
        'topic' => 'address.update',
        'status' => WebhookEventStatus::PENDING,
        'payload' => [
            'data' => [
                'contactId' => 'contact-xyz',
                'addressBookEntryId' => 'entry-1',
                'address' => [
                    'line1' => '500 Dorm Rd',
                    'city' => 'Norfolk',
                    'region' => 'VA',
                    'postalCode' => '23529',
                    'countryCode' => 'US',
                ],
            ],
        ],
    ]);

    (new ProcessSquarespaceAddressWebhook($event->id))->handle(app(\App\Services\AccountProvisioningService::class));

    expect($event->fresh()->status)->toBe(WebhookEventStatus::PROCESSED)
        ->and(SquarespaceAddressEntry::query()->where('address_book_entry_id', 'entry-1')->exists())->toBeTrue();
});

it('processes an address webhook with no matching contact as a no-op', function () {
    $event = SquarespaceWebhookEvent::query()->create([
        'notification_id' => 'note-2',
        'topic' => 'address.update',
        'status' => WebhookEventStatus::PENDING,
        'payload' => ['data' => []],
    ]);

    (new ProcessSquarespaceAddressWebhook($event->id))->handle(app(\App\Services\AccountProvisioningService::class));

    expect($event->fresh()->status)->toBe(WebhookEventStatus::PROCESSED)
        ->and(SquarespaceAddressEntry::query()->count())->toBe(0);
});

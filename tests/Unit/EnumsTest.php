<?php

use App\Enums\AddOnStatus;
use App\Enums\ContainerStatus;
use App\Enums\DeadlineStatus;
use App\Enums\DeadlineType;
use App\Enums\NotificationCategory;
use App\Enums\PackageTier;
use App\Enums\RetailPackageStatus;
use App\Enums\SquarespaceLogDirection;
use App\Enums\StoragePickupStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WebhookEventStatus;

it('AddOnStatus exposes labels and validity', function () {
    expect(AddOnStatus::all())->toBe([AddOnStatus::ACTIVE, AddOnStatus::CANCELLED]);
    expect(AddOnStatus::label(AddOnStatus::ACTIVE))->toBe('Active');
    expect(AddOnStatus::label(AddOnStatus::CANCELLED))->toBe('Cancelled');
    expect(AddOnStatus::label('partially_refunded'))->toBe('Partially Refunded');
    expect(AddOnStatus::isValid(AddOnStatus::ACTIVE))->toBeTrue();
    expect(AddOnStatus::isValid('bogus'))->toBeFalse();
    expect(AddOnStatus::isValid(null))->toBeFalse();
});

it('DeadlineStatus exposes labels, tone and validity', function () {
    expect(DeadlineStatus::all())->toBe([
        DeadlineStatus::UPCOMING,
        DeadlineStatus::COMPLETED,
        DeadlineStatus::OVERDUE,
    ]);
    expect(DeadlineStatus::label(DeadlineStatus::UPCOMING))->toBe('Upcoming');
    expect(DeadlineStatus::label(DeadlineStatus::COMPLETED))->toBe('Completed');
    expect(DeadlineStatus::label(DeadlineStatus::OVERDUE))->toBe('Overdue');
    expect(DeadlineStatus::label('snoozed'))->toBe('Snoozed');
    expect(DeadlineStatus::tone(DeadlineStatus::COMPLETED))->toBe('success');
    expect(DeadlineStatus::tone(DeadlineStatus::OVERDUE))->toBe('warning');
    expect(DeadlineStatus::tone(DeadlineStatus::UPCOMING))->toBe('info');
    expect(DeadlineStatus::isValid(DeadlineStatus::UPCOMING))->toBeTrue();
    expect(DeadlineStatus::isValid('nope'))->toBeFalse();
    expect(DeadlineStatus::isValid(null))->toBeFalse();
});

it('DeadlineType exposes the catalog and labels', function () {
    expect(DeadlineType::all())->toHaveCount(4);
    expect(DeadlineType::label(DeadlineType::PROFILE_COMPLETION))->toBe('Profile Completion');
    expect(DeadlineType::label(DeadlineType::CONTAINER_PICKUP))->toBe('Container Pickup');
    expect(DeadlineType::label(DeadlineType::RETAIL_ARRIVAL))->toBe('Retail Package Arrival');
    expect(DeadlineType::label(DeadlineType::ADDON_CONTAINER_PICKUP))->toBe('Add-on Container Pickup');
    expect(DeadlineType::label('custom_reminder'))->toBe('Custom Reminder');
});

it('NotificationCategory exposes the catalog, labels and parent CC scope', function () {
    expect(NotificationCategory::all())->toHaveCount(6);
    expect(NotificationCategory::label(NotificationCategory::ACCOUNT))->toBe('Account');
    expect(NotificationCategory::label(NotificationCategory::SHIPMENT))->toBe('Shipment');
    expect(NotificationCategory::label(NotificationCategory::RETAIL))->toBe('Retail');
    expect(NotificationCategory::label(NotificationCategory::ADD_ON))->toBe('Add-on');
    expect(NotificationCategory::label(NotificationCategory::DEADLINE))->toBe('Deadline');
    expect(NotificationCategory::label(NotificationCategory::SYSTEM))->toBe('System');
    expect(NotificationCategory::label('promo'))->toBe('Promo');
    expect(NotificationCategory::parentCcCategories())
        ->toBe([NotificationCategory::SHIPMENT, NotificationCategory::RETAIL]);
});

it('SquarespaceLogDirection labels both directions and falls back', function () {
    expect(SquarespaceLogDirection::label(SquarespaceLogDirection::INCOMING))->toBe('Incoming (webhook)');
    expect(SquarespaceLogDirection::label(SquarespaceLogDirection::OUTGOING))->toBe('Outgoing (API call)');
    expect(SquarespaceLogDirection::label('internal'))->toBe('Internal');
});

it('StoragePickupStatus exposes ordering, labels, validity and helpers', function () {
    expect(StoragePickupStatus::ordered())->toHaveCount(6);
    expect(StoragePickupStatus::all())->toHaveCount(7);
    expect(StoragePickupStatus::all())->toContain(StoragePickupStatus::CANCELLED);

    foreach (StoragePickupStatus::all() as $status) {
        expect(StoragePickupStatus::label($status))->toBeString()->not->toBe('');
    }
    expect(StoragePickupStatus::label('partially_returned'))->toBe('Partially Returned');

    expect(StoragePickupStatus::isValid(StoragePickupStatus::SCHEDULED))->toBeTrue();
    expect(StoragePickupStatus::isValid('bogus'))->toBeFalse();
    expect(StoragePickupStatus::isValid(null))->toBeFalse();

    expect(StoragePickupStatus::orderIndex(StoragePickupStatus::REQUESTED))->toBe(0);
    expect(StoragePickupStatus::orderIndex(StoragePickupStatus::RETURNED))->toBe(5);
    expect(StoragePickupStatus::orderIndex(StoragePickupStatus::CANCELLED))->toBe(0);

    expect(StoragePickupStatus::isActive(StoragePickupStatus::SCHEDULED))->toBeTrue();
    expect(StoragePickupStatus::isActive(StoragePickupStatus::RETURNED))->toBeFalse();
    expect(StoragePickupStatus::isActive(StoragePickupStatus::CANCELLED))->toBeFalse();
});

it('SubscriptionStatus, UserRole and WebhookEventStatus expose their values', function () {
    expect(SubscriptionStatus::values())->toBe([
        SubscriptionStatus::ACTIVE,
        SubscriptionStatus::PAST_DUE,
        SubscriptionStatus::CANCELLED,
        SubscriptionStatus::COMPLETED,
    ]);
    expect(UserRole::values())->toBe([UserRole::STUDENT, UserRole::ADMIN]);
    expect(WebhookEventStatus::values())->toBe([
        WebhookEventStatus::PENDING,
        WebhookEventStatus::PROCESSING,
        WebhookEventStatus::PROCESSED,
        WebhookEventStatus::FAILED,
    ]);
});

it('UserStatus exposes values and validity', function () {
    expect(UserStatus::values())->toHaveCount(4);
    expect(UserStatus::isValid(UserStatus::ACTIVE))->toBeTrue();
    expect(UserStatus::isValid('frozen'))->toBeFalse();
    expect(UserStatus::isValid(null))->toBeFalse();
});

it('PackageTier normalizes legacy aliases and unknown tiers', function () {
    expect(PackageTier::values())->toContain(PackageTier::UNKNOWN);

    expect(PackageTier::normalize('basic'))->toBe(PackageTier::ESSENTIAL);
    expect(PackageTier::normalize(PackageTier::ESSENTIAL))->toBe(PackageTier::ESSENTIAL);
    expect(PackageTier::normalize('standard'))->toBe(PackageTier::SUMMIT);
    expect(PackageTier::normalize(PackageTier::SUMMIT))->toBe(PackageTier::SUMMIT);
    expect(PackageTier::normalize('premium'))->toBe(PackageTier::LEGACY);
    expect(PackageTier::normalize(PackageTier::LEGACY))->toBe(PackageTier::LEGACY);
    expect(PackageTier::normalize(null))->toBe(PackageTier::UNKNOWN);
    expect(PackageTier::normalize('mystery'))->toBe(PackageTier::UNKNOWN);
});

it('RetailPackageStatus exposes ordering, labels, validity and index', function () {
    expect(RetailPackageStatus::ordered())->toHaveCount(5);
    foreach (RetailPackageStatus::ordered() as $status) {
        expect(RetailPackageStatus::label($status))->toBeString()->not->toBe('');
    }
    expect(RetailPackageStatus::label('lost_in_transit'))->toBe('Lost In Transit');

    expect(RetailPackageStatus::isValid(RetailPackageStatus::LOGGED))->toBeTrue();
    expect(RetailPackageStatus::isValid('bogus'))->toBeFalse();
    expect(RetailPackageStatus::isValid(null))->toBeFalse();

    expect(RetailPackageStatus::orderIndex(RetailPackageStatus::LOGGED))->toBe(0);
    expect(RetailPackageStatus::orderIndex(RetailPackageStatus::DELIVERED_TO_DORM))->toBe(4);
    expect(RetailPackageStatus::orderIndex('bogus'))->toBe(0);
});

it('ContainerStatus labels every status and lists the ordered journey', function () {
    expect(ContainerStatus::ordered())->not->toBeEmpty();
    foreach (ContainerStatus::ordered() as $status) {
        expect(ContainerStatus::label($status))->toBeString()->not->toBe('');
    }
});

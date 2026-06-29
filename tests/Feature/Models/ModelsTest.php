<?php

use App\Enums\ContainerStatus;
use App\Enums\RetailPackageStatus;
use App\Models\Container;
use App\Models\ContainerPhoto;
use App\Models\Package;
use App\Models\ParentGuardian;
use App\Models\RetailPackage;
use App\Models\RetailPackageStatusHistory;
use App\Models\ShippingAddress;
use App\Models\SquarespaceAddressEntry;
use Illuminate\Support\Facades\Storage;

it('relates a parent guardian back to its student profile', function () {
    [, $profile] = completeStudent();

    $guardian = ParentGuardian::query()->where('student_profile_id', $profile->id)->first();

    expect($guardian->studentProfile->id)->toBe($profile->id);
});

it('casts the squarespace address entry payload and relates a shipping address', function () {
    [, $profile] = completeStudent();
    $address = ShippingAddress::query()->where('student_profile_id', $profile->id)->first();

    $entry = SquarespaceAddressEntry::query()->create([
        'squarespace_contact_id' => 'contact-1',
        'address_book_entry_id' => 'entry-1',
        'shipping_address_id' => $address->id,
        'raw_payload' => ['line1' => '1 Test Rd'],
    ]);

    expect($entry->raw_payload)->toBe(['line1' => '1 Test Rd'])
        ->and($entry->shippingAddress->id)->toBe($address->id);
});

it('builds a public url for a container photo and defaults the type', function () {
    Storage::fake('public');
    [, $profile] = completeStudent();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-PHOTO-1',
        'status' => ContainerStatus::CUSTOMER_PACKING,
    ]);

    $photo = ContainerPhoto::query()->create([
        'container_id' => $container->id,
        'disk' => 'public',
        'path' => 'container-photos/x.jpg',
        'size' => 10,
    ]);

    expect($photo->type)->toBe(ContainerPhoto::TYPE_EXTERIOR)
        ->and($photo->url())->toContain('container-photos/x.jpg')
        ->and($photo->container->id)->toBe($container->id);
});

it('labels a retail package status history entry', function () {
    [, $profile] = completeStudent();

    $package = RetailPackage::query()->create([
        'student_profile_id' => $profile->id,
        'retailer' => 'Amazon',
        'description' => 'Bedding',
        'tracking_number' => '1Z999',
        'status' => RetailPackageStatus::LOGGED,
    ]);

    $history = RetailPackageStatusHistory::query()->create([
        'retail_package_id' => $package->id,
        'to_status' => RetailPackageStatus::IN_TRANSIT,
        'created_at' => now(),
    ]);

    expect($history->toStatusLabel())->toBe('In Transit')
        ->and($history->retailPackage->id)->toBe($package->id);
});

it('exposes retail package editability and active state', function () {
    [, $profile] = completeStudent();

    $editable = RetailPackage::query()->create([
        'student_profile_id' => $profile->id,
        'retailer' => 'Target',
        'description' => 'Lamp',
        'tracking_number' => 'TGT1',
        'status' => RetailPackageStatus::LOGGED,
    ]);

    $locked = RetailPackage::query()->create([
        'student_profile_id' => $profile->id,
        'retailer' => 'Target',
        'description' => 'Desk',
        'tracking_number' => 'TGT2',
        'status' => RetailPackageStatus::RECEIVED_AT_HUB,
    ]);

    $delivered = RetailPackage::query()->create([
        'student_profile_id' => $profile->id,
        'retailer' => 'Target',
        'description' => 'Chair',
        'tracking_number' => 'TGT3',
        'status' => RetailPackageStatus::DELIVERED_TO_DORM,
    ]);

    expect($editable->isEditable())->toBeTrue()
        ->and($editable->statusLabel())->toBe('Logged')
        ->and($locked->isEditable())->toBeFalse()
        ->and($editable->isActive())->toBeTrue()
        ->and($delivered->isActive())->toBeFalse();
});

it('exposes package helpers and labels', function () {
    $legacy = Package::query()->where('slug', 'legacy')->first();

    expect($legacy)->not->toBeNull()
        ->and($legacy->allowsRetailPackages())->toBeTrue()
        ->and($legacy->maxRetailPackages())->toBeGreaterThan(0)
        ->and($legacy->shortLabel())->toBe('Legacy')
        ->and($legacy->formattedPrice())->toStartWith('$');

    $custom = Package::query()->create([
        'slug' => 'custom-tier',
        'name' => 'Custom Tier',
        'price_cents' => 12300,
        'container_count' => 1,
        'includes_storage' => true,
        'allows_retail_packages' => false,
        'max_retail_packages' => 0,
        'sort_order' => 99,
    ]);

    expect($custom->includesStorage())->toBeTrue()
        ->and($custom->allowsRetailPackages())->toBeFalse()
        ->and($custom->shortLabel())->toBe('Custom Tier');
});

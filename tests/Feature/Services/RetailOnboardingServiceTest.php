<?php

use App\Enums\PackageTier;
use App\Enums\RetailPackageStatus;
use App\Models\RetailPackage;
use App\Services\OnboardingService;
use App\Services\RetailPackageService;
use App\Services\StudentPackageService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * @return \App\Models\StudentProfile
 */
function retailEligibleProfile()
{
    [, $profile] = completeStudent();
    app(StudentPackageService::class)->assignFromTier($profile, PackageTier::LEGACY);

    return $profile->fresh();
}

it('logs, updates, transitions and removes a retail package via the service', function () {
    Mail::fake();
    $profile = retailEligibleProfile();
    $service = app(RetailPackageService::class);

    $package = $service->create($profile, [
        'retailer' => 'Amazon',
        'description' => 'Bedding set',
        'tracking_number' => 'AMZ-1',
    ]);

    expect($package->status)->toBe(RetailPackageStatus::LOGGED)
        ->and($profile->fresh()->retail_packages_acknowledged_at)->not->toBeNull()
        ->and($service->purchasedCount($profile))->toBe(1)
        ->and($service->activeCount($profile))->toBe(1)
        ->and($service->capFor($profile))->toBeGreaterThan(0)
        ->and($service->activeCap())->toBeGreaterThan(0);

    $updated = $service->update($package, [
        'description' => 'Updated bedding set',
        'estimated_arrival' => now()->addWeek()->toDateString(),
    ]);
    expect($updated->description)->toBe('Updated bedding set');

    $service->transition($package, RetailPackageStatus::IN_TRANSIT, null, 'Scanned');
    expect($package->fresh()->status)->toBe(RetailPackageStatus::IN_TRANSIT);

    $service->delete($package, 'Logged in error');
    expect($package->fresh()->trashed())->toBeTrue()
        ->and($package->fresh()->removed_reason)->toBe('Logged in error');
});

it('rejects invalid retail package transitions', function () {
    Mail::fake();
    $profile = retailEligibleProfile();
    $service = app(RetailPackageService::class);

    $package = $service->create($profile, [
        'retailer' => 'Target',
        'description' => 'Lamp',
        'tracking_number' => 'TGT-1',
    ]);

    expect($service->transition($package, RetailPackageStatus::LOGGED)->status)->toBe(RetailPackageStatus::LOGGED);

    $service->transition($package, RetailPackageStatus::DELIVERED_TO_DORM, null, null, true);

    expect(fn () => $service->transition($package->fresh(), RetailPackageStatus::LOGGED))
        ->toThrow(ValidationException::class);

    expect(fn () => $service->transition($package->fresh(), 'not-a-status'))
        ->toThrow(ValidationException::class);
});

it('blocks editing a package once it is locked', function () {
    Mail::fake();
    $profile = retailEligibleProfile();
    $service = app(RetailPackageService::class);

    $package = $service->create($profile, [
        'retailer' => 'Target',
        'description' => 'Chair',
        'tracking_number' => 'TGT-2',
    ]);
    $service->transition($package, RetailPackageStatus::RECEIVED_AT_HUB, null, null, true);

    expect(fn () => $service->update($package->fresh(), ['description' => 'nope']))
        ->toThrow(ValidationException::class);
});

it('detects onboarding section changes and saves steps', function () {
    $service = app(OnboardingService::class);
    [$user, $profile] = completeStudent();

    expect($service->isComplete($profile))->toBeTrue()
        ->and($service->getProgress($profile))->toBeInt();

    expect($service->hasSectionChanges($profile, 1, ['first_name' => 'Test', 'last_name' => 'Student', 'phone' => '757-555-0100', 'school' => 'ODU', 'incoming_year' => '2026']))->toBeFalse()
        ->and($service->hasSectionChanges($profile, 1, ['first_name' => 'Changed']))->toBeTrue()
        ->and($service->hasSectionChanges($profile, 2, ['parent_name' => 'Someone Else']))->toBeTrue()
        ->and($service->hasSectionChanges($profile, 3, ['city' => 'Richmond']))->toBeTrue()
        ->and($service->hasSectionChanges($profile, 4, ['residence_hall' => 'Different Hall']))->toBeTrue()
        ->and($service->hasSectionChanges($profile, 5, []))->toBeFalse();

    $service->saveStep($user, 2, [
        'parent_name' => 'New Parent',
        'parent_email' => 'newparent@example.com',
        'parent_phone' => '757-555-0199',
        'parent_relationship' => 'Guardian',
    ]);
    expect($profile->fresh()->parentGuardian->name)->toBe('New Parent');

    expect(fn () => $service->saveStep($user, 9, []))->toThrow(InvalidArgumentException::class);
});

it('marks onboarding complete through the service', function () {
    $service = app(OnboardingService::class);
    [, $profile] = completeStudent();
    $profile->forceFill(['onboarding_completed_at' => null, 'onboarding_step' => 1])->save();

    $completed = $service->complete($profile);

    expect($completed->onboarding_step)->toBe(5)
        ->and($completed->onboarding_completed_at)->not->toBeNull();
});

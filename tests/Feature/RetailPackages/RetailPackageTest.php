<?php

use App\Enums\RetailPackageStatus;
use App\Enums\UserRole;
use App\Mail\PortalNotificationMail;
use App\Models\RetailPackage;
use App\Models\RetailPackageStatusHistory;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\CarrierLinkBuilder;
use App\Services\RetailPackageService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * @return array{0: User, 1: StudentProfile}
 */
function makeRetailStudent(): array
{
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('SecurePass123!'),
        'must_reset_password' => false,
    ]);

    $profile = StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_completed_at' => now(),
    ]);

    \App\Models\ParentGuardian::query()->create([
        'student_profile_id' => $profile->id,
        'name' => 'Parent',
        'email' => 'parent@example.com',
        'phone' => '757-555-0101',
        'relationship' => 'Mother',
    ]);

    \App\Models\ShippingAddress::query()->create([
        'student_profile_id' => $profile->id,
        'type' => 'home',
        'line1' => '100 Main St',
        'city' => 'Norfolk',
        'region' => 'VA',
        'postal_code' => '23510',
        'country_code' => 'US',
    ]);

    \App\Models\HousingInfo::query()->create([
        'student_profile_id' => $profile->id,
        'university' => 'ODU',
        'residence_hall' => 'Gresham',
        'move_in_date' => now()->addDays(30)->toDateString(),
    ]);

    return [$user, $profile];
}

/**
 * @param  array<string, mixed>  $overrides
 */
function makePackage(StudentProfile $profile, array $overrides = []): RetailPackage
{
    return RetailPackage::query()->create(array_merge([
        'student_profile_id' => $profile->id,
        'retailer' => 'Amazon',
        'description' => 'Mini fridge',
        'tracking_number' => 'TBA' . fake()->numerify('##########'),
        'estimated_arrival' => now()->addDays(5)->toDateString(),
        'status' => RetailPackageStatus::LOGGED,
    ], $overrides));
}

test('student can log a package and acknowledgement is recorded', function () {
    [$user, $profile] = makeRetailStudent();

    $this->actingAs($user)
        ->post(route('student.retail-packages.store'), [
            'retailer' => 'Amazon',
            'description' => 'Mini fridge, dorm size',
            'tracking_number' => 'TBA1234567890',
            'estimated_arrival' => now()->addDays(7)->toDateString(),
            'acknowledge' => '1',
        ])
        ->assertRedirect(route('student.retail-packages'));

    expect(RetailPackage::query()->where('student_profile_id', $profile->id)->count())->toBe(1)
        ->and($profile->fresh()->retail_packages_acknowledged_at)->not->toBeNull();
});

test('acknowledgement is required on the first package but skipped afterward', function () {
    [$user, $profile] = makeRetailStudent();

    $this->actingAs($user)
        ->post(route('student.retail-packages.store'), [
            'retailer' => 'Amazon',
            'description' => 'Desk lamp',
            'tracking_number' => 'TBA0000000001',
            'estimated_arrival' => now()->addDays(7)->toDateString(),
        ])
        ->assertSessionHasErrors('acknowledge');

    expect(RetailPackage::query()->where('student_profile_id', $profile->id)->count())->toBe(0);

    $profile->forceFill(['retail_packages_acknowledged_at' => now()])->save();

    $this->actingAs($user->fresh())
        ->post(route('student.retail-packages.store'), [
            'retailer' => 'Target',
            'description' => 'Bedding kit',
            'tracking_number' => 'TBA0000000002',
            'estimated_arrival' => now()->addDays(7)->toDateString(),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(RetailPackage::query()->where('student_profile_id', $profile->id)->count())->toBe(1);
});

test('the active package cap is enforced', function () {
    [, $profile] = makeRetailStudent();
    $service = app(RetailPackageService::class);
    $cap = $service->activeCap();

    for ($i = 0; $i < $cap; $i++) {
        $service->create($profile, [
            'retailer' => 'Amazon',
            'description' => "Item {$i}",
            'tracking_number' => 'TBA' . str_pad((string) $i, 10, '0', STR_PAD_LEFT),
            'estimated_arrival' => now()->addDays(5)->toDateString(),
        ]);
    }

    expect(fn () => $service->create($profile, [
        'retailer' => 'Amazon',
        'description' => 'One too many',
        'tracking_number' => 'TBA9999999999',
        'estimated_arrival' => now()->addDays(5)->toDateString(),
    ]))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('student cannot edit a package once received at hub', function () {
    [$user, $profile] = makeRetailStudent();
    $package = makePackage($profile, ['status' => RetailPackageStatus::RECEIVED_AT_HUB]);

    $this->actingAs($user)
        ->put(route('student.retail-packages.update', $package), [
            'retailer' => 'Amazon',
            'description' => 'Changed',
            'tracking_number' => 'TBA1111111111',
            'estimated_arrival' => now()->addDays(3)->toDateString(),
        ])
        ->assertForbidden();
});

test('student cannot edit another students package', function () {
    [$user] = makeRetailStudent();
    [, $otherProfile] = makeRetailStudent();
    $package = makePackage($otherProfile);

    $this->actingAs($user)
        ->put(route('student.retail-packages.update', $package), [
            'retailer' => 'Amazon',
            'description' => 'Hijack',
            'tracking_number' => 'TBA2222222222',
            'estimated_arrival' => now()->addDays(3)->toDateString(),
        ])
        ->assertForbidden();
});

test('backward transition is rejected without force', function () {
    [, $profile] = makeRetailStudent();
    $package = makePackage($profile, ['status' => RetailPackageStatus::RECEIVED_AT_HUB]);
    $service = app(RetailPackageService::class);

    expect(fn () => $service->transition($package, RetailPackageStatus::LOGGED))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('admin force override moves status backward and writes audit history', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    [, $profile] = makeRetailStudent();
    $package = makePackage($profile, ['status' => RetailPackageStatus::RECEIVED_AT_HUB]);

    $this->actingAs($admin)
        ->put(route('admin.retail-packages.update', $package), [
            'status' => RetailPackageStatus::IN_TRANSIT,
            'status_note' => 'Mis-scanned at hub',
            'force_status' => '1',
        ])
        ->assertRedirect();

    expect($package->fresh()->status)->toBe(RetailPackageStatus::IN_TRANSIT)
        ->and(RetailPackageStatusHistory::query()->where('retail_package_id', $package->id)->where('to_status', RetailPackageStatus::IN_TRANSIT)->exists())->toBeTrue();
});

test('reaching notifiable statuses queues the student email and records in-app notifications', function () {
    Mail::fake();
    [$user, $profile] = makeRetailStudent();
    $package = makePackage($profile, ['status' => RetailPackageStatus::IN_TRANSIT]);
    $service = app(RetailPackageService::class);

    $service->transition($package, RetailPackageStatus::RECEIVED_AT_HUB);
    $service->transition($package->fresh(), RetailPackageStatus::STAGED_FOR_DELIVERY);
    $service->transition($package->fresh(), RetailPackageStatus::DELIVERED_TO_DORM);

    Mail::assertQueued(PortalNotificationMail::class, 3);

    expect(\App\Models\PortalNotification::query()->where('user_id', $user->id)->count())->toBe(3);
});

test('carrier link builder maps known retailers and falls back', function () {
    $builder = app(CarrierLinkBuilder::class);

    expect($builder->build('FedEx', '794612345678'))->toContain('fedex.com')
        ->and($builder->build('UPS', '1Z999'))->toContain('ups.com')
        ->and($builder->build('USPS', '9400111'))->toContain('usps.com')
        ->and($builder->build('Unknown Store', 'ABC123'))->toContain('google.com')
        ->and($builder->build('FedEx', ''))->toBeNull();
});

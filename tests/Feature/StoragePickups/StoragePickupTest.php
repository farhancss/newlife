<?php

use App\Enums\AddOnStatus;
use App\Enums\ContainerStatus;
use App\Enums\StoragePickupStatus;
use App\Enums\UserRole;
use App\Models\Container;
use App\Models\StoragePickup;
use App\Models\StudentAddOn;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\NewLifeIdGenerator;
use App\Services\StorageEligibilityService;
use App\Services\StoragePickupService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * @return array{0: User, 1: StudentProfile, 2: Container}
 */
function makeStorageStudent(bool $eligible = true, string $containerStatus = ContainerStatus::DELIVERED_TO_DORM): array
{
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('SecurePass123!'),
        'must_reset_password' => false,
    ]);

    $profile = StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Sasha',
        'last_name' => 'Storage',
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

    if ($eligible) {
        StudentAddOn::query()->create([
            'student_profile_id' => $profile->id,
            'add_on_slug' => StudentAddOn::SUMMER_STORAGE_SLUG,
            'name' => 'Full-Service Summer Storage',
            'price_cents' => 19900,
            'squarespace_url' => 'https://example.com/storage',
            'status' => AddOnStatus::ACTIVE,
        ]);
    }

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-STORAGE-' . $profile->id,
        'status' => $containerStatus,
    ]);

    return [$user, $profile, $container];
}

it('lets an eligible student request an end-of-year pickup and notifies an admin', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    [$user, $profile, $container] = makeStorageStudent();

    $this->actingAs($user)
        ->post(route('student.move-tracking.end-of-year-pickup', $container), [
            'requested_pickup_date' => now()->addWeek()->toDateString(),
            'pickup_location' => 'Gresham Hall, Room 204',
            'contact_phone' => '757-555-0101',
            'container_count' => 2,
            'notes' => 'Two bins by the door',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $pickup = StoragePickup::query()->where('student_profile_id', $profile->id)->first();

    expect($pickup)->not->toBeNull()
        ->and($pickup->status)->toBe(StoragePickupStatus::REQUESTED)
        ->and($pickup->isActive())->toBeTrue()
        ->and(\App\Models\PortalNotification::query()->where('user_id', $admin->id)->exists())->toBeTrue();
});

it('blocks a student without storage from requesting a pickup', function () {
    [$user, , $container] = makeStorageStudent(eligible: false);

    $this->actingAs($user)
        ->post(route('student.move-tracking.end-of-year-pickup', $container), [
            'requested_pickup_date' => now()->addWeek()->toDateString(),
            'pickup_location' => 'Gresham Hall',
        ])
        ->assertSessionHasErrors('storage');

    expect(StoragePickup::query()->count())->toBe(0);
});

it('blocks a pickup request before the container is delivered to the dorm', function () {
    [$user, , $container] = makeStorageStudent(containerStatus: ContainerStatus::SHIPPED_TO_HOME);

    $this->actingAs($user)
        ->post(route('student.move-tracking.end-of-year-pickup', $container), [
            'requested_pickup_date' => now()->addWeek()->toDateString(),
            'pickup_location' => 'Gresham Hall',
        ])
        ->assertSessionHasErrors('storage');
});

it('rejects a second active pickup request', function () {
    Mail::fake();
    [$user, $profile, $container] = makeStorageStudent();

    StoragePickup::query()->create([
        'student_profile_id' => $profile->id,
        'container_id' => $container->id,
        'status' => StoragePickupStatus::REQUESTED,
        'requested_pickup_date' => now()->addWeek()->toDateString(),
        'pickup_location' => 'Existing',
    ]);

    $this->actingAs($user)
        ->post(route('student.move-tracking.end-of-year-pickup', $container), [
            'requested_pickup_date' => now()->addWeek()->toDateString(),
            'pickup_location' => 'Gresham Hall',
        ])
        ->assertSessionHasErrors('storage');
});

it('validates required fields and a future pickup date', function () {
    [$user, , $container] = makeStorageStudent();

    $this->actingAs($user)
        ->post(route('student.move-tracking.end-of-year-pickup', $container), [
            'requested_pickup_date' => now()->subDay()->toDateString(),
            'pickup_location' => '',
        ])
        ->assertSessionHasErrors(['requested_pickup_date', 'pickup_location']);
});

it('forbids requesting a pickup on another students container', function () {
    [$user] = makeStorageStudent();
    [, , $otherContainer] = makeStorageStudent();

    $this->actingAs($user)
        ->post(route('student.move-tracking.end-of-year-pickup', $otherContainer), [
            'requested_pickup_date' => now()->addWeek()->toDateString(),
            'pickup_location' => 'Elsewhere',
        ])
        ->assertForbidden();
});

it('shows the admin storage pickups index with stats, search and edit drawer', function () {
    [, $profile, $container] = makeStorageStudent();
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $pickup = StoragePickup::query()->create([
        'student_profile_id' => $profile->id,
        'container_id' => $container->id,
        'status' => StoragePickupStatus::REQUESTED,
        'requested_pickup_date' => now()->addWeek()->toDateString(),
        'pickup_location' => 'Gresham Hall',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.storage-pickups', ['q' => 'Sasha', 'status' => StoragePickupStatus::REQUESTED, 'edit' => $pickup->id]))
        ->assertOk()
        ->assertSee('Gresham Hall');
});

it('lets an admin advance a pickup, stamp confirmation and notify the student', function () {
    Mail::fake();
    [, $profile, $container] = makeStorageStudent();
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $pickup = StoragePickup::query()->create([
        'student_profile_id' => $profile->id,
        'container_id' => $container->id,
        'status' => StoragePickupStatus::REQUESTED,
        'requested_pickup_date' => now()->addWeek()->toDateString(),
        'pickup_location' => 'Gresham Hall',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.storage-pickups.update', $pickup), [
            'status' => StoragePickupStatus::SCHEDULED,
            'confirmed_pickup_date' => now()->addWeeks(2)->toDateString(),
            'admin_notes' => 'Scheduled with the dorm front desk.',
        ])
        ->assertRedirect(route('admin.storage-pickups'));

    $pickup->refresh();
    expect($pickup->status)->toBe(StoragePickupStatus::SCHEDULED)
        ->and($pickup->confirmed_at)->not->toBeNull()
        ->and($pickup->confirmed_by_user_id)->toBe($admin->id);
});

it('rejects a confirmed pickup date before the requested date', function () {
    [, $profile, $container] = makeStorageStudent();
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $pickup = StoragePickup::query()->create([
        'student_profile_id' => $profile->id,
        'container_id' => $container->id,
        'status' => StoragePickupStatus::REQUESTED,
        'requested_pickup_date' => now()->addWeeks(2)->toDateString(),
        'pickup_location' => 'Gresham Hall',
    ]);

    $this->actingAs($admin)
        ->put(route('admin.storage-pickups.update', $pickup), [
            'status' => StoragePickupStatus::SCHEDULED,
            'confirmed_pickup_date' => now()->addDay()->toDateString(),
        ])
        ->assertSessionHasErrors('confirmed_pickup_date');
});

it('exposes eligibility reason, active pickup and timeline via the services', function () {
    Mail::fake();
    [, $profile, $container] = makeStorageStudent();

    $eligibility = app(StorageEligibilityService::class);
    expect($eligibility->isEligible($profile))->toBeTrue()
        ->and($eligibility->reason($profile))->toBe('add_on');

    $service = app(StoragePickupService::class);
    $pickup = $service->requestForStudent($profile, $container, [
        'requested_pickup_date' => now()->addWeek()->toDateString(),
        'pickup_location' => 'Gresham Hall',
    ]);

    expect($service->activeFor($profile)?->id)->toBe($pickup->id);

    $timeline = $service->timelineFor($pickup);
    expect($timeline)->toHaveCount(6)
        ->and($timeline[0]['current'])->toBeTrue()
        ->and($timeline[0]['reached'])->toBeTrue();
});

it('reports no eligibility reason for an ineligible student', function () {
    [, $profile] = makeStorageStudent(eligible: false);

    expect(app(StorageEligibilityService::class)->reason($profile))->toBeNull();
});

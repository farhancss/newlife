<?php

use App\Enums\ContainerStatus;
use App\Enums\DeadlineStatus;
use App\Enums\DeadlineType;
use App\Enums\RetailPackageStatus;
use App\Enums\UserRole;
use App\Mail\PortalNotificationMail;
use App\Models\Container;
use App\Models\Deadline;
use App\Models\PortalNotification;
use App\Models\RetailPackage;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ContainerWorkflowService;
use App\Services\DeadlineService;
use App\Services\RetailPackageService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * @return array{0: User, 1: StudentProfile}
 */
function makeDeadlineStudent(bool $onboarded = true): array
{
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('SecurePass123!'),
        'must_reset_password' => false,
    ]);

    $profile = StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Dana',
        'last_name' => 'Scott',
        'phone' => '757-555-0190',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_completed_at' => $onboarded ? now() : null,
    ]);

    \App\Models\ParentGuardian::query()->create([
        'student_profile_id' => $profile->id,
        'name' => 'Pat Scott',
        'email' => 'pat.scott@example.com',
        'phone' => '757-555-0191',
        'relationship' => 'Parent',
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

it('case 01: opens a profile-completion deadline and completes it on onboarding', function () {
    Mail::fake();
    [, $profile] = makeDeadlineStudent(onboarded: false);

    app(DeadlineService::class)->openProfileCompletion($profile);

    $deadline = $profile->deadlines()->where('type', DeadlineType::PROFILE_COMPLETION)->first();
    expect($deadline)->not->toBeNull()
        ->and($deadline->status)->toBe(DeadlineStatus::UPCOMING)
        ->and($deadline->due_at->isAfter(now()->addDays(6)))->toBeTrue();

    // Completing onboarding satisfies it.
    app(\App\Services\OnboardingService::class)->complete($profile);

    expect($deadline->fresh()->status)->toBe(DeadlineStatus::COMPLETED);
    Mail::assertQueued(PortalNotificationMail::class);
});

it('case 01: completing the profile via the completion service satisfies the deadline', function () {
    Mail::fake();
    [, $profile] = makeDeadlineStudent(onboarded: false);

    // Open the deadline while the profile is still incomplete.
    app(DeadlineService::class)->openProfileCompletion($profile);
    $deadline = $profile->deadlines()->where('type', DeadlineType::PROFILE_COMPLETION)->first();
    expect($deadline->status)->toBe(DeadlineStatus::UPCOMING);

    // The profile is fully populated by the helper, so syncing completion (the
    // path used by the profile page) should flip it to 100% and complete it.
    app(\App\Services\ProfileCompletionService::class)
        ->syncCompletionStatus($profile->fresh(['parentGuardian', 'shippingAddress', 'housingInfo']));

    expect($deadline->fresh()->status)->toBe(DeadlineStatus::COMPLETED);
});

it('case 02: opens a 3-day pickup deadline on home delivery and completes when pickup scheduled', function () {
    Mail::fake();
    [$user, $profile] = makeDeadlineStudent();
    $workflow = app(ContainerWorkflowService::class);
    $container = $workflow->createForStudent($profile);

    $workflow->transition($container, ContainerStatus::DELIVERED_TO_HOME, $user, force: true);

    $deadline = Deadline::query()->where('type', DeadlineType::CONTAINER_PICKUP)->first();
    expect($deadline)->not->toBeNull()
        ->and($deadline->status)->toBe(DeadlineStatus::UPCOMING);

    $workflow->transition($container->fresh(), ContainerStatus::PICKUP_SCHEDULED, $user, force: true);

    expect($deadline->fresh()->status)->toBe(DeadlineStatus::COMPLETED);
});

it('case 04: add-on container pickup uses its own deadline type', function () {
    Mail::fake();
    [$user, $profile] = makeDeadlineStudent();
    $workflow = app(ContainerWorkflowService::class);
    $container = $workflow->createForStudent($profile, $user, Container::SOURCE_ADD_ON);

    $workflow->transition($container, ContainerStatus::DELIVERED_TO_HOME, $user, force: true);

    $deadline = Deadline::query()->where('type', DeadlineType::ADDON_CONTAINER_PICKUP)->first();
    expect($deadline)->not->toBeNull();
});

it('case 03: opens a retail-arrival deadline and completes it on receipt at hub', function () {
    Mail::fake();
    [$user, $profile] = makeDeadlineStudent();
    $service = app(RetailPackageService::class);

    $package = $service->create($profile, [
        'retailer' => 'Amazon',
        'description' => 'Bedding',
        'tracking_number' => 'TBA123',
        'estimated_arrival' => now()->addDays(5)->toDateString(),
    ], $user);

    $deadline = Deadline::query()->where('type', DeadlineType::RETAIL_ARRIVAL)->first();
    expect($deadline)->not->toBeNull()
        ->and($deadline->status)->toBe(DeadlineStatus::UPCOMING);

    $service->transition($package->fresh(), RetailPackageStatus::RECEIVED_AT_HUB, $user, force: true);

    expect($deadline->fresh()->status)->toBe(DeadlineStatus::COMPLETED);
});

it('does not open a retail deadline without an estimated arrival', function () {
    [$user, $profile] = makeDeadlineStudent();

    app(RetailPackageService::class)->create($profile, [
        'retailer' => 'Amazon',
        'description' => 'Bedding',
        'tracking_number' => 'TBA124',
    ], $user);

    expect(Deadline::query()->where('type', DeadlineType::RETAIL_ARRIVAL)->count())->toBe(0);
});

it('marks past-due deadlines overdue and emails the student once', function () {
    Mail::fake();
    [$user, $profile] = makeDeadlineStudent(onboarded: false);

    $deadline = Deadline::query()->create([
        'student_profile_id' => $profile->id,
        'deadlinable_type' => $profile->getMorphClass(),
        'deadlinable_id' => $profile->id,
        'type' => DeadlineType::PROFILE_COMPLETION,
        'title' => 'Complete your profile',
        'description' => 'Finish onboarding.',
        'status' => DeadlineStatus::UPCOMING,
        'due_at' => now()->subDay(),
    ]);

    $summary = app(DeadlineService::class)->evaluate();
    expect($summary['overdue'])->toBe(1)
        ->and($deadline->fresh()->status)->toBe(DeadlineStatus::OVERDUE);

    Mail::assertQueued(PortalNotificationMail::class, 1);

    // Running again does not re-notify.
    app(DeadlineService::class)->evaluate();
    Mail::assertQueued(PortalNotificationMail::class, 1);
});

it('sends a one-day reminder once', function () {
    Mail::fake();
    [, $profile] = makeDeadlineStudent(onboarded: false);

    Deadline::query()->create([
        'student_profile_id' => $profile->id,
        'deadlinable_type' => $profile->getMorphClass(),
        'deadlinable_id' => $profile->id,
        'type' => DeadlineType::PROFILE_COMPLETION,
        'title' => 'Complete your profile',
        'description' => 'Finish onboarding.',
        'status' => DeadlineStatus::UPCOMING,
        'due_at' => now()->addHours(12),
    ]);

    $summary = app(DeadlineService::class)->evaluate();
    expect($summary['reminded'])->toBe(1);
    Mail::assertQueued(PortalNotificationMail::class, 1);

    app(DeadlineService::class)->evaluate();
    Mail::assertQueued(PortalNotificationMail::class, 1);

    expect(PortalNotification::query()->where('type', 'deadline.reminder')->count())->toBe(1);
});

it('evaluate command runs and reports a summary', function () {
    $this->artisan('deadlines:evaluate')->assertSuccessful();
});

it('student can view the deadline center', function () {
    [$user, $profile] = makeDeadlineStudent();
    Deadline::query()->create([
        'student_profile_id' => $profile->id,
        'deadlinable_type' => $profile->getMorphClass(),
        'deadlinable_id' => $profile->id,
        'type' => DeadlineType::PROFILE_COMPLETION,
        'title' => 'Complete your profile',
        'status' => DeadlineStatus::COMPLETED,
        'due_at' => now()->addDays(3),
        'completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('student.deadlines'))
        ->assertOk()
        ->assertSee('Deadline Center')
        ->assertSee('Complete your profile');
});

it('admin can view the deadline center with stats', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN, 'must_reset_password' => false]);
    [, $profile] = makeDeadlineStudent();
    Deadline::query()->create([
        'student_profile_id' => $profile->id,
        'deadlinable_type' => $profile->getMorphClass(),
        'deadlinable_id' => $profile->id,
        'type' => DeadlineType::PROFILE_COMPLETION,
        'title' => 'Complete your profile',
        'status' => DeadlineStatus::OVERDUE,
        'due_at' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.deadlines'))
        ->assertOk()
        ->assertSee('Deadline Center')
        ->assertSee('Complete your profile');
});

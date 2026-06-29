<?php

use App\Enums\ContainerStatus;
use App\Enums\DeadlineStatus;
use App\Enums\DeadlineType;
use App\Enums\PackageTier;
use App\Models\Container;
use App\Models\ContainerPhoto;
use App\Models\Deadline;
use App\Models\RetailPackage;
use App\Services\StudentPackageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('renders the admin deadline center with search and status filters', function () {
    $admin = makeAdmin();
    [, $profile] = completeStudent();

    Deadline::query()->create([
        'student_profile_id' => $profile->id,
        'type' => DeadlineType::PROFILE_COMPLETION,
        'title' => 'Finish your profile',
        'status' => DeadlineStatus::UPCOMING,
        'due_at' => now()->addWeek(),
    ]);
    Deadline::query()->create([
        'student_profile_id' => $profile->id,
        'type' => DeadlineType::CONTAINER_PICKUP,
        'title' => 'Pickup overdue',
        'status' => DeadlineStatus::UPCOMING,
        'due_at' => now()->subWeek(),
    ]);
    Deadline::query()->create([
        'student_profile_id' => $profile->id,
        'type' => DeadlineType::RETAIL_ARRIVAL,
        'title' => 'Done already',
        'status' => DeadlineStatus::COMPLETED,
        'due_at' => now()->subMonth(),
        'completed_at' => now()->subWeek(),
    ]);

    foreach (['', 'upcoming', 'overdue', 'completed'] as $status) {
        $this->actingAs($admin)
            ->get(route('admin.deadlines', $status === '' ? [] : ['status' => $status]))
            ->assertOk();
    }

    $this->actingAs($admin)
        ->get(route('admin.deadlines', ['q' => 'Student']))
        ->assertOk();
});

it('lets a legacy student log, edit and remove a retail package', function () {
    [$user, $profile] = completeStudent();
    app(StudentPackageService::class)->assignFromTier($profile, PackageTier::LEGACY);

    $this->actingAs($user)->post(route('student.retail-packages.store'), [
        'retailer' => 'Amazon',
        'description' => 'Desk lamp',
        'tracking_number' => 'AMZ-100',
        'estimated_arrival' => now()->addWeek()->toDateString(),
        'acknowledge' => '1',
    ])->assertRedirect(route('student.retail-packages'));

    $package = RetailPackage::query()->where('student_profile_id', $profile->id)->firstOrFail();

    $this->actingAs($user)->put(route('student.retail-packages.update', $package), [
        'retailer' => 'Amazon',
        'description' => 'Updated lamp',
        'tracking_number' => 'AMZ-100',
        'estimated_arrival' => now()->addWeek()->toDateString(),
    ])->assertRedirect(route('student.retail-packages'));

    expect($package->fresh()->description)->toBe('Updated lamp');

    $this->actingAs($user)->delete(route('student.retail-packages.destroy', $package))
        ->assertRedirect(route('student.retail-packages'));

    expect($package->fresh()?->trashed())->toBeTrue();
});

it('lets a student upload and remove container photos while packing', function () {
    Storage::fake('public');
    [$user, $profile] = completeStudent();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-PHOTO-1',
        'status' => ContainerStatus::CUSTOMER_PACKING,
        'source' => Container::SOURCE_MOVE,
    ]);

    $this->actingAs($user)->post(route('student.move-tracking.photos.store', $container), [
        'photos' => [UploadedFile::fake()->image('box.jpg')],
        'acknowledge' => '1',
    ])->assertRedirect();

    $photo = ContainerPhoto::query()->where('container_id', $container->id)->firstOrFail();
    Storage::disk('public')->assertExists($photo->path);

    $this->actingAs($user)->delete(route('student.move-tracking.photos.destroy', [$container, $photo]))
        ->assertRedirect();

    expect(ContainerPhoto::query()->where('container_id', $container->id)->count())->toBe(0);
});

it('rejects container photo uploads when the slot limit is exceeded', function () {
    Storage::fake('public');
    [$user, $profile] = completeStudent();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-PHOTO-2',
        'status' => ContainerStatus::CUSTOMER_PACKING,
        'source' => Container::SOURCE_MOVE,
    ]);

    $files = [];
    for ($i = 0; $i < 6; $i++) {
        $files[] = UploadedFile::fake()->image("box-{$i}.jpg");
    }

    $this->actingAs($user)->post(route('student.move-tracking.photos.store', $container), [
        'photos' => $files,
        'acknowledge' => '1',
    ])->assertSessionHasErrors('photos');
});

it('exposes the student profile relation on housing and shipping records', function () {
    [, $profile] = completeStudent();

    expect($profile->housingInfo->studentProfile->id)->toBe($profile->id)
        ->and($profile->shippingAddress->studentProfile->id)->toBe($profile->id);
});

/**
 * @return array{0: \App\Models\User, 1: \App\Models\StudentProfile}
 */
function incompleteProfileStudent(): array
{
    $user = \App\Models\User::factory()->create([
        'role' => \App\Enums\UserRole::STUDENT,
        'status' => \App\Enums\UserStatus::INCOMPLETE,
        'must_reset_password' => false,
    ]);

    $profile = \App\Models\StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Ima',
        'last_name' => 'Incomplete',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_step' => 1,
    ]);

    return [$user, $profile];
}

it('advances an incomplete profile section and saves changes', function () {
    [$user] = incompleteProfileStudent();

    $this->actingAs($user)->post(route('student.profile.update'), [
        'section' => 1,
        'action' => 'next',
        'first_name' => 'Changed',
        'last_name' => 'Name',
        'phone' => '757-555-0123',
        'school' => 'William & Mary',
        'incoming_year' => '2027',
    ])->assertRedirect();
});

it('keeps a profile section in place when nothing changed', function () {
    [$user] = incompleteProfileStudent();

    $this->actingAs($user)->post(route('student.profile.update'), [
        'section' => 1,
        'action' => 'save',
        'first_name' => 'Ima',
        'last_name' => 'Incomplete',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
    ])->assertRedirect(route('student.profile', ['section' => 1]));
});

it('warns when the final dorm section is still incomplete', function () {
    [$user] = incompleteProfileStudent();

    $this->actingAs($user)->post(route('student.profile.update'), [
        'section' => 4,
        'action' => 'next',
        'university' => 'Old Dominion University',
        'residence_hall' => 'Gresham',
        'move_in_date' => now()->addMonth()->format('Y-m-d'),
    ])->assertRedirect();
});

it('completes the profile when the last change is saved', function () {
    [$user] = completeStudent();

    $this->actingAs($user)->post(route('student.profile.update'), [
        'section' => 1,
        'action' => 'next',
        'first_name' => 'Finalized',
        'last_name' => 'Student',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
    ])->assertRedirect(route('student.dashboard'));
});

it('replaces and removes a student avatar', function () {
    Storage::fake('public');
    [$user] = completeStudent();

    $this->actingAs($user)->post(route('student.profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('first.jpg'),
    ])->assertRedirect();

    $first = $user->fresh()->avatar_path;
    expect($first)->not->toBeNull();

    $this->actingAs($user)->postJson(route('student.profile.avatar.update'), [
        'avatar' => UploadedFile::fake()->image('second.jpg'),
    ])->assertOk()->assertJsonStructure(['message', 'avatar_url']);

    Storage::disk('public')->assertMissing($first);

    $this->actingAs($user)->deleteJson(route('student.profile.avatar.destroy'))
        ->assertOk();
    expect($user->fresh()->avatar_path)->toBeNull();
});

<?php

use App\Enums\ContainerStatus;
use App\Enums\PackageTier;
use App\Enums\UserRole;
use App\Models\Container;
use App\Models\ContainerPhoto;
use App\Models\HousingInfo;
use App\Models\ParentGuardian;
use App\Models\ShippingAddress;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ContainerWorkflowService;
use App\Services\MoveProgressService;
use App\Services\NewLifeIdGenerator;
use App\Services\ProfileCompletionService;
use App\Services\StudentPackageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{0: User, 1: StudentProfile}
 */
function createStudentWithAddress(bool $complete = true): array
{
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('SecurePass123!'),
        'must_reset_password' => false,
    ]);

    $profile = StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_completed_at' => $complete ? now() : null,
    ]);

    ParentGuardian::query()->create([
        'student_profile_id' => $profile->id,
        'name' => 'Parent',
        'email' => 'parent@example.com',
        'phone' => '757-555-0101',
        'relationship' => 'Mother',
    ]);

    ShippingAddress::query()->create([
        'student_profile_id' => $profile->id,
        'type' => 'home',
        'line1' => '100 Main St',
        'city' => 'Norfolk',
        'region' => 'VA',
        'postal_code' => '23510',
        'country_code' => 'US',
    ]);

    HousingInfo::query()->create([
        'student_profile_id' => $profile->id,
        'university' => 'ODU',
        'residence_hall' => 'Gresham',
        'move_in_date' => now()->addDays(30)->toDateString(),
    ]);

    return [$user, $profile];
}

test('onboarding completion creates a single move shipment regardless of package size', function () {
    Mail::fake();

    [, $profile] = createStudentWithAddress(complete: false);
    app(StudentPackageService::class)->assignFromTier($profile, PackageTier::SUMMIT);

    app(ProfileCompletionService::class)->syncCompletionStatus($profile->fresh());

    $containers = Container::query()->where('student_profile_id', $profile->id)->get();

    expect($containers)->toHaveCount(1)
        ->and($containers->first()->status)->toBe(ContainerStatus::CONTAINER_PREPARED)
        ->and($profile->fresh()->move_container_quantity)->toBe(5);
});

test('move shipment provisioning is idempotent on repeated completion sync', function () {
    Mail::fake();

    [, $profile] = createStudentWithAddress(complete: false);
    app(StudentPackageService::class)->assignFromTier($profile, PackageTier::ESSENTIAL);

    $service = app(ProfileCompletionService::class);
    $service->syncCompletionStatus($profile->fresh());
    $service->syncCompletionStatus($profile->fresh());

    expect(Container::query()->where('student_profile_id', $profile->id)->count())->toBe(1);
});

test('timeline includes reached dates from status history', function () {
    $workflow = app(ContainerWorkflowService::class);
    [, $profile] = createStudentWithAddress();

    $container = $workflow->createForStudent($profile);
    $preparedAt = $container->reachedAt(ContainerStatus::CONTAINER_PREPARED);

    $container = $workflow->transition($container, ContainerStatus::LABEL_GENERATED);
    $labelAt = $container->reachedAt(ContainerStatus::LABEL_GENERATED);

    $container->load('statusHistories');
    $timeline = $workflow->timelineFor($container);

    $preparedStep = collect($timeline)->firstWhere('status', ContainerStatus::CONTAINER_PREPARED);
    $labelStep = collect($timeline)->firstWhere('status', ContainerStatus::LABEL_GENERATED);
    $shippedStep = collect($timeline)->firstWhere('status', ContainerStatus::SHIPPED_TO_HOME);

    expect($preparedStep['reached'])->toBeTrue()
        ->and($preparedStep['reached_at']->toDateTimeString())->toBe($preparedAt->toDateTimeString())
        ->and($labelStep['reached'])->toBeTrue()
        ->and($labelStep['current'])->toBeTrue()
        ->and($labelStep['reached_at']->toDateTimeString())->toBe($labelAt->toDateTimeString())
        ->and($shippedStep['reached'])->toBeFalse()
        ->and($shippedStep['reached_at'])->toBeNull();
});

test('student move tracking shows assigned container timeline', function () {
    [$user, $profile] = createStudentWithAddress();

    Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-99999',
        'status' => ContainerStatus::SHIPPED_TO_HOME,
        'outbound_tracking' => '1234567890',
    ]);

    $this->actingAs($user)
        ->get(route('student.move-tracking'))
        ->assertOk()
        ->assertSee('CTN-99999')
        ->assertSee('Shipped to Home')
        ->assertSee('1234567890');
});

test('student cannot reach removed shipment trigger route', function () {
    expect(fn () => route('student.move-tracking.trigger-shipment'))
        ->toThrow(\Symfony\Component\Routing\Exception\RouteNotFoundException::class);
});

test('dashboard and my move show the same high-level stage', function () {
    [$user, $profile] = createStudentWithAddress();

    Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-55555',
        'status' => ContainerStatus::SHIPPED_TO_HOME,
    ]);

    $expectedLabel = app(MoveProgressService::class)->currentLabel($profile->fresh());

    expect($expectedLabel)->toBe('Containers Shipped');

    $this->actingAs($user)->get(route('student.dashboard'))->assertOk()->assertSee($expectedLabel);
    $this->actingAs($user)->get(route('student.move-tracking'))->assertOk()->assertSee('Shipped to Home');
});

test('invalid status transition is rejected without force', function () {
    $profile = StudentProfile::query()->create([
        'user_id' => User::factory()->create(['role' => UserRole::STUDENT])->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
    ]);

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-66666',
        'status' => ContainerStatus::DELIVERED_TO_DORM,
    ]);

    $workflow = app(ContainerWorkflowService::class);

    expect(fn () => $workflow->transition(
        $container,
        ContainerStatus::CONTAINER_PREPARED,
    ))->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('admin advances container status and label generation sets ship-by date', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    [, $profile] = createStudentWithAddress();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-44444',
        'status' => ContainerStatus::CONTAINER_PREPARED,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.containers.update', $container), [
            'status' => ContainerStatus::LABEL_GENERATED,
        ])
        ->assertRedirect();

    $container->refresh();
    expect($container->status)->toBe(ContainerStatus::LABEL_GENERATED)
        ->and($container->ship_by_date)->not->toBeNull();
});

test('student can upload photos while customer packing', function () {
    Storage::fake('public');
    [$user, $profile] = createStudentWithAddress();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-33333',
        'status' => ContainerStatus::CUSTOMER_PACKING,
    ]);

    $this->actingAs($user)
        ->post(route('student.move-tracking.photos.store', $container), [
            'photos' => [UploadedFile::fake()->image('side.jpg')],
            'acknowledge' => '1',
        ])
        ->assertRedirect();

    expect(ContainerPhoto::query()->where('container_id', $container->id)->count())->toBe(1);
});

test('student cannot upload photos when container is not packing', function () {
    Storage::fake('public');
    [$user, $profile] = createStudentWithAddress();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-22222',
        'status' => ContainerStatus::SHIPPED_TO_HOME,
    ]);

    $this->actingAs($user)
        ->post(route('student.move-tracking.photos.store', $container), [
            'photos' => [UploadedFile::fake()->image('side.jpg')],
            'acknowledge' => '1',
        ])
        ->assertForbidden();

    expect(ContainerPhoto::query()->where('container_id', $container->id)->count())->toBe(0);
});

test('photo uploads are capped per container', function () {
    Storage::fake('public');
    [$user, $profile] = createStudentWithAddress();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-11111',
        'status' => ContainerStatus::CUSTOMER_PACKING,
    ]);

    $cap = $container->photoCap();
    $photos = collect(range(1, $cap + 1))
        ->map(fn (int $i) => UploadedFile::fake()->image("p{$i}.jpg"))
        ->all();

    $this->actingAs($user)
        ->post(route('student.move-tracking.photos.store', $container), [
            'photos' => $photos,
            'acknowledge' => '1',
        ])
        ->assertSessionHasErrors('photos');

    expect(ContainerPhoto::query()->where('container_id', $container->id)->count())->toBe(0);
});

test('student can request pickup after uploading a photo and admins are notified', function () {
    Storage::fake('public');
    Mail::fake();
    [$user, $profile] = createStudentWithAddress();
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-44444',
        'status' => ContainerStatus::CUSTOMER_PACKING,
    ]);

    ContainerPhoto::query()->create([
        'container_id' => $container->id,
        'uploaded_by_user_id' => $user->id,
        'disk' => 'public',
        'path' => 'container-photos/sample.jpg',
        'original_name' => 'sample.jpg',
        'mime' => 'image/jpeg',
        'size' => 1024,
    ]);

    $this->actingAs($user)
        ->post(route('student.move-tracking.schedule-pickup', $container))
        ->assertRedirect();

    expect($container->fresh()->status)->toBe(ContainerStatus::PICKUP_SCHEDULED)
        ->and(\App\Models\PortalNotification::query()
            ->where('user_id', $admin->id)
            ->where('type', 'container.pickup_requested')
            ->exists())->toBeTrue();
});

test('student can start packing from delivered to home and admins are notified', function () {
    Mail::fake();
    [$user, $profile] = createStudentWithAddress();
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-77777',
        'status' => ContainerStatus::DELIVERED_TO_HOME,
    ]);

    $this->actingAs($user)
        ->post(route('student.move-tracking.start-packing', $container))
        ->assertRedirect();

    expect($container->fresh()->status)->toBe(ContainerStatus::CUSTOMER_PACKING)
        ->and(\App\Models\PortalNotification::query()
            ->where('user_id', $admin->id)
            ->where('type', 'container.packing_started')
            ->exists())->toBeTrue();
});

test('student cannot start packing before delivery to home', function () {
    [$user, $profile] = createStudentWithAddress();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-88888',
        'status' => ContainerStatus::SHIPPED_TO_HOME,
    ]);

    $this->actingAs($user)
        ->post(route('student.move-tracking.start-packing', $container))
        ->assertSessionHasErrors('packing');

    expect($container->fresh()->status)->toBe(ContainerStatus::SHIPPED_TO_HOME);
});

test('student cannot request pickup without a photo', function () {
    [$user, $profile] = createStudentWithAddress();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-55555',
        'status' => ContainerStatus::CUSTOMER_PACKING,
    ]);

    $this->actingAs($user)
        ->post(route('student.move-tracking.schedule-pickup', $container))
        ->assertSessionHasErrors('pickup');

    expect($container->fresh()->status)->toBe(ContainerStatus::CUSTOMER_PACKING);
});

test('student cannot request pickup outside the packing stage', function () {
    Storage::fake('public');
    [$user, $profile] = createStudentWithAddress();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-66666',
        'status' => ContainerStatus::DELIVERED_TO_HOME,
    ]);

    ContainerPhoto::query()->create([
        'container_id' => $container->id,
        'uploaded_by_user_id' => $user->id,
        'disk' => 'public',
        'path' => 'container-photos/sample.jpg',
        'original_name' => 'sample.jpg',
        'mime' => 'image/jpeg',
        'size' => 1024,
    ]);

    $this->actingAs($user)
        ->post(route('student.move-tracking.schedule-pickup', $container))
        ->assertSessionHasErrors('pickup');

    expect($container->fresh()->status)->toBe(ContainerStatus::DELIVERED_TO_HOME);
});

test('student cannot upload photos to another students container', function () {
    Storage::fake('public');
    [$user] = createStudentWithAddress();
    [, $otherProfile] = createStudentWithAddress();

    $container = Container::query()->create([
        'student_profile_id' => $otherProfile->id,
        'code' => 'CTN-12121',
        'status' => ContainerStatus::CUSTOMER_PACKING,
    ]);

    $this->actingAs($user)
        ->post(route('student.move-tracking.photos.store', $container), [
            'photos' => [UploadedFile::fake()->image('side.jpg')],
            'acknowledge' => '1',
        ])
        ->assertForbidden();
});

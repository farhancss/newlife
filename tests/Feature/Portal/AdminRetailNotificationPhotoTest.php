<?php

use App\Enums\ContainerStatus;
use App\Enums\NotificationCategory;
use App\Enums\PackageTier;
use App\Enums\RetailPackageStatus;
use App\Models\Container;
use App\Models\ContainerPhoto;
use App\Models\PortalNotification;
use App\Models\RetailPackage;
use App\Services\StudentPackageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * @return array{0: \App\Models\User, 1: \App\Models\StudentProfile}
 */
function legacyStudent(): array
{
    [$user, $profile] = completeStudent();
    app(StudentPackageService::class)->assignFromTier($profile, PackageTier::LEGACY);

    return [$user, $profile->fresh()];
}

it('shows the admin retail packages index with filters', function () {
    [, $profile] = legacyStudent();
    RetailPackage::query()->create([
        'student_profile_id' => $profile->id,
        'retailer' => 'Amazon',
        'description' => 'Mini fridge',
        'tracking_number' => 'AMZN1',
        'status' => RetailPackageStatus::LOGGED,
    ]);

    $this->actingAs(makeAdmin())
        ->get(route('admin.retail-packages', ['q' => 'Amazon', 'status' => RetailPackageStatus::LOGGED, 'retailer' => 'Amazon', 'edit' => 1]))
        ->assertOk()
        ->assertSee('Mini fridge');
});

it('lets an admin log a package on behalf of a student', function () {
    Mail::fake();
    [, $profile] = legacyStudent();

    $this->actingAs(makeAdmin())
        ->post(route('admin.retail-packages.store'), [
            'student_profile_id' => $profile->id,
            'retailer' => 'Target',
            'description' => 'Desk lamp',
            'tracking_number' => 'TGT-100',
        ])
        ->assertRedirect(route('admin.retail-packages'))
        ->assertSessionHas('status');

    expect(RetailPackage::query()->where('student_profile_id', $profile->id)->exists())->toBeTrue();
});

it('lets an admin advance a package status and remove a package', function () {
    Mail::fake();
    [, $profile] = legacyStudent();
    $package = RetailPackage::query()->create([
        'student_profile_id' => $profile->id,
        'retailer' => 'Amazon',
        'description' => 'Bedding',
        'tracking_number' => 'AMZN2',
        'status' => RetailPackageStatus::LOGGED,
    ]);

    $admin = makeAdmin();

    $this->actingAs($admin)
        ->put(route('admin.retail-packages.update', $package), [
            'status' => RetailPackageStatus::IN_TRANSIT,
            'status_note' => 'Carrier scanned the label',
        ])
        ->assertRedirect(route('admin.retail-packages'));

    expect($package->fresh()->status)->toBe(RetailPackageStatus::IN_TRANSIT);

    $this->actingAs($admin)
        ->delete(route('admin.retail-packages.destroy', $package), ['removed_reason' => 'Duplicate entry'])
        ->assertRedirect(route('admin.retail-packages'));

    expect($package->fresh()->trashed())->toBeTrue();
});

it('shows the student retail packages page for eligible and ineligible students', function () {
    [$legacyUser] = legacyStudent();
    $this->actingAs($legacyUser)
        ->get(route('student.retail-packages', ['add' => 1]))
        ->assertOk();

    [$plainUser] = completeStudent();
    $this->actingAs($plainUser)
        ->get(route('student.retail-packages'))
        ->assertOk();
});

it('shows the admin notifications index and compose pages', function () {
    completeStudent();

    $admin = makeAdmin();
    $this->actingAs($admin)
        ->get(route('admin.notifications', ['q' => 'x', 'category' => NotificationCategory::ACCOUNT, 'status' => PortalNotification::EMAIL_SENT]))
        ->assertOk();

    $this->actingAs($admin)
        ->get(route('admin.notifications.create'))
        ->assertOk();
});

it('lets an admin send and resend a custom notification', function () {
    Mail::fake();
    [$student] = completeStudent();
    $admin = makeAdmin();

    $this->actingAs($admin)
        ->post(route('admin.notifications.send'), [
            'user_id' => $student->id,
            'category' => NotificationCategory::ACCOUNT,
            'title' => 'Welcome aboard',
            'body' => 'Your portal is ready to use.',
        ])
        ->assertRedirect(route('admin.notifications'))
        ->assertSessionHas('status');

    $notification = PortalNotification::query()->where('user_id', $student->id)->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.notifications.resend', $notification))
        ->assertRedirect(route('admin.notifications'))
        ->assertSessionHas('status');
});

it('validates the admin custom notification form', function () {
    $this->actingAs(makeAdmin())
        ->post(route('admin.notifications.send'), [])
        ->assertSessionHasErrors(['user_id', 'category', 'title', 'body']);
});

it('lets an admin upload and remove hub evidence photos', function () {
    Storage::fake('public');
    Mail::fake();
    [, $profile] = completeStudent();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-HUB-1',
        'status' => ContainerStatus::DELIVERED_TO_DORM,
    ]);

    $admin = makeAdmin();

    $this->actingAs($admin)
        ->post(route('admin.containers.photos.store', $container), [
            'photos' => [UploadedFile::fake()->image('hub.jpg')],
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $photo = ContainerPhoto::query()->where('container_id', $container->id)->firstOrFail();
    expect($photo->type)->toBe(ContainerPhoto::TYPE_HUB_INTAKE);

    $this->actingAs($admin)
        ->delete(route('admin.containers.photos.destroy', [$container, $photo]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(ContainerPhoto::query()->whereKey($photo->id)->exists())->toBeFalse();
});

it('forbids hub photo upload before the container is delivered to the dorm', function () {
    Storage::fake('public');
    [, $profile] = completeStudent();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-HUB-2',
        'status' => ContainerStatus::SHIPPED_TO_HOME,
    ]);

    $this->actingAs(makeAdmin())
        ->post(route('admin.containers.photos.store', $container), [
            'photos' => [UploadedFile::fake()->image('hub.jpg')],
        ])
        ->assertForbidden();
});

it('only allows removing hub evidence photos', function () {
    Storage::fake('public');
    [, $profile] = completeStudent();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-HUB-3',
        'status' => ContainerStatus::DELIVERED_TO_DORM,
    ]);

    $exterior = ContainerPhoto::query()->create([
        'container_id' => $container->id,
        'type' => ContainerPhoto::TYPE_EXTERIOR,
        'disk' => 'public',
        'path' => 'container-photos/ext.jpg',
        'size' => 10,
    ]);

    $this->actingAs(makeAdmin())
        ->delete(route('admin.containers.photos.destroy', [$container, $exterior]))
        ->assertForbidden();
});

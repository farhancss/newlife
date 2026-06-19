<?php

use App\Enums\AddOnStatus;
use App\Enums\ContainerStatus;
use App\Enums\UserRole;
use App\Models\Container;
use App\Models\StudentAddOn;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AddOnService;
use Illuminate\Support\Facades\Hash;

/**
 * @return array{0: User, 1: StudentProfile}
 */
function makeAddOnStudent(): array
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

it('shows the available add-on catalog', function () {
    [$user] = makeAddOnStudent();

    $this->actingAs($user)
        ->get(route('student.add-ons'))
        ->assertOk()
        ->assertSee('Full-Service Summer Storage')
        ->assertSee('Additional Container')
        ->assertSee('$700.00');
});

it('shows a purchased add-on detail page to its owner', function () {
    [$user, $profile] = makeAddOnStudent();
    $service = app(AddOnService::class);

    $addOn = $service->purchase($profile, $service->findInCatalog('protection-coverage'));

    $this->actingAs($user)
        ->get(route('student.add-ons.show', $addOn))
        ->assertOk()
        ->assertSee('Protection Coverage')
        ->assertSee('Active');
});

it('provisions a trackable container when buying the additional container add-on', function () {
    [, $profile] = makeAddOnStudent();
    $service = app(AddOnService::class);

    $addOn = $service->purchase($profile, $service->findInCatalog('additional-container'));

    expect($addOn->status)->toBe(AddOnStatus::ACTIVE)
        ->and($addOn->container_id)->not->toBeNull();

    $container = Container::query()->where('student_profile_id', $profile->id)
        ->where('source', Container::SOURCE_ADD_ON)->first();

    expect($container)->not->toBeNull()
        ->and($container->status)->toBe(ContainerStatus::CONTAINER_PREPARED);
});

it('does not provision a container for non-container add-ons', function () {
    [, $profile] = makeAddOnStudent();
    $service = app(AddOnService::class);

    $addOn = $service->purchase($profile, $service->findInCatalog('protection-coverage'));

    expect($addOn->container_id)->toBeNull()
        ->and(Container::query()->where('student_profile_id', $profile->id)->count())->toBe(0);
});

it('add-on containers do not count against the package move shipment', function () {
    [, $profile] = makeAddOnStudent();
    $service = app(AddOnService::class);

    $service->purchase($profile, $service->findInCatalog('additional-container'));

    expect(app(\App\Services\ContainerWorkflowService::class)->primaryContainer($profile))->toBeNull();
});

it('shows the 12-status move journey on the additional container detail page', function () {
    [$user, $profile] = makeAddOnStudent();
    $service = app(AddOnService::class);

    $addOn = $service->purchase($profile, $service->findInCatalog('additional-container'));

    $this->actingAs($user)
        ->get(route('student.add-ons.show', $addOn))
        ->assertOk()
        ->assertSee('Move progress')
        ->assertSee('Step 1 of 12')
        ->assertSee($addOn->container->code);
});

it('buys an add-on for a student via the temporary command', function () {
    [, $profile] = makeAddOnStudent();

    $this->artisan('portal:buy-addon', [
        'student' => $profile->new_life_id,
        'slug' => 'additional-container',
    ])->assertSuccessful();

    $addOn = $profile->addOns()->first();

    expect($addOn)->not->toBeNull()
        ->and($addOn->status)->toBe(AddOnStatus::ACTIVE)
        ->and($addOn->container_id)->not->toBeNull();
});

it('notifies the student and queues an email when an add-on is purchased', function () {
    \Illuminate\Support\Facades\Mail::fake();

    [$user, $profile] = makeAddOnStudent();

    $service = app(AddOnService::class);
    $service->purchase($profile, $service->findInCatalog('protection-coverage'));

    $notification = \App\Models\PortalNotification::query()
        ->where('user_id', $user->id)
        ->where('type', 'add_on.purchased')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->category)->toBe(\App\Enums\NotificationCategory::ADD_ON)
        ->and($notification->email_status)->toBe(\App\Models\PortalNotification::EMAIL_SENT);

    \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\PortalNotificationMail::class);
});

it('shows purchased add-ons and stats on the admin add-ons page', function () {
    [, $profile] = makeAddOnStudent();
    $service = app(AddOnService::class);
    $service->purchase($profile, $service->findInCatalog('additional-container'));
    $service->purchase($profile, $service->findInCatalog('protection-coverage'));

    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $this->actingAs($admin)
        ->get(route('admin.add-ons'))
        ->assertOk()
        ->assertSee('Additional Container')
        ->assertSee('Protection Coverage')
        ->assertSee('Total purchases')
        ->assertSee($profile->new_life_id);
});

it('shows the admin add-on detail page with the container journey and edit link', function () {
    [, $profile] = makeAddOnStudent();
    $service = app(AddOnService::class);
    $addOn = $service->purchase($profile, $service->findInCatalog('additional-container'));

    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $this->actingAs($admin)
        ->get(route('admin.add-ons.show', $addOn))
        ->assertOk()
        ->assertSee('Container move journey')
        ->assertSee('Edit container')
        ->assertSee($addOn->container->code);
});

it('shows the add-ons section on the admin student detail page', function () {
    [, $profile] = makeAddOnStudent();
    $service = app(AddOnService::class);
    $service->purchase($profile, $service->findInCatalog('additional-container'));

    $admin = User::factory()->create(['role' => UserRole::ADMIN]);

    $this->actingAs($admin)
        ->get(route('admin.students.show', $profile))
        ->assertOk()
        ->assertSee('Add-ons')
        ->assertSee('Additional Container');
});

it('forbids viewing another student add-on', function () {
    [$owner, $ownerProfile] = makeAddOnStudent();
    [$intruder] = makeAddOnStudent();

    $addOn = $ownerProfile->addOns()->create([
        'add_on_slug' => 'protection-coverage',
        'name' => 'Protection Coverage',
        'price_cents' => 9500,
        'squarespace_url' => 'https://example.com',
        'status' => AddOnStatus::ACTIVE,
        'requested_at' => now(),
    ]);

    $this->actingAs($intruder)
        ->get(route('student.add-ons.show', $addOn))
        ->assertForbidden();
});

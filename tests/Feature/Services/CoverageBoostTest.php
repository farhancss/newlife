<?php

use App\Enums\ContainerStatus;
use App\Enums\DeadlineStatus;
use App\Enums\DeadlineType;
use App\Enums\NotificationCategory;
use App\Enums\RetailPackageStatus;
use App\Enums\StoragePickupStatus;
use App\Enums\UserRole;
use App\Mail\PortalNotificationMail;
use App\Models\Container;
use App\Models\ContainerPhoto;
use App\Models\ContainerStatusHistory;
use App\Models\Deadline;
use App\Models\NotificationPreference;
use App\Models\PortalNotification;
use App\Models\RetailPackage;
use App\Models\RetailPackageStatusHistory;
use App\Models\StoragePickup;
use App\Models\StudentAddOn;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AccountProvisioningService;
use App\Services\AdminDashboardService;
use App\Services\InvitationMailService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

it('covers admin dashboard recent activity and unknown student names', function () {
    $admin = makeAdmin();
    [, $profile] = completeStudent();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-ACT1',
        'status' => ContainerStatus::SHIPPED_TO_HOME,
        'source' => Container::SOURCE_MOVE,
    ]);

    ContainerStatusHistory::query()->create([
        'container_id' => $container->id,
        'to_status' => ContainerStatus::SHIPPED_TO_HOME,
        'created_at' => now()->subHour(),
    ]);

    $package = RetailPackage::query()->create([
        'student_profile_id' => $profile->id,
        'retailer' => 'Amazon',
        'description' => 'Desk lamp',
        'tracking_number' => 'PKG-ACT-1',
        'status' => RetailPackageStatus::IN_TRANSIT,
    ]);

    RetailPackageStatusHistory::query()->create([
        'retail_package_id' => $package->id,
        'to_status' => RetailPackageStatus::IN_TRANSIT,
        'created_at' => now()->subMinutes(30),
    ]);

    $orphanHistory = ContainerStatusHistory::query()->create([
        'container_id' => Container::query()->create([
            'student_profile_id' => $profile->id,
            'code' => 'CTN-ORPH',
            'status' => ContainerStatus::LABEL_GENERATED,
            'source' => Container::SOURCE_MOVE,
        ])->id,
        'to_status' => ContainerStatus::LABEL_GENERATED,
        'created_at' => now()->subMinutes(10),
    ]);
    $orphanHistory->container->delete();

    StudentProfile::query()->create([
        'user_id' => User::factory()->create(['role' => UserRole::STUDENT, 'name' => 'Only Name'])->id,
        'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
        'first_name' => '',
        'last_name' => '',
    ]);

    $activity = app(AdminDashboardService::class)->recentActivity(20);

    expect($activity)->not->toBeEmpty()
        ->and(collect($activity)->pluck('type')->unique()->values()->all())
        ->toContain('Container', 'Package', 'Student');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Shipped to Home');
});

it('covers account provisioning edge paths', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'existing-contact@example.com',
        'squarespace_contact_id' => null,
    ]);

    app(AccountProvisioningService::class)->provisionFromContact([
        'contactId' => 'sq-contact-update',
        'firstName' => 'Existing',
        'lastName' => 'Student',
        'primaryEmail' => ['value' => 'existing-contact@example.com'],
    ], false);

    expect($user->fresh()->squarespace_contact_id)->toBe('sq-contact-update');

    $profile = app(AccountProvisioningService::class)->enrichFromOrder(realOrderPayload([
        'customerEmail' => 'enrich@example.com',
        'customerId' => 'enrich-contact-001',
    ]));
    expect($profile)->toBeInstanceOf(StudentProfile::class);

    expect(fn () => app(AccountProvisioningService::class)->provisionFromOrder([
        'id' => 'missing-email',
        'customerEmail' => '',
    ]))->toThrow(RuntimeException::class);

    expect(fn () => app(AccountProvisioningService::class)->provisionFromContact([
        'contactId' => 'no-email',
    ], false))->toThrow(InvalidArgumentException::class);

    app(AccountProvisioningService::class)->syncFromAddressNotification(['data' => []]);
    app(AccountProvisioningService::class)->syncFromAddressNotification([
        'data' => ['contactId' => 'missing', 'addressBookEntryId' => 'entry-1'],
    ]);

    [, $completeProfile] = completeStudent(['email' => 'complete-ship@example.com']);
    app(AccountProvisioningService::class)->provisionFromContact([
        'contactId' => 'sq-complete',
        'primaryEmail' => ['value' => 'complete-ship@example.com'],
        'defaultShippingAddress' => [
            'address' => [
                'address1' => '999 Ship Ln',
                'city' => 'Norfolk',
                'state' => 'VA',
                'postalCode' => '23510',
                'countryCode' => 'US',
            ],
        ],
    ], false);

    expect($completeProfile->fresh()->shippingAddress?->line1)->toBe('100 Main St');

    Mail::fake();
    app(AccountProvisioningService::class)->provisionFromOrder(realOrderPayload([
        'customerEmail' => 'subscription@example.com',
        'customerId' => 'subscription-contact-001',
        'fulfillmentStatus' => 'fulfilled',
        'subscriptionDetails' => [
            'billingPeriod' => 'monthly',
            'currentPeriodEnd' => '2026-12-31T00:00:00Z',
        ],
    ]));
});

it('returns false when invitation mail delivery fails', function () {
    [$user] = completeStudent(['email' => 'invite-fail@example.com']);

    $pending = Mockery::mock();
    $pending->shouldReceive('send')->andThrow(new RuntimeException('SMTP down'));
    Mail::shouldReceive('to')->once()->andReturn($pending);

    expect(app(InvitationMailService::class)->send($user, 'TempPass123!'))->toBeFalse();
});

it('covers storage pickup and deadline notification branches', function () {
    Mail::fake();
    [$user, $profile] = completeStudent();

    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-NOTIF',
        'status' => ContainerStatus::DELIVERED_TO_DORM,
        'source' => Container::SOURCE_MOVE,
    ]);

    $service = app(NotificationService::class);

    expect($service->containerHubEvidenceAdded($container, 0))->toBeNull();

    $service->containerHubEvidenceAdded($container, 2, makeAdmin());

    $pickup = StoragePickup::query()->create([
        'student_profile_id' => $profile->id,
        'status' => StoragePickupStatus::SCHEDULED,
        'requested_pickup_date' => now()->addWeek()->toDateString(),
        'confirmed_pickup_date' => now()->addWeek()->toDateString(),
        'pickup_location' => 'Gresham Hall',
    ]);

    foreach ([
        StoragePickupStatus::SCHEDULED,
        StoragePickupStatus::PICKED_UP,
        StoragePickupStatus::IN_STORAGE,
        StoragePickupStatus::OUT_FOR_RETURN,
        StoragePickupStatus::RETURNED,
        StoragePickupStatus::CANCELLED,
        'partially_returned',
    ] as $status) {
        $pickup->forceFill(['status' => $status])->save();
        $service->storagePickupStatusChanged($pickup->fresh());
    }

    $addOn = StudentAddOn::query()->create([
        'student_profile_id' => $profile->id,
        'add_on_slug' => 'protection-coverage',
        'name' => 'Protection Coverage',
        'price_cents' => 9500,
        'squarespace_url' => 'https://example.com/protection',
        'status' => \App\Enums\AddOnStatus::ACTIVE,
    ]);
    $service->addOnPurchased($addOn);

    $deadline = Deadline::query()->create([
        'student_profile_id' => $profile->id,
        'type' => DeadlineType::PROFILE_COMPLETION,
        'title' => 'Finish profile',
        'status' => DeadlineStatus::UPCOMING,
        'due_at' => now()->addDays(3),
        'description' => 'Complete your profile.',
    ]);

    $service->deadlineReminder($deadline);
    $service->deadlineCompleted($deadline);
    $service->deadlineOverdue($deadline);

    expect(PortalNotification::query()->where('user_id', $user->id)->count())->toBeGreaterThan(5);
});

it('skips parent cc and handles email dispatch edge cases', function () {
    Mail::fake();
    [$user] = makeNotifiableStudent();

    NotificationPreference::query()->updateOrCreate(
        ['user_id' => $user->id],
        ['email_enabled' => true, 'sms_enabled' => false, 'parent_cc_enabled' => false],
    );

    app(NotificationService::class)->notify(
        $user,
        NotificationCategory::SHIPMENT,
        'test.no_parent_cc',
        'No CC',
        'Body',
    );

    Mail::assertQueued(PortalNotificationMail::class, function ($mail) {
        return count($mail->cc ?? []) === 0;
    });

    $user->forceFill(['email' => ''])->saveQuietly();

    $notification = app(NotificationService::class)->notify(
        $user,
        NotificationCategory::SYSTEM,
        'test.no_email',
        'No email',
        'Body',
    );

    expect($notification->email_status)->toBe(PortalNotification::EMAIL_SKIPPED);
});

it('marks email failed when queueing portal notification mail throws', function () {
    [$user] = makeNotifiableStudent();

    $pending = Mockery::mock();
    $pending->shouldReceive('cc')->andReturnSelf();
    $pending->shouldReceive('queue')->andThrow(new RuntimeException('Queue unavailable'));
    Mail::shouldReceive('to')->andReturn($pending);

    $notification = app(NotificationService::class)->notify(
        $user,
        NotificationCategory::SYSTEM,
        'test.mail_fail',
        'Mail fail',
        'Body',
    );

    expect($notification->fresh()->email_status)->toBe(PortalNotification::EMAIL_FAILED)
        ->and($notification->email_attempts)->toBe(1);
});

it('renders move tracking with the default timeline when no containers exist', function () {
    [$user] = completeStudent();

    $this->actingAs($user)
        ->get(route('student.move-tracking'))
        ->assertOk()
        ->assertSee('Move Tracking');
});

it('handles profile save without changes on an incomplete profile', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'must_reset_password' => false,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Edge',
        'last_name' => 'Case',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_step' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('student.profile.update'), [
            'action' => 'save',
            'section' => 1,
            'first_name' => 'Edge',
            'last_name' => 'Case',
            'phone' => '757-555-0100',
            'school' => 'ODU',
            'incoming_year' => '2026',
        ])
        ->assertRedirect(route('student.profile', ['section' => 1]));
});

it('covers remaining controller and service edge paths', function () {
    [, $profile] = completeStudent();
    $admin = makeAdmin();

    // Admin add-ons search filter branches
    $service = app(\App\Services\AddOnService::class);
    $service->purchase($profile, $service->findInCatalog('protection-coverage'));

    $this->actingAs($admin)
        ->get(route('admin.add-ons', ['q' => $profile->first_name, 'status' => \App\Enums\AddOnStatus::ACTIVE]))
        ->assertOk()
        ->assertSee('Protection Coverage');

    // Auth logout + admin login redirect
    $this->actingAs($admin)->get(route('logout'))->assertRedirect(route('login'));

    $this->post(route('login.submit'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    // Container workflow edge cases
    $workflow = app(\App\Services\ContainerWorkflowService::class);
    $container = $workflow->createForStudent($profile);
    expect($workflow->ensureMoveShipment($profile)->id)->toBe($container->id);

    expect(fn () => $workflow->transition($container, 'not-a-status'))
        ->toThrow(\Illuminate\Validation\ValidationException::class);

    $workflow->transition($container, $container->status, note: 'Still packing');

    $container->studentProfile->housingInfo?->update(['move_in_date' => now()->subMonth()->toDateString()]);
    $workflow->transition($container->fresh(), \App\Enums\ContainerStatus::LABEL_GENERATED, force: true);

    // Squarespace order mapper edge cases
    $mapper = app(\App\Services\Squarespace\SquarespaceOrderMapper::class);
    $sparse = $mapper->map(realOrderPayload([
        'createdOn' => 'not-a-date',
        'grandTotal' => ['value' => ''],
        'formSubmission' => 'invalid',
        'lineItems' => [[
            'productName' => 'Basic Package',
            'customizations' => [['value' => 'no label']],
        ]],
        'billingAddress' => ['firstName' => '', 'lastName' => ''],
    ]));
    expect($sparse['student']['incoming_year'])->toBeNull();

    // Deadline service branches
    $deadlines = app(\App\Services\DeadlineService::class);
    expect($deadlines->openProfileCompletion($profile))->toBeNull();

    $orphanContainer = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-ORPHAN',
        'status' => ContainerStatus::DELIVERED_TO_HOME,
        'source' => Container::SOURCE_MOVE,
    ]);
    $orphanContainer->setRelation('studentProfile', null);
    expect($deadlines->openContainerPickup($orphanContainer))->toBeNull();

    // Squarespace webhook subscription failures
    \App\Models\SquarespaceCredential::query()->create([
        'access_token' => 'access-token-123',
        'refresh_token' => 'refresh-token-123',
        'token_type' => 'bearer',
        'expires_at' => now()->addHour(),
    ]);
    config(['squarespace.client_id' => 'test-client-id', 'squarespace.client_secret' => 'test-client-secret']);
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    expect(fn () => app(\App\Services\Squarespace\SquarespaceWebhookSubscriptionService::class)->list())
        ->toThrow(RuntimeException::class);

    Http::fake([
        '*' => fn () => throw new RuntimeException('network down'),
    ]);

    expect(fn () => app(\App\Services\Squarespace\SquarespaceWebhookSubscriptionService::class)->list())
        ->toThrow(RuntimeException::class);

    Http::fake([
        '*' => Http::response(['id' => ''], 200),
    ]);

    expect(fn () => app(\App\Services\Squarespace\SquarespaceWebhookSubscriptionService::class)->create())
        ->toThrow(RuntimeException::class);
});

it('handles a failed squarespace oauth callback', function () {
    $this->mock(\App\Services\Squarespace\SquarespaceOAuthService::class, function ($mock) {
        $mock->shouldReceive('handleCallback')->andThrow(new RuntimeException('Token exchange failed'));
    });

    $this->actingAs(makeAdmin())
        ->withSession(['squarespace_oauth_state' => 'state-123'])
        ->get(route('squarespace.callback', ['state' => 'state-123', 'code' => 'bad-code']))
        ->assertRedirect(route('admin.squarespace'))
        ->assertSessionHas('error');
});

it('covers photo slot limits, password reset errors, and importer edge cases', function () {
    Storage::fake('public');
    Mail::fake();

    [$user, $profile] = completeStudent();
    $container = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-FULL',
        'status' => ContainerStatus::CUSTOMER_PACKING,
        'source' => Container::SOURCE_MOVE,
    ]);

    foreach (range(1, $container->photoCap()) as $i) {
        ContainerPhoto::query()->create([
            'container_id' => $container->id,
            'uploaded_by_user_id' => $user->id,
            'disk' => 'public',
            'path' => "container-photos/full-{$i}.jpg",
            'original_name' => "full-{$i}.jpg",
            'mime' => 'image/jpeg',
            'size' => 1024,
        ]);
    }

    $this->actingAs($user)
        ->post(route('student.move-tracking.photos.store', $container), [
            'photos' => [UploadedFile::fake()->image('extra.jpg')],
            'acknowledge' => '1',
        ])
        ->assertSessionHasErrors('photos');

    $container->photos()->delete();
    foreach (range(1, 4) as $i) {
        ContainerPhoto::query()->create([
            'container_id' => $container->id,
            'uploaded_by_user_id' => $user->id,
            'disk' => 'public',
            'path' => "container-photos/partial-{$i}.jpg",
            'original_name' => "partial-{$i}.jpg",
            'mime' => 'image/jpeg',
            'size' => 1024,
        ]);
    }

    $this->actingAs($user)
        ->post(route('student.move-tracking.photos.store', $container), [
            'photos' => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
            'acknowledge' => '1',
        ])
        ->assertSessionHasErrors('photos');

    $hubContainer = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-HUB',
        'status' => ContainerStatus::DELIVERED_TO_DORM,
        'source' => Container::SOURCE_MOVE,
    ]);

    foreach (range(1, $hubContainer->hubPhotoCap()) as $i) {
        ContainerPhoto::query()->create([
            'container_id' => $hubContainer->id,
            'type' => ContainerPhoto::TYPE_HUB_INTAKE,
            'uploaded_by_user_id' => makeAdmin()->id,
            'disk' => 'public',
            'path' => "container-photos/hub-{$i}.jpg",
            'original_name' => "hub-{$i}.jpg",
            'mime' => 'image/jpeg',
            'size' => 1024,
        ]);
    }

    $this->actingAs(makeAdmin())
        ->post(route('admin.containers.photos.store', $hubContainer), [
            'photos' => [UploadedFile::fake()->image('hub-extra.jpg')],
        ])
        ->assertSessionHasErrors('photos');

    $orphanUser = User::factory()->create(['role' => UserRole::STUDENT]);
    expect(fn () => app(\App\Services\OnboardingService::class)->saveStep($orphanUser, 1, [
        'first_name' => 'No',
        'last_name' => 'Profile',
    ]))->toThrow(RuntimeException::class);

    expect(fn () => app(\App\Services\OnboardingService::class)->saveStep($user, 9, []))
        ->toThrow(InvalidArgumentException::class);

    $profile->shippingAddress?->update(['country_code' => null]);
    expect(app(\App\Services\OnboardingService::class)->hasSectionChanges($profile->fresh(), 3, [
        'line1' => $profile->shippingAddress?->line1,
        'city' => $profile->shippingAddress?->city,
        'region' => $profile->shippingAddress?->region,
        'postal_code' => $profile->shippingAddress?->postal_code,
        'country_code' => 'CA',
    ]))->toBeTrue();

    $importProfile = app(AccountProvisioningService::class)->upsertFromContact([
        'contactId' => 'importer-edge',
        'firstName' => 'Import',
        'lastName' => 'Edge',
        'primaryEmail' => ['value' => 'importer-edge@example.com'],
    ], false);
    app(\App\Services\Squarespace\SquarespaceOrderImporter::class)->import([
        'id' => 'edge-order',
        'lineItems' => ['not-an-array', ['productName' => '', 'quantity' => 1]],
    ], $importProfile);

    [, $deadlineProfile] = completeStudent();
    $pickupContainer = Container::query()->create([
        'student_profile_id' => $deadlineProfile->id,
        'code' => 'CTN-DL',
        'status' => ContainerStatus::PICKUP_SCHEDULED,
        'source' => Container::SOURCE_MOVE,
    ]);

    Deadline::query()->create([
        'student_profile_id' => $deadlineProfile->id,
        'deadlinable_type' => $pickupContainer->getMorphClass(),
        'deadlinable_id' => $pickupContainer->id,
        'type' => DeadlineType::CONTAINER_PICKUP,
        'title' => 'Schedule pickup',
        'description' => 'Pack and schedule.',
        'status' => DeadlineStatus::UPCOMING,
        'due_at' => now()->addWeek(),
    ]);

    expect(app(\App\Services\DeadlineService::class)->evaluate()['completed'])->toBeGreaterThan(0);

    config(['squarespace.client_id' => '', 'squarespace.client_secret' => '']);
    expect(fn () => app(\App\Services\Squarespace\SquarespaceOAuthService::class)->validAccessToken())
        ->toThrow(RuntimeException::class);
});

it('covers the last remaining service and auth edge paths', function () {
    Storage::fake('public');

    $studentNoProfile = User::factory()->create([
        'role' => UserRole::STUDENT,
        'email' => 'no-profile-login@example.com',
        'must_reset_password' => false,
    ]);

    $this->post(route('login.submit'), [
        'email' => 'no-profile-login@example.com',
        'password' => 'password',
    ])->assertRedirect(route('student.profile'));

    $incomplete = User::factory()->create([
        'role' => UserRole::STUDENT,
        'email' => 'incomplete-login@example.com',
        'must_reset_password' => false,
    ]);
    StudentProfile::query()->create([
        'user_id' => $incomplete->id,
        'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Inc',
        'last_name' => 'Complete',
        'onboarding_step' => 2,
    ]);

    $this->post(route('login.submit'), [
        'email' => 'incomplete-login@example.com',
        'password' => 'password',
    ])->assertRedirect(route('student.profile'));

    [, $profile] = completeStudent();
    app(\App\Services\OnboardingService::class)->saveStep($profile->user, 1, [
        'first_name' => $profile->first_name,
        'last_name' => $profile->last_name,
        'phone' => $profile->phone,
        'school' => $profile->school,
        'incoming_year' => $profile->incoming_year,
        'name' => 'Updated Display Name',
    ]);

    expect(app(\App\Services\MoveProgressService::class)->currentLabel(
        StudentProfile::query()->whereKey(
            StudentProfile::query()->create([
                'user_id' => User::factory()->create(['role' => UserRole::STUDENT, 'must_reset_password' => false])->id,
                'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
                'first_name' => 'Onboard',
                'last_name' => 'Ing',
                'onboarding_step' => 1,
            ])->id
        )->firstOrFail()
    ))->toBe('Student Onboarding');

    $hubContainer = Container::query()->create([
        'student_profile_id' => $profile->id,
        'code' => 'CTN-HUB2',
        'status' => ContainerStatus::DELIVERED_TO_DORM,
        'source' => Container::SOURCE_MOVE,
    ]);

    foreach (range(1, 4) as $i) {
        ContainerPhoto::query()->create([
            'container_id' => $hubContainer->id,
            'type' => ContainerPhoto::TYPE_HUB_INTAKE,
            'uploaded_by_user_id' => makeAdmin()->id,
            'disk' => 'public',
            'path' => "container-photos/hub2-{$i}.jpg",
            'original_name' => "hub2-{$i}.jpg",
            'mime' => 'image/jpeg',
            'size' => 1024,
        ]);
    }

    $this->actingAs(makeAdmin())
        ->post(route('admin.containers.photos.store', $hubContainer), [
            'photos' => [
                UploadedFile::fake()->image('hub-a.jpg'),
                UploadedFile::fake()->image('hub-b.jpg'),
            ],
        ])
        ->assertSessionHasErrors('photos');

    config(['squarespace.skip_signature_verification' => true]);
    expect(app(\App\Services\Squarespace\SquarespaceSignatureVerifier::class)->verify('{}', null))->toBeTrue();

    config(['squarespace.skip_signature_verification' => false]);
    expect(app(\App\Services\Squarespace\SquarespaceSignatureVerifier::class)->verify('{}', null))->toBeFalse();

    [, $housingProfile] = completeStudent();
    expect(app(\App\Services\OnboardingService::class)->hasSectionChanges($housingProfile, 4, [
        'university' => $housingProfile->housingInfo?->university,
        'residence_hall' => $housingProfile->housingInfo?->residence_hall,
        'move_in_date' => now()->addMonths(2)->format('Y-m-d'),
    ]))->toBeTrue();
});

it('exposes menu helper utilities for admin navigation', function () {
    config(['devtools.enabled' => true]);
    $this->actingAs(makeAdmin())->get(route('admin.dashboard'));

    $groups = \App\Helpers\MenuHelper::getMenuGroups();
    expect(collect($groups)->pluck('title'))->toContain('Tools')
        ->and(\App\Helpers\MenuHelper::isActive('/admin/dashboard'))->toBeTrue()
        ->and(\App\Helpers\MenuHelper::getIconSvg('dashboard'))->toContain('<svg');
});

<?php

use App\Enums\ContainerStatus;
use App\Enums\NotificationCategory;
use App\Enums\UserRole;
use App\Mail\PortalNotificationMail;
use App\Models\Container;
use App\Models\NotificationPreference;
use App\Models\ParentGuardian;
use App\Models\PortalNotification;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ContainerWorkflowService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * @return array{0: User, 1: StudentProfile}
 */
function makeNotifiableStudent(array $userOverrides = []): array
{
    $user = User::factory()->create(array_merge([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('SecurePass123!'),
        'must_reset_password' => false,
    ], $userOverrides));

    $profile = StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jordan',
        'last_name' => 'Lee',
        'phone' => '757-555-0142',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_completed_at' => now(),
    ]);

    ParentGuardian::query()->create([
        'student_profile_id' => $profile->id,
        'name' => 'Pat Lee',
        'email' => 'pat.lee@example.com',
        'phone' => '757-555-0143',
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

test('container milestone transition creates an in-app notification and queues email', function () {
    Mail::fake();
    [$user, $profile] = makeNotifiableStudent();
    $service = app(ContainerWorkflowService::class);
    $container = $service->createForStudent($profile);

    $service->transition($container, ContainerStatus::LABEL_GENERATED);
    $service->transition($container->fresh(), ContainerStatus::SHIPPED_TO_HOME);

    $notification = PortalNotification::query()->where('user_id', $user->id)->first();

    expect($notification)->not->toBeNull()
        ->and($notification->category)->toBe(NotificationCategory::SHIPMENT)
        ->and($notification->type)->toBe('container.' . ContainerStatus::SHIPPED_TO_HOME)
        ->and($notification->email_status)->toBe(PortalNotification::EMAIL_SENT);

    Mail::assertQueued(PortalNotificationMail::class, 1);
});

test('non-milestone container transition does not notify', function () {
    Mail::fake();
    [$user, $profile] = makeNotifiableStudent();
    $service = app(ContainerWorkflowService::class);
    $container = $service->createForStudent($profile);

    $service->transition($container, ContainerStatus::LABEL_GENERATED);

    expect(PortalNotification::query()->where('user_id', $user->id)->count())->toBe(0);
    Mail::assertNothingQueued();
});

test('email preference off skips email but still records in-app notification', function () {
    Mail::fake();
    [$user, $profile] = makeNotifiableStudent();
    NotificationPreference::query()->create([
        'user_id' => $user->id,
        'email_enabled' => false,
        'sms_enabled' => true,
        'parent_cc_enabled' => true,
    ]);

    $service = app(ContainerWorkflowService::class);
    $container = $service->createForStudent($profile);
    $service->transition($container, ContainerStatus::SHIPPED_TO_HOME, force: true);

    $notification = PortalNotification::query()->where('user_id', $user->id)->first();
    expect($notification->email_status)->toBe(PortalNotification::EMAIL_SKIPPED);
    Mail::assertNothingQueued();
});

test('parent guardian is cc-d on key events when enabled', function () {
    Mail::fake();
    [$user] = makeNotifiableStudent();

    app(NotificationService::class)->notify(
        recipient: $user,
        category: NotificationCategory::SHIPMENT,
        type: 'test.event',
        title: 'Test',
        body: 'Body',
    );

    Mail::assertQueued(PortalNotificationMail::class, function ($mail) {
        return $mail->hasCc('pat.lee@example.com');
    });
});

test('mark read and mark all read update unread count', function () {
    [$user] = makeNotifiableStudent();
    $service = app(NotificationService::class);

    $service->notify($user, NotificationCategory::SYSTEM, 'a', 'One', 'b');
    $service->notify($user, NotificationCategory::SYSTEM, 'a', 'Two', 'b');

    expect($service->unreadCount($user))->toBe(2);

    $first = PortalNotification::query()->where('user_id', $user->id)->first();
    $service->markRead($first);
    expect($service->unreadCount($user))->toBe(1);

    $service->markAllRead($user);
    expect($service->unreadCount($user))->toBe(0);
});

test('student can view notification center and mark all read', function () {
    [$user] = makeNotifiableStudent();
    app(NotificationService::class)->notify($user, NotificationCategory::SYSTEM, 'a', 'Hello', 'World');

    $this->actingAs($user)->get(route('student.notifications'))
        ->assertOk()
        ->assertSee('Hello')
        ->assertSee('Read');

    $this->actingAs($user)->post(route('student.notifications.read-all'))->assertRedirect();

    expect(app(NotificationService::class)->unreadCount($user))->toBe(0);
});

test('student can mark a single notification as read without leaving the list', function () {
    [$user] = makeNotifiableStudent();
    $notification = app(NotificationService::class)->notify(
        $user,
        NotificationCategory::SHIPMENT,
        'container.shipped',
        'Container shipped',
        'Your container is on the way.',
        url('/student/move-tracking'),
    );

    $this->actingAs($user)
        ->post(route('student.notifications.read', $notification))
        ->assertRedirect(route('student.notifications'));

    expect($notification->fresh()->read_at)->not->toBeNull()
        ->and(app(NotificationService::class)->unreadCount($user))->toBe(0);
});

test('student view action marks notification read and follows the link', function () {
    [$user] = makeNotifiableStudent();
    $notification = app(NotificationService::class)->notify(
        $user,
        NotificationCategory::SHIPMENT,
        'container.shipped',
        'Container shipped',
        'Your container is on the way.',
        url('/student/move-tracking'),
    );

    $this->actingAs($user)
        ->post(route('student.notifications.read', $notification), ['follow' => '1'])
        ->assertRedirect('/student/move-tracking');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('admin can send a custom notification and email to a student', function () {
    Mail::fake();
    [$user] = makeNotifiableStudent();
    $admin = User::factory()->create(['role' => UserRole::ADMIN, 'must_reset_password' => false]);

    $this->actingAs($admin)
        ->post(route('admin.notifications.send'), [
            'user_id' => $user->id,
            'category' => NotificationCategory::ACCOUNT,
            'title' => 'Please confirm your move-in date',
            'body' => 'We need you to confirm your move-in date by Friday.',
            'url' => 'https://new-life.test/student/profile',
        ])
        ->assertRedirect(route('admin.notifications'));

    $notification = PortalNotification::query()
        ->where('user_id', $user->id)
        ->where('type', 'admin.custom')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('Please confirm your move-in date')
        ->and($notification->category)->toBe(NotificationCategory::ACCOUNT)
        ->and($notification->created_by_user_id)->toBe($admin->id)
        ->and($notification->email_status)->toBe(PortalNotification::EMAIL_SENT);

    Mail::assertQueued(PortalNotificationMail::class);
});

test('admin custom notification validates required fields', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN, 'must_reset_password' => false]);

    $this->actingAs($admin)
        ->post(route('admin.notifications.send'), [])
        ->assertSessionHasErrors(['user_id', 'category', 'title', 'body']);
});

test('admin resend re-queues email and increments attempts', function () {
    Mail::fake();
    [$user] = makeNotifiableStudent();
    $admin = User::factory()->create(['role' => UserRole::ADMIN, 'must_reset_password' => false]);

    $notification = app(NotificationService::class)->notify($user, NotificationCategory::SYSTEM, 'a', 'Hi', 'b');
    expect($notification->email_attempts)->toBe(1);

    $this->actingAs($admin)->post(route('admin.notifications.resend', $notification))->assertRedirect();

    expect($notification->fresh()->email_attempts)->toBe(2);
    Mail::assertQueued(PortalNotificationMail::class, 2);
});

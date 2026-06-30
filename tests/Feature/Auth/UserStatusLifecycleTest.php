<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Jobs\Squarespace\ProcessSquarespaceContactWebhook;
use App\Mail\OnboardingCompleteMail;
use App\Mail\PasswordChangedMail;
use App\Mail\StudentInvitationMail;
use App\Models\HousingInfo;
use App\Models\ParentGuardian;
use App\Models\ShippingAddress;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AccountProvisioningService;
use App\Services\NewLifeIdGenerator;
use App\Services\ProfileCompletionService;
use App\Services\UserStatusService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

test('new account from contact provisioning starts as INVITED', function () {
    Mail::fake();

    $profile = app(AccountProvisioningService::class)->upsertFromContact([
        'contactId' => 'sq-contact-001',
        'firstName' => 'Newcomer',
        'lastName' => 'Student',
        'primaryEmail' => ['value' => 'newcomer@example.com'],
    ]);

    expect($profile->user->status)->toBe(UserStatus::INVITED)
        ->and($profile->user->must_reset_password)->toBeTrue();

    Mail::assertSent(StudentInvitationMail::class);
});

test('first password reset transitions INVITED to INCOMPLETE and sends confirmation email', function () {
    Mail::fake();

    $user = User::factory()->invited()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('TempPass123!'),
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
    ]);

    $this->actingAs($user)
        ->post(route('student.change-password.submit'), [
            'password' => 'BrandNew123!',
            'password_confirmation' => 'BrandNew123!',
        ])
        ->assertRedirect(route('student.profile'));

    expect($user->fresh()->status)->toBe(UserStatus::INCOMPLETE);

    Mail::assertQueued(
        PasswordChangedMail::class,
        fn (PasswordChangedMail $mail): bool => $mail->wasFirstReset === true
            && $mail->user->is($user)
    );
});

test('subsequent password change keeps status INCOMPLETE or ACTIVE and emails are flagged as not first reset', function () {
    Mail::fake();

    $user = User::factory()->incomplete()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('Existing123!'),
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
    ]);

    $this->actingAs($user)
        ->post(route('student.change-password.submit'), [
            'current_password' => 'Existing123!',
            'password' => 'AnotherOne456!',
            'password_confirmation' => 'AnotherOne456!',
        ])
        ->assertRedirect(route('student.profile'));

    expect($user->fresh()->status)->toBe(UserStatus::INCOMPLETE);

    Mail::assertQueued(
        PasswordChangedMail::class,
        fn (PasswordChangedMail $mail): bool => $mail->wasFirstReset === false
    );
});

test('completing all onboarding sections transitions status to ACTIVE and sends welcome email once', function () {
    Mail::fake();

    $user = User::factory()->incomplete()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('Existing123!'),
    ]);

    $profile = StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_step' => 4,
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
    ]);

    HousingInfo::query()->create([
        'student_profile_id' => $profile->id,
        'university' => 'ODU',
        'residence_hall' => 'Gresham',
        'move_in_date' => '2026-05-27',
    ]);

    $service = app(ProfileCompletionService::class);
    $service->syncCompletionStatus($profile->fresh(['parentGuardian', 'shippingAddress', 'housingInfo']));

    expect($user->fresh()->status)->toBe(UserStatus::ACTIVE);
    Mail::assertQueued(OnboardingCompleteMail::class);

    $service->syncCompletionStatus($profile->fresh(['parentGuardian', 'shippingAddress', 'housingInfo']));
    Mail::assertQueuedCount(1);
});

test('suspended accounts cannot log in', function () {
    $user = User::factory()->suspended()->create([
        'email' => 'suspended@example.com',
        'password' => Hash::make('AnyPass123!'),
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
    ]);

    $this->post(route('login.submit'), [
        'email' => 'suspended@example.com',
        'password' => 'AnyPass123!',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['login']);

    expect(auth()->check())->toBeFalse();
});

test('suspended account active in middleware logs the user out mid-session', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user);

    app(UserStatusService::class)->markOnboardingComplete($user);
    $user->status = UserStatus::SUSPENDED;
    $user->save();

    $this->get(route('student.profile'))
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse();
});

test('login rate limiter blocks after repeated failures', function () {
    RateLimiter::clear('login:victim@example.com|127.0.0.1');

    User::factory()->create([
        'email' => 'victim@example.com',
        'password' => Hash::make('RealPass123!'),
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login.submit'), [
            'email' => 'victim@example.com',
            'password' => 'WrongPass!',
        ]);
    }

    $this->post(route('login.submit'), [
        'email' => 'victim@example.com',
        'password' => 'RealPass123!',
    ])
        ->assertSessionHasErrors(['login']);

    expect(auth()->check())->toBeFalse();
});

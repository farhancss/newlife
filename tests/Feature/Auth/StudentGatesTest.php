<?php

use App\Enums\UserRole;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\NewLifeIdGenerator;
use Illuminate\Support\Facades\Hash;

test('student with must reset password is redirected to change password', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('TempPass123!'),
        'must_reset_password' => true,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('student.change-password'));
});

test('student with incomplete profile is redirected to profile', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('SecurePass123!'),
        'must_reset_password' => false,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'onboarding_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('student.profile'));
});

test('login redirects new student to change password then onboarding', function () {
    $user = User::factory()->create([
        'email' => 'newstudent@example.com',
        'role' => UserRole::STUDENT,
        'password' => Hash::make('TempPass123!'),
        'must_reset_password' => true,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
    ]);

    $this->post(route('login.submit'), [
        'email' => 'newstudent@example.com',
        'password' => 'TempPass123!',
    ])->assertRedirect(route('student.change-password'));
});

test('password change clears must reset and redirects to onboarding', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('TempPass123!'),
        'must_reset_password' => true,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
    ]);

    $this->actingAs($user)
        ->post(route('student.change-password.submit'), [
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])
        ->assertRedirect(route('student.profile'));

    $user->refresh();
    expect($user->must_reset_password)->toBeFalse();
});

test('profile page auto-creates profile and shows completion', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'password' => Hash::make('TempPass123!'),
        'must_reset_password' => false,
    ]);

    expect($user->studentProfile)->toBeNull();

    $this->actingAs($user)
        ->get(route('student.profile'))
        ->assertOk()
        ->assertSee('Overall progress');

    expect($user->fresh()->studentProfile)->not->toBeNull();
});

test('demo student can access dashboard', function () {
    $this->seed(\Database\Seeders\PortalUsersSeeder::class);

    $this->post(route('login.submit'), [
        'email' => 'student@demo.com',
        'password' => 'Admin@123',
    ])->assertRedirect(route('student.dashboard'));
});

<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

function incompleteStudent(): User
{
    return User::factory()->create([
        'role' => UserRole::STUDENT,
        'status' => UserStatus::INCOMPLETE,
        'must_reset_password' => false,
    ]);
}

it('redirects an incomplete student from onboarding to the profile', function () {
    $user = incompleteStudent();

    $this->actingAs($user)
        ->get(route('student.onboarding'))
        ->assertRedirect(route('student.profile'));
});

it('redirects a completed student from onboarding to the dashboard', function () {
    [$user] = completeStudent();

    $this->actingAs($user)
        ->get(route('student.onboarding'))
        ->assertRedirect(route('student.dashboard'));
});

it('validates the first onboarding step', function () {
    $user = incompleteStudent();

    $this->actingAs($user)
        ->post(route('student.onboarding.submit'), ['step' => 1, 'action' => 'next'])
        ->assertSessionHasErrors(['first_name', 'last_name', 'phone', 'school', 'incoming_year']);
});

it('walks a student through all onboarding steps to completion', function () {
    Mail::fake();
    $user = incompleteStudent();

    $this->actingAs($user)->post(route('student.onboarding.submit'), [
        'step' => 1,
        'first_name' => 'Avery',
        'last_name' => 'Newcomer',
        'phone' => '757-555-0190',
        'school' => 'ODU',
        'incoming_year' => '2026',
    ])->assertRedirect(route('student.profile'));

    $this->actingAs($user)->post(route('student.onboarding.submit'), [
        'step' => 2,
        'parent_name' => 'Pat Newcomer',
        'parent_email' => 'pat@example.com',
        'parent_phone' => '757-555-0191',
        'parent_relationship' => 'Father',
    ])->assertRedirect(route('student.profile'));

    $this->actingAs($user)->post(route('student.onboarding.submit'), [
        'step' => 3,
        'line1' => '200 Campus Way',
        'city' => 'Norfolk',
        'region' => 'VA',
        'postal_code' => '23529',
        'country_code' => 'US',
    ])->assertRedirect(route('student.profile'));

    $this->actingAs($user)->post(route('student.onboarding.submit'), [
        'step' => 4,
        'university' => 'ODU',
        'residence_hall' => 'Whitehurst',
        'move_in_date' => now()->addMonth()->toDateString(),
    ])
        ->assertRedirect(route('student.dashboard'))
        ->assertSessionHas('status');

    $profile = StudentProfile::query()->where('user_id', $user->id)->first();
    expect($profile->isOnboardingComplete())->toBeTrue();
});

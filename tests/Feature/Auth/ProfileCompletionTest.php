<?php

use App\Enums\UserRole;
use App\Models\HousingInfo;
use App\Models\ParentGuardian;
use App\Models\ShippingAddress;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\NewLifeIdGenerator;
use App\Services\ProfileCompletionService;
use Illuminate\Support\Facades\Hash;

test('profile completion percent reflects filled sections', function () {
    $user = User::factory()->create(['role' => UserRole::STUDENT]);

    $profile = StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
    ]);

    $summary = app(ProfileCompletionService::class)->summary($profile);

    expect($summary['percent'])->toBeGreaterThan(0)
        ->and($summary['percent'])->toBeLessThan(100)
        ->and($summary['is_complete'])->toBeFalse();
});

test('profile completion reaches one hundred percent when all sections filled', function () {
    $user = User::factory()->create(['role' => UserRole::STUDENT]);

    $profile = StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
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

    $summary = app(ProfileCompletionService::class)->summary($profile->fresh());

    expect($summary['percent'])->toBe(100)
        ->and($summary['is_complete'])->toBeTrue();
});

test('incomplete student can access profile route', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'must_reset_password' => false,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
    ]);

    $this->actingAs($user)
        ->get(route('student.profile'))
        ->assertOk()
        ->assertSee('Sections to complete', false);
});

test('profile respects section query param for direct navigation', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'must_reset_password' => false,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jane',
        'onboarding_step' => 1,
    ]);

    $this->actingAs($user)
        ->get(route('student.profile', ['section' => 4]))
        ->assertOk()
        ->assertSee('University Dorm', false)
        ->assertSee('Move-in Date', false);
});

test('profile hides progress steps when all sections are complete', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
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
        'onboarding_completed_at' => now(),
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

    $this->actingAs($user)
        ->get(route('student.profile'))
        ->assertOk()
        ->assertDontSee('Sections to complete', false)
        ->assertDontSee('Overall progress', false)
        ->assertSee('Student Information', false)
        ->assertSee('University Dorm', false);
});

test('profile next without changes advances without success message', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'must_reset_password' => false,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_step' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('student.profile.update'), [
            'action' => 'next',
            'section' => 1,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone' => '757-555-0100',
            'school' => 'ODU',
            'incoming_year' => '2026',
        ])
        ->assertRedirect(route('student.profile', ['section' => 2]))
        ->assertSessionMissing('status');

    expect(StudentProfile::query()->where('user_id', $user->id)->value('onboarding_step'))->toBe(2);
});

test('profile next with changes saves and shows success message', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'must_reset_password' => false,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_step' => 1,
    ]);

    $this->actingAs($user)
        ->post(route('student.profile.update'), [
            'action' => 'next',
            'section' => 1,
            'first_name' => 'Janet',
            'last_name' => 'Doe',
            'phone' => '757-555-0100',
            'school' => 'ODU',
            'incoming_year' => '2026',
        ])
        ->assertRedirect(route('student.profile', ['section' => 2]))
        ->assertSessionHas('status', 'Your changes have been saved.');

    expect(StudentProfile::query()->where('user_id', $user->id)->value('first_name'))->toBe('Janet');
});

test('profile save on complete section four stays on profile with success message', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
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
        'onboarding_step' => 4,
        'onboarding_completed_at' => now(),
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

    $this->actingAs($user)
        ->post(route('student.profile.update'), [
            'action' => 'save',
            'section' => 4,
            'university' => 'ODU',
            'residence_hall' => 'Gresham East',
            'move_in_date' => '2026-05-27',
        ])
        ->assertRedirect(route('student.profile', ['section' => 4]))
        ->assertSessionHas('status', 'Profile updated successfully.');

    expect(HousingInfo::query()->where('student_profile_id', $profile->id)->value('residence_hall'))
        ->toBe('Gresham East');
});

test('student information step requires mandatory fields', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'must_reset_password' => false,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'onboarding_step' => 1,
    ]);

    $this->actingAs($user)
        ->from(route('student.profile', ['section' => 1]))
        ->post(route('student.profile.update'), [
            'action' => 'next',
            'section' => 1,
            'first_name' => '',
            'last_name' => 'Doe',
            'phone' => '757-555-0100',
            'school' => 'ODU',
            'incoming_year' => '2026',
        ])
        ->assertRedirect(route('student.profile', ['section' => 1]))
        ->assertSessionHasErrors(['first_name']);
});

test('home address step requires mandatory fields', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
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
        'onboarding_step' => 3,
    ]);

    ParentGuardian::query()->create([
        'student_profile_id' => $profile->id,
        'name' => 'Parent',
        'email' => 'parent@example.com',
        'phone' => '757-555-0101',
        'relationship' => 'Mother',
    ]);

    $this->actingAs($user)
        ->from(route('student.profile', ['section' => 3]))
        ->post(route('student.profile.update'), [
            'action' => 'next',
            'section' => 3,
            'line1' => '100 Main St',
            'line2' => '',
            'city' => '',
            'region' => 'VA',
            'postal_code' => '23510',
            'country_code' => 'US',
            'shipping_notes' => '',
        ])
        ->assertRedirect(route('student.profile', ['section' => 3]))
        ->assertSessionHasErrors(['city']);
});

test('header shows profile completion badge for incomplete student', function () {
    $user = User::factory()->create([
        'role' => UserRole::STUDENT,
        'must_reset_password' => false,
    ]);

    StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jane',
    ]);

    $this->actingAs($user)
        ->get(route('student.profile'))
        ->assertOk()
        ->assertSee('Profile', false);
});

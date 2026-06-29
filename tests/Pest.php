<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a student whose profile passes the onboarding-complete gate, so
 * authenticated student routes are reachable in feature tests.
 *
 * @param  array<string, mixed>  $userOverrides
 * @return array{0: \App\Models\User, 1: \App\Models\StudentProfile}
 */
function completeStudent(array $userOverrides = []): array
{
    $user = \App\Models\User::factory()->create(array_merge([
        'role' => \App\Enums\UserRole::STUDENT,
        'status' => \App\Enums\UserStatus::ACTIVE,
        'password' => \Illuminate\Support\Facades\Hash::make('SecurePass123!'),
        'must_reset_password' => false,
    ], $userOverrides));

    $profile = \App\Models\StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Test',
        'last_name' => 'Student',
        'phone' => '757-555-0100',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_completed_at' => now(),
    ]);

    \App\Models\ParentGuardian::query()->create([
        'student_profile_id' => $profile->id,
        'name' => 'Parent Guardian',
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

function makeAdmin(): \App\Models\User
{
    return \App\Models\User::factory()->create([
        'role' => \App\Enums\UserRole::ADMIN,
        'status' => \App\Enums\UserStatus::ACTIVE,
        'must_reset_password' => false,
    ]);
}

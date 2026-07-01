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

/**
 * Mirrors a real Squarespace order payload for provisioning tests.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function realOrderPayload(array $overrides = []): array
{
    return array_merge([
        'id' => '6a429283f484262820b6a327',
        'orderNumber' => '520',
        'createdOn' => '2026-06-29T15:42:59.273Z',
        'customerEmail' => 'test@yopmail.com',
        'customerId' => '6a42928246c96a756367575e',
        'billingAddress' => [
            'firstName' => 'Farhan',
            'lastName' => 'ahmed',
            'address1' => '302 Roma Ct',
            'address2' => null,
            'city' => 'ALLEN',
            'state' => 'TX',
            'countryCode' => 'US',
            'postalCode' => '75013',
            'phone' => '+11231231234',
        ],
        'shippingAddress' => null,
        'fulfillmentStatus' => 'PENDING',
        'lineItems' => [[
            'id' => '6a4291bd528e130bbde37bb0',
            'sku' => 'SQ7020693',
            'productId' => '6a42558c21dbe14148c1770c',
            'productName' => 'Basic Package',
            'quantity' => 1,
            'unitPricePaid' => ['currency' => 'USD', 'value' => '0.00'],
            'customizations' => [
                ['label' => 'Name', 'value' => 'Farhan Ahmed'],
                ['label' => 'Email', 'value' => 'test@yopmail.com'],
                ['label' => 'Phone', 'value' => '(123) 123-123'],
                ['label' => 'Address', 'value' => '302 roma ct, Allen, Texas 75031 US'],
                ['label' => 'Message', 'value' => 'Test'],
            ],
        ]],
        'formSubmission' => [
            ['label' => 'Student Full Name', 'value' => 'Farhan Ahmed'],
            ['label' => 'Student Phone Number', 'value' => ' (123) 123-1234'],
            ['label' => 'Student Email', 'value' => 'test@yopmail.com'],
            ['label' => 'University', 'value' => 'Florida A&M University'],
            ['label' => 'Residence Hall Assigned?', 'value' => 'No'],
            ['label' => 'If Yes, which FAMU Housing?', 'value' => '• FAMU Towers South'],
            ['label' => 'Move-In Classification', 'value' => '• Incoming Freshman'],
            ['label' => 'Parent or Guardian Full Name', 'value' => 'Farhan new'],
            ['label' => 'Parent or Guardian Phone Number', 'value' => ' (123) 123-1234'],
            ['label' => 'Parent or Guardian Email', 'value' => 'parent@yopmail.com'],
            ['label' => 'I agree to the New Life Campus Service Terms and Move-In Policies.', 'value' => 'Yes'],
            ['label' => 'I understand that the New Life Campus Customer Portal opens July 1, 2026, and I will receive instructions by email.', 'value' => 'Yes'],
        ],
        'subtotal' => ['currency' => 'USD', 'value' => '0.00'],
        'grandTotal' => ['currency' => 'USD', 'value' => '0.00'],
    ], $overrides);
}

/**
 * Student with parent/shipping/housing for notification tests.
 *
 * @param  array<string, mixed>  $userOverrides
 * @return array{0: \App\Models\User, 1: \App\Models\StudentProfile}
 */
function makeNotifiableStudent(array $userOverrides = []): array
{
    $user = \App\Models\User::factory()->create(array_merge([
        'role' => \App\Enums\UserRole::STUDENT,
        'password' => \Illuminate\Support\Facades\Hash::make('SecurePass123!'),
        'must_reset_password' => false,
    ], $userOverrides));

    $profile = \App\Models\StudentProfile::query()->create([
        'user_id' => $user->id,
        'new_life_id' => app(\App\Services\NewLifeIdGenerator::class)->generate(),
        'first_name' => 'Jordan',
        'last_name' => 'Lee',
        'phone' => '757-555-0142',
        'school' => 'ODU',
        'incoming_year' => '2026',
        'onboarding_completed_at' => now(),
    ]);

    \App\Models\ParentGuardian::query()->create([
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

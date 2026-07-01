<?php

use App\Enums\AddOnStatus;
use App\Mail\StudentInvitationMail;
use App\Models\StudentProfile;
use App\Models\StudentSubscription;
use App\Models\User;
use App\Services\AccountProvisioningService;
use App\Services\Squarespace\PackageTierMapper;
use App\Services\Squarespace\SquarespaceOrderMapper;
use Illuminate\Support\Facades\Mail;

it('maps a real order into normalized onboarding data', function () {
    $mapped = app(SquarespaceOrderMapper::class)->map(realOrderPayload());

    expect($mapped['email'])->toBe('test@yopmail.com')
        ->and($mapped['contact_id'])->toBe('6a42928246c96a756367575e')
        ->and($mapped['tier'])->toBe('essential');

    expect($mapped['student'])->toMatchArray([
        'first_name' => 'Farhan',
        'last_name' => 'ahmed',
        'phone' => '(123) 123-1234',
        'school' => 'Florida A&M University',
        'incoming_year' => '2026',
    ]);

    expect($mapped['parent'])->toMatchArray([
        'name' => 'Farhan new',
        'email' => 'parent@yopmail.com',
        'phone' => '(123) 123-1234',
        'relationship' => 'Parent/Guardian',
    ]);

    expect($mapped['home_address'])->toMatchArray([
        'line1' => '302 Roma Ct',
        'city' => 'ALLEN',
        'region' => 'TX',
        'postal_code' => '75013',
        'country_code' => 'US',
    ]);

    expect($mapped['housing'])->toMatchArray([
        'university' => 'Florida A&M University',
        'residence_hall' => 'FAMU Towers South',
        'move_in_classification' => 'Incoming Freshman',
    ]);

    expect($mapped['agreements'])->toHaveCount(2);
});

it('provisions a brand-new student from a single order and emails the invite', function () {
    Mail::fake();

    $account = app(AccountProvisioningService::class)->provisionFromOrder(realOrderPayload());

    expect($account->isNewUser)->toBeTrue()
        ->and($account->temporaryPassword)->not->toBeNull()
        ->and($account->invitationSent)->toBeTrue();

    $user = User::query()->where('email', 'test@yopmail.com')->firstOrFail();
    expect($user->status)->toBe(\App\Enums\UserStatus::INVITED)
        ->and($user->must_reset_password)->toBeTrue()
        ->and($user->squarespace_contact_id)->toBe('6a42928246c96a756367575e');

    Mail::assertSent(StudentInvitationMail::class);
    Mail::assertNothingQueued();

    $profile = $user->studentProfile;
    expect($profile->first_name)->toBe('Farhan')
        ->and($profile->last_name)->toBe('ahmed')
        ->and($profile->phone)->toBe('(123) 123-1234')
        ->and($profile->school)->toBe('Florida A&M University')
        ->and($profile->incoming_year)->toBe('2026')
        ->and($profile->package_tier)->toBe('essential')
        ->and($profile->package_id)->not->toBeNull()
        ->and($profile->isOnboardingComplete())->toBeFalse();

    expect($profile->parentGuardian->name)->toBe('Farhan new')
        ->and($profile->parentGuardian->email)->toBe('parent@yopmail.com');

    expect($profile->shippingAddress->line1)->toBe('302 Roma Ct')
        ->and($profile->shippingAddress->city)->toBe('ALLEN')
        ->and($profile->shippingAddress->postal_code)->toBe('75013');

    expect($profile->housingInfo->university)->toBe('Florida A&M University')
        ->and($profile->housingInfo->residence_hall)->toBe('FAMU Towers South');

    expect(StudentSubscription::query()->where('student_profile_id', $profile->id)->exists())->toBeTrue();
    expect($profile->squarespaceOrders()->where('squarespace_order_id', '6a429283f484262820b6a327')->exists())->toBeTrue();
});

it('does not re-send the invite or clobber edits for an existing student', function () {
    Mail::fake();

    app(AccountProvisioningService::class)->provisionFromOrder(realOrderPayload());
    Mail::assertSent(StudentInvitationMail::class, 1);

    // Student edits their phone during onboarding.
    $profile = StudentProfile::query()->firstOrFail();
    $profile->update(['phone' => '999-999-9999']);

    Mail::fake();
    $account = app(AccountProvisioningService::class)->provisionFromOrder(realOrderPayload());

    expect($account->isNewUser)->toBeFalse()
        ->and($account->temporaryPassword)->toBeNull();
    Mail::assertNothingQueued();

    expect($profile->fresh()->phone)->toBe('999-999-9999');
    expect(User::query()->where('email', 'test@yopmail.com')->count())->toBe(1);
});

it('matches the package tier from the product name (exact and partial)', function () {
    $mapper = app(PackageTierMapper::class);

    expect($mapper->mapFromLineItems([['productName' => 'Summit Package']]))->toBe('summit')
        ->and($mapper->mapFromLineItems([['productName' => 'Summit Move 2026 Special']]))->toBe('summit')
        ->and($mapper->mapFromLineItems([['productName' => 'Legacy']]))->toBe('legacy')
        ->and($mapper->mapFromLineItems([['productName' => 'Essential Package']]))->toBe('essential')
        ->and($mapper->mapFromLineItems([['productName' => 'Basic Package']]))->toBe('essential')
        ->and($mapper->mapFromLineItems([['productName' => 'Random Gift Card']]))->toBe('unknown');
});

it('stores the order grand total as the package price', function () {
    Mail::fake();

    app(AccountProvisioningService::class)->provisionFromOrder(realOrderPayload([
        'grandTotal' => ['currency' => 'USD', 'value' => '1350.00'],
    ]));

    $profile = StudentProfile::query()->firstOrFail();

    expect($profile->package_price_cents)->toBe(135000);
});

it('activates an add-on for an existing student from an add-on-only order', function () {
    Mail::fake();

    // Initial package purchase.
    app(AccountProvisioningService::class)->provisionFromOrder(realOrderPayload());
    $profile = StudentProfile::query()->firstOrFail();

    // A later add-on order (same customer email) on a different order id.
    app(AccountProvisioningService::class)->provisionFromOrder(realOrderPayload([
        'id' => 'addon-order-001',
        'orderNumber' => '521',
        'lineItems' => [[
            'id' => 'li-addon-1',
            'sku' => 'SQ-ADDON-1',
            'productName' => 'Protection Coverage',
            'quantity' => 1,
            'unitPricePaid' => ['currency' => 'USD', 'value' => '95.00'],
        ]],
    ]));

    $profile->refresh();

    // Package is preserved (the add-on-only order must not wipe it).
    expect($profile->package_tier)->toBe('essential');

    $addOn = $profile->addOns()->where('add_on_slug', 'protection-coverage')->first();
    expect($addOn)->not->toBeNull()
        ->and($addOn->status)->toBe(AddOnStatus::ACTIVE)
        ->and($addOn->squarespace_order_id)->toBe('addon-order-001');
});

it('provisions an additional-container add-on with a trackable container', function () {
    Mail::fake();

    app(AccountProvisioningService::class)->provisionFromOrder(realOrderPayload());
    $profile = StudentProfile::query()->firstOrFail();

    app(AccountProvisioningService::class)->provisionFromOrder(realOrderPayload([
        'id' => 'addon-order-002',
        'orderNumber' => '522',
        'lineItems' => [[
            'id' => 'li-addon-2',
            'productName' => 'Additional Container',
            'quantity' => 1,
            'unitPricePaid' => ['currency' => 'USD', 'value' => '175.00'],
        ]],
    ]));

    $addOn = $profile->addOns()->where('add_on_slug', 'additional-container')->first();
    expect($addOn)->not->toBeNull()
        ->and($addOn->container_id)->not->toBeNull();
});

<?php

use App\Support\PasswordPolicy;
use Illuminate\Validation\Rules\Password;

it('builds a strong password rule', function () {
    expect(PasswordPolicy::rule())->toBeInstanceOf(Password::class);
});

it('exposes the full requirement checklist', function () {
    $requirements = PasswordPolicy::requirements();

    expect($requirements)
        ->toHaveKeys(['minLength', 'uppercase', 'lowercase', 'number', 'symbol'])
        ->and($requirements['minLength'])->toContain('8 characters');
});

it('exposes a compact requirement checklist for auth screens', function () {
    $requirements = PasswordPolicy::authRequirements();

    expect($requirements)
        ->toHaveKeys(['minLength', 'number', 'uppercase', 'symbol'])
        ->and($requirements)->not->toHaveKey('lowercase');
});

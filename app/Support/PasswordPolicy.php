<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class PasswordPolicy
{
    public static function rule(): Password
    {
        return Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }

    /**
     * Requirement keys and labels — kept in sync with client-side checks in
     * `resources/views/components/form/password-strength.blade.php`.
     *
     * @return array<string, string>
     */
    public static function requirements(): array
    {
        return [
            'minLength' => 'At least 8 characters',
            'uppercase' => 'At least one uppercase letter (A–Z)',
            'lowercase' => 'At least one lowercase letter (a–z)',
            'number' => 'At least one number (0–9)',
            'symbol' => 'At least one special character (!@#$…)',
        ];
    }

    /**
     * Compact labels for auth screens (reset password, etc.) — display only.
     * Client-side submit still enforces the full policy in password-strength.
     *
     * @return array<string, string>
     */
    public static function authRequirements(): array
    {
        return [
            'minLength' => 'At least use 8 characters',
            'number' => 'Must contain min 1 number',
            'uppercase' => 'At least 1 uppercase letter',
            'symbol' => 'Must contain min 1 symbol',
        ];
    }
}

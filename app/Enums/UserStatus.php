<?php

namespace App\Enums;

final class UserStatus
{
    public const INVITED = 'invited';
    public const INCOMPLETE = 'incomplete';
    public const ACTIVE = 'active';
    public const SUSPENDED = 'suspended';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::INVITED,
            self::INCOMPLETE,
            self::ACTIVE,
            self::SUSPENDED,
        ];
    }

    public static function isValid(?string $status): bool
    {
        return $status !== null && in_array($status, self::values(), true);
    }
}

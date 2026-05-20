<?php

namespace App\Enums;

final class PackageTier
{
    public const BASIC = 'basic';
    public const STANDARD = 'standard';
    public const PREMIUM = 'premium';
    public const UNKNOWN = 'unknown';

    public static function values(): array
    {
        return [
            self::BASIC,
            self::STANDARD,
            self::PREMIUM,
            self::UNKNOWN,
        ];
    }
}

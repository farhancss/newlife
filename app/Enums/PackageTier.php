<?php

namespace App\Enums;

final class PackageTier
{
    public const ESSENTIAL = 'essential';
    public const SUMMIT = 'summit';
    public const LEGACY = 'legacy';
    public const UNKNOWN = 'unknown';

    /** @deprecated Use ESSENTIAL */
    public const BASIC = self::ESSENTIAL;

    /** @deprecated Use SUMMIT */
    public const STANDARD = self::SUMMIT;

    /** @deprecated Use LEGACY */
    public const PREMIUM = self::LEGACY;

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::ESSENTIAL,
            self::SUMMIT,
            self::LEGACY,
            self::UNKNOWN,
        ];
    }

    public static function normalize(?string $tier): string
    {
        return match ($tier) {
            'basic', self::ESSENTIAL => self::ESSENTIAL,
            'standard', self::SUMMIT => self::SUMMIT,
            'premium', self::LEGACY => self::LEGACY,
            self::LEGACY, self::SUMMIT, self::ESSENTIAL => $tier,
            default => self::UNKNOWN,
        };
    }
}

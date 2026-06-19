<?php

namespace App\Enums;

final class AddOnStatus
{
    /** Add-on is purchased and active on the student's account. */
    public const ACTIVE = 'active';

    /** Add-on was cancelled. */
    public const CANCELLED = 'cancelled';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::CANCELLED,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::ACTIVE => 'Active',
            self::CANCELLED => 'Cancelled',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    public static function isValid(?string $status): bool
    {
        return $status !== null && in_array($status, self::all(), true);
    }
}

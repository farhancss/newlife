<?php

namespace App\Enums;

final class NotificationCategory
{
    public const ACCOUNT = 'account';
    public const SHIPMENT = 'shipment';
    public const RETAIL = 'retail';
    public const DEADLINE = 'deadline';
    public const SYSTEM = 'system';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::ACCOUNT,
            self::SHIPMENT,
            self::RETAIL,
            self::DEADLINE,
            self::SYSTEM,
        ];
    }

    public static function label(string $category): string
    {
        return match ($category) {
            self::ACCOUNT => 'Account',
            self::SHIPMENT => 'Shipment',
            self::RETAIL => 'Retail',
            self::DEADLINE => 'Deadline',
            self::SYSTEM => 'System',
            default => ucfirst($category),
        };
    }

    /**
     * Categories that, by scope, copy the parent/guardian on key updates.
     *
     * @return list<string>
     */
    public static function parentCcCategories(): array
    {
        return [self::SHIPMENT, self::RETAIL];
    }
}

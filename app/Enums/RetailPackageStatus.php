<?php

namespace App\Enums;

final class RetailPackageStatus
{
    public const LOGGED = 'logged';
    public const IN_TRANSIT = 'in_transit';
    public const RECEIVED_AT_HUB = 'received_at_hub';
    public const STAGED_FOR_DELIVERY = 'staged_for_delivery';
    public const DELIVERED_TO_DORM = 'delivered_to_dorm';

    /**
     * @return list<string>
     */
    public static function ordered(): array
    {
        return [
            self::LOGGED,
            self::IN_TRANSIT,
            self::RECEIVED_AT_HUB,
            self::STAGED_FOR_DELIVERY,
            self::DELIVERED_TO_DORM,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::LOGGED => 'Logged',
            self::IN_TRANSIT => 'In Transit',
            self::RECEIVED_AT_HUB => 'Received at Hub',
            self::STAGED_FOR_DELIVERY => 'Staged for Delivery',
            self::DELIVERED_TO_DORM => 'Delivered to Dorm',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    public static function isValid(?string $status): bool
    {
        return $status !== null && in_array($status, self::ordered(), true);
    }

    public static function orderIndex(string $status): int
    {
        $index = array_search($status, self::ordered(), true);

        return $index === false ? 0 : (int) $index;
    }
}

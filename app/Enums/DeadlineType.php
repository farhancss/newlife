<?php

namespace App\Enums;

final class DeadlineType
{
    public const PROFILE_COMPLETION = 'profile_completion';
    public const CONTAINER_PICKUP = 'container_pickup';
    public const RETAIL_ARRIVAL = 'retail_arrival';
    public const ADDON_CONTAINER_PICKUP = 'addon_container_pickup';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::PROFILE_COMPLETION,
            self::CONTAINER_PICKUP,
            self::RETAIL_ARRIVAL,
            self::ADDON_CONTAINER_PICKUP,
        ];
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::PROFILE_COMPLETION => 'Profile Completion',
            self::CONTAINER_PICKUP => 'Container Pickup',
            self::RETAIL_ARRIVAL => 'Retail Package Arrival',
            self::ADDON_CONTAINER_PICKUP => 'Add-on Container Pickup',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }
}

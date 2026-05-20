<?php

namespace App\Enums;

final class SubscriptionStatus
{
    public const ACTIVE = 'active';
    public const PAST_DUE = 'past_due';
    public const CANCELLED = 'cancelled';
    public const COMPLETED = 'completed';

    public static function values(): array
    {
        return [
            self::ACTIVE,
            self::PAST_DUE,
            self::CANCELLED,
            self::COMPLETED,
        ];
    }
}

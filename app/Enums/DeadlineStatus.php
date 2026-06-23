<?php

namespace App\Enums;

final class DeadlineStatus
{
    public const UPCOMING = 'upcoming';
    public const COMPLETED = 'completed';
    public const OVERDUE = 'overdue';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::UPCOMING,
            self::COMPLETED,
            self::OVERDUE,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::UPCOMING => 'Upcoming',
            self::COMPLETED => 'Completed',
            self::OVERDUE => 'Overdue',
            default => ucfirst($status),
        };
    }

    /**
     * UI tone used to highlight each stage (maps to the alert/badge variants).
     */
    public static function tone(string $status): string
    {
        return match ($status) {
            self::COMPLETED => 'success',
            self::OVERDUE => 'warning',
            default => 'info',
        };
    }

    public static function isValid(?string $status): bool
    {
        return $status !== null && in_array($status, self::all(), true);
    }
}

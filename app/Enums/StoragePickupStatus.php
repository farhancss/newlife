<?php

namespace App\Enums;

/**
 * Lifecycle of an end-of-year storage pickup & return. This is the "next
 * journey" that begins once a student's move container has been delivered to
 * the dorm (the 12th and final move status). It is intentionally tracked
 * separately from the 12-stage move {@see ContainerStatus} so the move
 * timeline ("Step X of 12") stays intact.
 */
final class StoragePickupStatus
{
    public const REQUESTED = 'requested';
    public const SCHEDULED = 'scheduled';
    public const PICKED_UP = 'picked_up';
    public const IN_STORAGE = 'in_storage';
    public const OUT_FOR_RETURN = 'out_for_return';
    public const RETURNED = 'returned';
    public const CANCELLED = 'cancelled';

    /**
     * Ordered "happy path" of the storage & return journey (excludes the
     * terminal CANCELLED state).
     *
     * @return list<string>
     */
    public static function ordered(): array
    {
        return [
            self::REQUESTED,
            self::SCHEDULED,
            self::PICKED_UP,
            self::IN_STORAGE,
            self::OUT_FOR_RETURN,
            self::RETURNED,
        ];
    }

    /**
     * Every valid status, including CANCELLED.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return [...self::ordered(), self::CANCELLED];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::REQUESTED => 'Pickup Requested',
            self::SCHEDULED => 'Pickup Scheduled',
            self::PICKED_UP => 'Picked Up from Dorm',
            self::IN_STORAGE => 'In Storage',
            self::OUT_FOR_RETURN => 'Out for Return Delivery',
            self::RETURNED => 'Returned for New Cycle',
            self::CANCELLED => 'Cancelled',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }

    public static function isValid(?string $status): bool
    {
        return $status !== null && in_array($status, self::all(), true);
    }

    public static function orderIndex(string $status): int
    {
        $index = array_search($status, self::ordered(), true);

        return $index === false ? 0 : (int) $index;
    }

    public static function isActive(string $status): bool
    {
        return ! in_array($status, [self::RETURNED, self::CANCELLED], true);
    }
}

<?php

namespace App\Enums;

final class SquarespaceLogDirection
{
    /** A webhook notification Squarespace sent to us. */
    public const INCOMING = 'incoming';

    /** An API call we made to Squarespace. */
    public const OUTGOING = 'outgoing';

    /** The outcome of processing an incoming webhook (e.g. provisioning a student). */
    public const PROCESSING = 'processing';

    public static function label(string $direction): string
    {
        return match ($direction) {
            self::INCOMING => 'Incoming (webhook)',
            self::OUTGOING => 'Outgoing (API call)',
            self::PROCESSING => 'Processing (result)',
            default => ucfirst($direction),
        };
    }
}

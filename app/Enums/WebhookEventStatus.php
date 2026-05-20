<?php

namespace App\Enums;

final class WebhookEventStatus
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const PROCESSED = 'processed';
    public const FAILED = 'failed';

    public static function values(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::PROCESSED,
            self::FAILED,
        ];
    }
}

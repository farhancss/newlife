<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $status
 * @property string $notification_id
 * @property string $topic
 * @property array<string, mixed> $payload
 */
class SquarespaceWebhookEvent extends Model
{
    protected $fillable = [
        'notification_id',
        'topic',
        'website_id',
        'payload',
        'status',
        'processed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}

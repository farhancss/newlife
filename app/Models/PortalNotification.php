<?php

namespace App\Models;

use App\Enums\NotificationCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * In-app notification record. Doubles as the student inbox, the admin delivery
 * log, and the audit trail for email sends.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $created_by_user_id
 * @property string $category
 * @property string $type
 * @property string $title
 * @property string|null $body
 * @property string|null $url
 * @property string $email_status
 * @property int $email_attempts
 * @property \Illuminate\Support\Carbon|null $emailed_at
 * @property \Illuminate\Support\Carbon|null $read_at
 * @property array<string, mixed>|null $meta
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read User|null $createdBy
 */
class PortalNotification extends Model
{
    public const EMAIL_NONE = 'none';
    public const EMAIL_QUEUED = 'queued';
    public const EMAIL_SENT = 'sent';
    public const EMAIL_FAILED = 'failed';
    public const EMAIL_SKIPPED = 'skipped';

    protected $fillable = [
        'user_id',
        'created_by_user_id',
        'category',
        'type',
        'title',
        'body',
        'url',
        'email_status',
        'email_attempts',
        'emailed_at',
        'read_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'emailed_at' => 'datetime',
            'read_at' => 'datetime',
            'email_attempts' => 'integer',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function categoryLabel(): string
    {
        return NotificationCategory::label($this->category);
    }

    /**
     * @param  Builder<PortalNotification>  $query
     * @return Builder<PortalNotification>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}

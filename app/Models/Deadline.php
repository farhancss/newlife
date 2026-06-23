<?php

namespace App\Models;

use App\Enums\DeadlineStatus;
use App\Enums\DeadlineType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_profile_id
 * @property string|null $deadlinable_type
 * @property int|null $deadlinable_id
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property Carbon $due_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $reminder_sent_at
 * @property Carbon|null $completed_notified_at
 * @property Carbon|null $overdue_notified_at
 * @property array<string, mixed>|null $meta
 */
class Deadline extends Model
{
    protected $fillable = [
        'student_profile_id',
        'deadlinable_type',
        'deadlinable_id',
        'type',
        'title',
        'description',
        'status',
        'due_at',
        'completed_at',
        'reminder_sent_at',
        'completed_notified_at',
        'overdue_notified_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'completed_notified_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return MorphTo<Model, $this> */
    public function deadlinable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isCompleted(): bool
    {
        return $this->status === DeadlineStatus::COMPLETED;
    }

    /**
     * The status to show right now. A still-open deadline whose date has passed
     * reads as overdue immediately, without waiting for the daily sweep to flip
     * the stored column.
     */
    public function effectiveStatus(): string
    {
        if ($this->isCompleted()) {
            return DeadlineStatus::COMPLETED;
        }

        if ($this->status === DeadlineStatus::OVERDUE || $this->due_at->isPast()) {
            return DeadlineStatus::OVERDUE;
        }

        return DeadlineStatus::UPCOMING;
    }

    public function isUpcoming(): bool
    {
        return $this->effectiveStatus() === DeadlineStatus::UPCOMING;
    }

    public function isOverdue(): bool
    {
        return $this->effectiveStatus() === DeadlineStatus::OVERDUE;
    }

    public function statusLabel(): string
    {
        return DeadlineStatus::label($this->effectiveStatus());
    }

    public function typeLabel(): string
    {
        return DeadlineType::label($this->type);
    }

    public function tone(): string
    {
        return DeadlineStatus::tone($this->effectiveStatus());
    }

    /**
     * Whole days from now until the due date. Negative once the date has passed.
     */
    public function daysRemaining(): int
    {
        return (int) round(now()->startOfDay()->diffInDays($this->due_at->copy()->startOfDay(), false));
    }
}

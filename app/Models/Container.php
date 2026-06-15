<?php

namespace App\Models;

use App\Enums\ContainerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $student_profile_id
 * @property string $code
 * @property string $status
 * @property string|null $location
 * @property string|null $outbound_tracking
 * @property string|null $return_tracking
 * @property \Illuminate\Support\Carbon|null $ship_by_date
 * @property string|null $internal_notes
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read StudentProfile $studentProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContainerStatusHistory> $statusHistories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ContainerPhoto> $photos
 */
class Container extends Model
{
    protected $fillable = [
        'student_profile_id',
        'code',
        'status',
        'location',
        'outbound_tracking',
        'return_tracking',
        'ship_by_date',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'ship_by_date' => 'date',
        ];
    }

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return HasMany<ContainerStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ContainerStatusHistory::class)->orderByDesc('created_at');
    }

    /** @return HasMany<ContainerPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(ContainerPhoto::class)->orderBy('created_at');
    }

    public function statusLabel(): string
    {
        return ContainerStatus::label($this->status);
    }

    /**
     * Timestamp the container first reached the given workflow status, derived
     * from the status-history audit trail.
     */
    public function reachedAt(string $status): ?\Illuminate\Support\Carbon
    {
        return $this->statusHistories
            ->where('to_status', $status)
            ->sortBy('created_at')
            ->first()?->created_at;
    }

    public function shippedAt(): ?\Illuminate\Support\Carbon
    {
        return $this->reachedAt(ContainerStatus::SHIPPED_TO_HOME);
    }

    public function deliveredHomeAt(): ?\Illuminate\Support\Carbon
    {
        return $this->reachedAt(ContainerStatus::DELIVERED_TO_HOME);
    }

    /**
     * Students may only upload (or delete) exterior photos while the container
     * is being packed at home. Existing photos remain viewable at every stage.
     */
    public function acceptsPhotos(): bool
    {
        return $this->status === ContainerStatus::CUSTOMER_PACKING;
    }

    public function photoCap(): int
    {
        return (int) config('portal.container_photos.max_per_container', 5);
    }

    public function remainingPhotoSlots(): int
    {
        return max(0, $this->photoCap() - $this->photos()->count());
    }
}

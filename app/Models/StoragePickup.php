<?php

namespace App\Models;

use App\Enums\StoragePickupStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An end-of-year dorm pickup request. Captures the date and details a student
 * submits so the New Life team can collect their containers from the dorm and
 * move them into summer storage, then re-deliver at the next academic cycle.
 *
 * @property int $id
 * @property int $student_profile_id
 * @property int|null $container_id
 * @property string $status
 * @property \Illuminate\Support\Carbon $requested_pickup_date
 * @property string $pickup_location
 * @property string|null $contact_phone
 * @property int|null $container_count
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $confirmed_pickup_date
 * @property string|null $admin_notes
 * @property int|null $confirmed_by_user_id
 * @property \Illuminate\Support\Carbon|null $confirmed_at
 * @property-read StudentProfile $studentProfile
 * @property-read Container|null $container
 * @property-read User|null $confirmedBy
 */
class StoragePickup extends Model
{
    protected $fillable = [
        'student_profile_id',
        'container_id',
        'status',
        'requested_pickup_date',
        'pickup_location',
        'contact_phone',
        'container_count',
        'notes',
        'confirmed_pickup_date',
        'admin_notes',
        'confirmed_by_user_id',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_pickup_date' => 'date',
            'confirmed_pickup_date' => 'date',
            'container_count' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<Container, $this> */
    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }

    /** @return BelongsTo<User, $this> */
    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_user_id');
    }

    /**
     * Pickups that are still in flight (not yet returned or cancelled).
     *
     * @param  Builder<StoragePickup>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNotIn('status', [StoragePickupStatus::RETURNED, StoragePickupStatus::CANCELLED]);
    }

    public function statusLabel(): string
    {
        return StoragePickupStatus::label($this->status);
    }

    public function isActive(): bool
    {
        return StoragePickupStatus::isActive($this->status);
    }
}

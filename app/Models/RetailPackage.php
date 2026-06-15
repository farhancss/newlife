<?php

namespace App\Models;

use App\Enums\RetailPackageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $student_profile_id
 * @property int|null $created_by_user_id
 * @property string $retailer
 * @property string $description
 * @property string $tracking_number
 * @property string|null $tracking_url
 * @property \Illuminate\Support\Carbon|null $estimated_arrival
 * @property string|null $notes
 * @property string $status
 * @property string|null $removed_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read StudentProfile $studentProfile
 * @property-read User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RetailPackageStatusHistory> $statusHistories
 */
class RetailPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_profile_id',
        'created_by_user_id',
        'retailer',
        'description',
        'tracking_number',
        'tracking_url',
        'estimated_arrival',
        'notes',
        'status',
        'removed_reason',
    ];

    protected function casts(): array
    {
        return [
            'estimated_arrival' => 'date',
        ];
    }

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<RetailPackageStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(RetailPackageStatusHistory::class)->orderByDesc('created_at');
    }

    public function statusLabel(): string
    {
        return RetailPackageStatus::label($this->status);
    }

    /**
     * A package is no longer student-editable once it reaches the configured
     * lock status (default "received at hub").
     */
    public function isEditable(): bool
    {
        $lockStatus = (string) config('portal.retail_packages.edit_lock_status', RetailPackageStatus::RECEIVED_AT_HUB);

        return RetailPackageStatus::orderIndex($this->status) < RetailPackageStatus::orderIndex($lockStatus);
    }

    /**
     * Active packages count toward the per-student cap (anything not yet
     * delivered to the dorm).
     */
    public function isActive(): bool
    {
        return $this->status !== RetailPackageStatus::DELIVERED_TO_DORM;
    }
}

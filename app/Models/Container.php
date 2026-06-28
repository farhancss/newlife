<?php

namespace App\Models;

use App\Enums\ContainerStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $student_profile_id
 * @property string $code
 * @property string $status
 * @property string $source
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
    /** Container that represents the student's package move shipment. */
    public const SOURCE_MOVE = 'move';

    /** Container provisioned by an add-on purchase (e.g. Additional Container). */
    public const SOURCE_ADD_ON = 'add_on';

    protected $fillable = [
        'student_profile_id',
        'code',
        'status',
        'source',
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

    /**
     * Limit to package move-shipment containers, excluding add-on containers.
     *
     * @param  Builder<Container>  $query
     */
    public function scopeMoveShipments(Builder $query): void
    {
        $query->where('source', self::SOURCE_MOVE);
    }

    public function isAddOn(): bool
    {
        return $this->source === self::SOURCE_ADD_ON;
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

    /**
     * Exterior photos uploaded by the student while packing.
     *
     * @return HasMany<ContainerPhoto, $this>
     */
    public function exteriorPhotos(): HasMany
    {
        return $this->hasMany(ContainerPhoto::class)
            ->where('type', ContainerPhoto::TYPE_EXTERIOR)
            ->orderBy('created_at');
    }

    /**
     * Evidence photos uploaded by an admin once the container is received at
     * the New Life hub. Visible to the student as proof of condition.
     *
     * @return HasMany<ContainerPhoto, $this>
     */
    public function hubPhotos(): HasMany
    {
        return $this->hasMany(ContainerPhoto::class)
            ->where('type', ContainerPhoto::TYPE_HUB_INTAKE)
            ->orderBy('created_at');
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

    /**
     * Admins may upload hub evidence photos only once the container has been
     * delivered to the dorm. Existing photos remain viewable afterwards.
     */
    public function acceptsHubPhotos(): bool
    {
        return $this->status === ContainerStatus::DELIVERED_TO_DORM;
    }

    public function photoCap(): int
    {
        return (int) config('portal.container_photos.max_per_container', 5);
    }

    public function hubPhotoCap(): int
    {
        return (int) config('portal.container_photos.hub_max_per_container', $this->photoCap());
    }

    public function remainingPhotoSlots(): int
    {
        return max(0, $this->photoCap() - $this->exteriorPhotos()->count());
    }

    public function remainingHubPhotoSlots(): int
    {
        return max(0, $this->hubPhotoCap() - $this->hubPhotos()->count());
    }
}

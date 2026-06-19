<?php

namespace App\Models;

use App\Enums\AddOnStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_profile_id
 * @property string $add_on_slug
 * @property string $name
 * @property int $price_cents
 * @property string $squarespace_url
 * @property string $status
 * @property int|null $container_id
 * @property string|null $squarespace_order_id
 * @property \Illuminate\Support\Carbon|null $requested_at
 * @property \Illuminate\Support\Carbon|null $activated_at
 * @property int|null $activated_by_user_id
 * @property-read StudentProfile $studentProfile
 * @property-read Container|null $container
 * @property-read User|null $activatedBy
 */
class StudentAddOn extends Model
{
    /** Catalog slug for the add-on that provisions a trackable container. */
    public const ADDITIONAL_CONTAINER_SLUG = 'additional-container';

    protected $fillable = [
        'student_profile_id',
        'add_on_slug',
        'name',
        'price_cents',
        'squarespace_url',
        'status',
        'container_id',
        'squarespace_order_id',
        'requested_at',
        'activated_at',
        'activated_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'requested_at' => 'datetime',
            'activated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<StudentProfile, $this> */
    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by_user_id');
    }

    /** @return BelongsTo<Container, $this> */
    public function container(): BelongsTo
    {
        return $this->belongsTo(Container::class);
    }

    /**
     * Whether this add-on provisions a trackable container that follows the
     * full move journey (rather than just being an informational record).
     */
    public function isAdditionalContainer(): bool
    {
        return $this->add_on_slug === self::ADDITIONAL_CONTAINER_SLUG;
    }

    public function tracksContainer(): bool
    {
        return $this->isAdditionalContainer() && $this->container_id !== null;
    }

    /** @param Builder<StudentAddOn> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', AddOnStatus::ACTIVE);
    }

    public function statusLabel(): string
    {
        return AddOnStatus::label($this->status);
    }

    public function isActive(): bool
    {
        return $this->status === AddOnStatus::ACTIVE;
    }

    public function formattedPrice(): string
    {
        return '$' . number_format($this->price_cents / 100, 2);
    }
}

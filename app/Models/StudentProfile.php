<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $squarespace_contact_id
 * @property string $new_life_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $phone
 * @property string|null $school
 * @property string|null $incoming_year
 * @property string|null $package_tier
 * @property int $onboarding_step
 * @property \Illuminate\Support\Carbon|null $onboarding_completed_at
 * @property-read User|null $user
 * @property-read ParentGuardian|null $parentGuardian
 * @property-read ShippingAddress|null $shippingAddress
 * @property-read HousingInfo|null $housingInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StudentSubscription> $subscriptions
 */
class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'squarespace_contact_id',
        'new_life_id',
        'first_name',
        'last_name',
        'phone',
        'school',
        'incoming_year',
        'package_tier',
        'onboarding_step',
        'onboarding_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'onboarding_completed_at' => 'datetime',
            'onboarding_step' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasOne<ParentGuardian, $this> */
    public function parentGuardian(): HasOne
    {
        return $this->hasOne(ParentGuardian::class);
    }

    /** @return HasOne<ShippingAddress, $this> */
    public function shippingAddress(): HasOne
    {
        return $this->hasOne(ShippingAddress::class)->where('type', 'home');
    }

    /** @return HasOne<HousingInfo, $this> */
    public function housingInfo(): HasOne
    {
        return $this->hasOne(HousingInfo::class);
    }

    /** @return HasMany<StudentSubscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(StudentSubscription::class);
    }

    public function isOnboardingComplete(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function fullName(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
}

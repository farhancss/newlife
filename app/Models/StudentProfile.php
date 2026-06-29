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
 * @property int|null $package_id
 * @property int|null $package_price_cents
 * @property-read Package|null $package
 * @property int $onboarding_step
 * @property \Illuminate\Support\Carbon|null $onboarding_completed_at
 * @property int $move_container_quantity
 * @property \Illuminate\Support\Carbon|null $move_address_confirmed_at
 * @property \Illuminate\Support\Carbon|null $move_shipment_triggered_at
 * @property \Illuminate\Support\Carbon|null $retail_packages_acknowledged_at
 * @property-read User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Container> $containers
 * @property-read \Illuminate\Database\Eloquent\Collection<int, RetailPackage> $retailPackages
 * @property-read ParentGuardian|null $parentGuardian
 * @property-read ShippingAddress|null $shippingAddress
 * @property-read HousingInfo|null $housingInfo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StudentSubscription> $subscriptions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StudentAddOn> $addOns
 * @property-read \Illuminate\Database\Eloquent\Collection<int, StoragePickup> $storagePickups
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
        'package_id',
        'package_price_cents',
        'onboarding_step',
        'onboarding_completed_at',
        'move_container_quantity',
        'move_address_confirmed_at',
        'move_shipment_triggered_at',
        'retail_packages_acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'onboarding_completed_at' => 'datetime',
            'onboarding_step' => 'integer',
            'package_price_cents' => 'integer',
            'move_container_quantity' => 'integer',
            'move_address_confirmed_at' => 'datetime',
            'move_shipment_triggered_at' => 'datetime',
            'retail_packages_acknowledged_at' => 'datetime',
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

    /** @return HasMany<SquarespaceOrder, $this> */
    public function squarespaceOrders(): HasMany
    {
        return $this->hasMany(SquarespaceOrder::class)->latest('placed_at');
    }

    /** @return HasMany<Container, $this> */
    public function containers(): HasMany
    {
        return $this->hasMany(Container::class);
    }

    /** @return HasMany<RetailPackage, $this> */
    public function retailPackages(): HasMany
    {
        return $this->hasMany(RetailPackage::class)->latest();
    }

    /** @return HasMany<StudentAddOn, $this> */
    public function addOns(): HasMany
    {
        return $this->hasMany(StudentAddOn::class)->latest();
    }

    /** @return HasMany<Deadline, $this> */
    public function deadlines(): HasMany
    {
        return $this->hasMany(Deadline::class);
    }

    /** @return HasMany<StoragePickup, $this> */
    public function storagePickups(): HasMany
    {
        return $this->hasMany(StoragePickup::class)->latest();
    }

    public function hasAcknowledgedRetailTerms(): bool
    {
        return $this->retail_packages_acknowledged_at !== null;
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
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

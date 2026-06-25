<?php

namespace App\Services;

use App\Models\StudentAddOn;
use App\Models\StudentProfile;
use App\Services\AddOnService;

/**
 * Decides whether a student may schedule an end-of-year dorm pickup. Eligibility
 * is granted when storage is bundled into their package, or when they have an
 * active summer-storage add-on. Otherwise the UI nudges them to purchase the
 * storage add-on first.
 */
class StorageEligibilityService
{
    public function __construct(
        private readonly StudentPackageService $studentPackageService,
        private readonly AddOnService $addOnService,
    ) {
    }

    public function isEligible(StudentProfile $profile): bool
    {
        return $this->packageIncludesStorage($profile) || $this->hasActiveStorageAddOn($profile);
    }

    public function packageIncludesStorage(StudentProfile $profile): bool
    {
        return (bool) $this->studentPackageService->resolve($profile)?->includesStorage();
    }

    public function hasActiveStorageAddOn(StudentProfile $profile): bool
    {
        return $profile->addOns()
            ->active()
            ->where('add_on_slug', StudentAddOn::SUMMER_STORAGE_SLUG)
            ->exists();
    }

    /**
     * The reason a student qualifies, for display in the UI. Null when not
     * eligible.
     */
    public function reason(StudentProfile $profile): ?string
    {
        if ($this->packageIncludesStorage($profile)) {
            return 'package';
        }

        if ($this->hasActiveStorageAddOn($profile)) {
            return 'add_on';
        }

        return null;
    }

    /**
     * The catalog entry students should buy to unlock storage, used to render
     * the upsell when they are not yet eligible.
     *
     * @return array{slug: string, name: string, price_cents: int, description: string, icon: string, url: string, formatted_price: string}|null
     */
    public function storageAddOn(): ?array
    {
        return $this->addOnService->findInCatalog(StudentAddOn::SUMMER_STORAGE_SLUG);
    }
}

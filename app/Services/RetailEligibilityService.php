<?php

namespace App\Services;

use App\Models\StudentProfile;

/**
 * Decides whether a student may log retail packages and how many. The feature
 * is bundled into the Legacy package (max 5), while Essentials and Summit
 * students unlock it only after purchasing an add-on. Otherwise the UI nudges
 * them toward an add-on or a package upgrade.
 */
class RetailEligibilityService
{
    public function __construct(
        private readonly StudentPackageService $studentPackageService,
    ) {
    }

    public function isEligible(StudentProfile $profile): bool
    {
        return $this->packageAllowsRetail($profile) || $this->hasPurchasedAddOn($profile);
    }

    public function packageAllowsRetail(StudentProfile $profile): bool
    {
        return (bool) $this->studentPackageService->resolve($profile)?->allowsRetailPackages();
    }

    public function hasPurchasedAddOn(StudentProfile $profile): bool
    {
        return $profile->addOns()->active()->exists();
    }

    /**
     * The maximum number of retail packages this student may have logged at
     * once. The package allowance (e.g. 5 on Legacy) is the base, and each
     * purchased add-on stacks additional slots on top so students can extend
     * their limit. Zero means the feature is locked for them.
     */
    public function maxPackages(StudentProfile $profile): int
    {
        $base = $this->packageAllowsRetail($profile)
            ? (int) $this->studentPackageService->resolve($profile)?->maxRetailPackages()
            : 0;

        $bonus = $this->activeAddOnCount($profile) * (int) config('portal.retail_packages.addon_cap', 5);

        return $base + $bonus;
    }

    public function activeAddOnCount(StudentProfile $profile): int
    {
        return $profile->addOns()->active()->count();
    }

    /**
     * Why the student qualifies, for display in the UI. Null when locked.
     */
    public function reason(StudentProfile $profile): ?string
    {
        if ($this->packageAllowsRetail($profile)) {
            return 'package';
        }

        if ($this->hasPurchasedAddOn($profile)) {
            return 'add_on';
        }

        return null;
    }
}

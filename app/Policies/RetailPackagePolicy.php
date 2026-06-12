<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\RetailPackage;
use App\Models\User;

class RetailPackagePolicy
{
    /**
     * Admins have full access to every package.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return null;
    }

    public function view(User $user, RetailPackage $package): bool
    {
        return $this->owns($user, $package);
    }

    public function update(User $user, RetailPackage $package): bool
    {
        return $this->owns($user, $package) && $package->isEditable();
    }

    public function delete(User $user, RetailPackage $package): bool
    {
        return $this->owns($user, $package) && $package->isEditable();
    }

    private function owns(User $user, RetailPackage $package): bool
    {
        $profile = $user->studentProfile;

        return $profile !== null && $package->student_profile_id === $profile->id;
    }
}

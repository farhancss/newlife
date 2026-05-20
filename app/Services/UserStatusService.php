<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;

class UserStatusService
{
    /**
     * Mark a freshly provisioned account as INVITED.
     * Idempotent: never downgrades ACTIVE/INCOMPLETE accounts that may re-receive a webhook.
     */
    public function markInvited(User $user): User
    {
        if ($user->status === UserStatus::INVITED) {
            return $user;
        }

        if (in_array($user->status, [UserStatus::ACTIVE, UserStatus::INCOMPLETE], true)) {
            return $user;
        }

        $user->status = UserStatus::INVITED;
        $user->save();

        return $user;
    }

    /**
     * Move INVITED → INCOMPLETE the first time a customer sets their permanent password.
     * No-op for already INCOMPLETE/ACTIVE/SUSPENDED accounts.
     */
    public function markPasswordChanged(User $user): User
    {
        if ($user->status === UserStatus::INVITED) {
            $user->status = UserStatus::INCOMPLETE;
            $user->save();
        }

        return $user;
    }

    /**
     * Move INVITED/INCOMPLETE → ACTIVE when the customer finishes onboarding.
     * Idempotent for already ACTIVE accounts. Suspended accounts are never auto-reactivated.
     */
    public function markOnboardingComplete(User $user): User
    {
        if ($user->isSuspended()) {
            return $user;
        }

        if ($user->status !== UserStatus::ACTIVE) {
            $user->status = UserStatus::ACTIVE;
            $user->save();
        }

        return $user;
    }

    /**
     * Reverse: an active account whose profile becomes incomplete again (admin reset, data change).
     * Suspended accounts are preserved.
     */
    public function markIncomplete(User $user): User
    {
        if ($user->isSuspended()) {
            return $user;
        }

        if ($user->status === UserStatus::INVITED) {
            return $user;
        }

        if ($user->status !== UserStatus::INCOMPLETE) {
            $user->status = UserStatus::INCOMPLETE;
            $user->save();
        }

        return $user;
    }
}

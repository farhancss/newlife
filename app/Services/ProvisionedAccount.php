<?php

namespace App\Services;

use App\Models\StudentProfile;
use App\Models\User;

final class ProvisionedAccount
{
    public function __construct(
        public readonly StudentProfile $profile,
        public readonly User $user,
        public readonly bool $isNewUser,
        public readonly ?string $temporaryPassword,
    ) {
    }
}

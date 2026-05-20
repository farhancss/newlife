<?php

namespace App\Services;

use App\Models\StudentProfile;

class NewLifeIdGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');
        $sequence = StudentProfile::query()->count() + 1;

        return sprintf('NL-%s%05d', $year, $sequence);
    }
}

<?php

namespace App\Services;

use App\Models\StudentProfile;
use App\Models\User;

class StudentProfileService
{
    public function __construct(private readonly NewLifeIdGenerator $newLifeIdGenerator)
    {
    }

    public function ensureForUser(User $user): StudentProfile
    {
        $existing = StudentProfile::query()->where('user_id', $user->id)->first();

        if ($existing instanceof StudentProfile) {
            return $existing;
        }

        [$firstName, $lastName] = $this->splitName($user->name);

        return StudentProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'new_life_id' => $this->newLifeIdGenerator->generate(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'onboarding_step' => 1,
            ]
        );
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2);

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
        ];
    }
}

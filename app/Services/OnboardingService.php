<?php

namespace App\Services;

use App\Models\HousingInfo;
use App\Models\ParentGuardian;
use App\Models\ShippingAddress;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    public function __construct(
        private readonly DeadlineService $deadlines,
    ) {
    }

    public function getProgress(StudentProfile $profile): int
    {
        return (int) $profile->onboarding_step;
    }

    public function isComplete(StudentProfile $profile): bool
    {
        return $profile->isOnboardingComplete();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function hasSectionChanges(StudentProfile $profile, int $step, array $data): bool
    {
        $profile->loadMissing(['parentGuardian', 'shippingAddress', 'housingInfo']);

        return match ($step) {
            1 => $this->studentInfoChanged($profile, $data),
            2 => $this->parentGuardianChanged($profile, $data),
            3 => $this->shippingChanged($profile, $data),
            4 => $this->housingChanged($profile, $data),
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveStep(User $user, int $step, array $data): StudentProfile
    {
        $profile = $user->studentProfile;

        if (!$profile instanceof StudentProfile) {
            throw new \RuntimeException('Student profile not found.');
        }

        return DB::transaction(function () use ($profile, $step, $data): StudentProfile {
            match ($step) {
                1 => $this->saveStudentInfo($profile, $data),
                2 => $this->saveParentGuardian($profile, $data),
                3 => $this->saveShipping($profile, $data),
                4 => $this->saveHousing($profile, $data),
                default => throw new \InvalidArgumentException('Invalid onboarding step.'),
            };

            return $profile->fresh(['parentGuardian', 'shippingAddress', 'housingInfo']);
        });
    }

    public function complete(StudentProfile $profile): StudentProfile
    {
        $profile->onboarding_completed_at = now();
        $profile->onboarding_step = 5;
        $profile->save();

        // Case 01: completing onboarding satisfies the profile-completion deadline.
        $this->deadlines->syncForSubject($profile);

        return $profile;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveStudentInfo(StudentProfile $profile, array $data): void
    {
        $profile->update([
            'first_name' => $data['first_name'] ?? $profile->first_name,
            'last_name' => $data['last_name'] ?? $profile->last_name,
            'phone' => $data['phone'] ?? $profile->phone,
            'school' => $data['school'] ?? $profile->school,
            'incoming_year' => $data['incoming_year'] ?? $profile->incoming_year,
        ]);

        if (isset($data['name'])) {
            $profile->user?->update(['name' => $data['name']]);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveParentGuardian(StudentProfile $profile, array $data): void
    {
        ParentGuardian::query()->updateOrCreate(
            ['student_profile_id' => $profile->id],
            [
                'name' => $data['parent_name'] ?? null,
                'email' => $data['parent_email'] ?? null,
                'phone' => $data['parent_phone'] ?? null,
                'relationship' => $data['parent_relationship'] ?? null,
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveShipping(StudentProfile $profile, array $data): void
    {
        ShippingAddress::query()->updateOrCreate(
            [
                'student_profile_id' => $profile->id,
                'type' => 'home',
            ],
            [
                'line1' => $data['line1'] ?? null,
                'line2' => $data['line2'] ?? null,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country_code' => $data['country_code'] ?? 'US',
                'phone' => $data['phone'] ?? null,
                'shipping_notes' => $data['shipping_notes'] ?? null,
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveHousing(StudentProfile $profile, array $data): void
    {
        HousingInfo::query()->updateOrCreate(
            ['student_profile_id' => $profile->id],
            [
                'university' => $data['university'] ?? null,
                'residence_hall' => $data['residence_hall'] ?? null,
                'building' => $data['building'] ?? null,
                'room' => $data['room'] ?? null,
                'move_in_date' => $data['move_in_date'] ?? null,
                'move_in_window' => $data['move_in_window'] ?? null,
                'delivery_notes' => $data['delivery_notes'] ?? null,
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function studentInfoChanged(StudentProfile $profile, array $data): bool
    {
        return $this->fieldChanged($profile->first_name, $data['first_name'] ?? null)
            || $this->fieldChanged($profile->last_name, $data['last_name'] ?? null)
            || $this->fieldChanged($profile->phone, $data['phone'] ?? null)
            || $this->fieldChanged($profile->school, $data['school'] ?? null)
            || $this->fieldChanged($profile->incoming_year, $data['incoming_year'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function parentGuardianChanged(StudentProfile $profile, array $data): bool
    {
        $parent = $profile->parentGuardian;

        return $this->fieldChanged($parent?->name, $data['parent_name'] ?? null)
            || $this->fieldChanged($parent?->email, $data['parent_email'] ?? null)
            || $this->fieldChanged($parent?->phone, $data['parent_phone'] ?? null)
            || $this->fieldChanged($parent?->relationship, $data['parent_relationship'] ?? null);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function shippingChanged(StudentProfile $profile, array $data): bool
    {
        $shipping = $profile->shippingAddress;
        $country = strtoupper((string) ($data['country_code'] ?? 'US'));

        return $this->fieldChanged($shipping?->line1, $data['line1'] ?? null)
            || $this->fieldChanged($shipping?->line2, $data['line2'] ?? null)
            || $this->fieldChanged($shipping?->city, $data['city'] ?? null)
            || $this->fieldChanged($shipping?->region, $data['region'] ?? null)
            || $this->fieldChanged($shipping?->postal_code, $data['postal_code'] ?? null)
            || $this->fieldChanged($this->currentCountryCode($shipping), $country)
            || $this->fieldChanged($shipping?->shipping_notes, $data['shipping_notes'] ?? null);
    }

    private function currentCountryCode(?ShippingAddress $shipping): string
    {
        if ($shipping === null) {
            return 'US';
        }

        $code = $shipping->country_code;

        return $code !== null && $code !== '' ? $code : 'US';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function housingChanged(StudentProfile $profile, array $data): bool
    {
        $housing = $profile->housingInfo;
        $incomingDate = isset($data['move_in_date'])
            ? (string) $data['move_in_date']
            : null;
        $currentDate = $housing?->move_in_date?->format('Y-m-d');

        return $this->fieldChanged($housing?->university, $data['university'] ?? null)
            || $this->fieldChanged($housing?->residence_hall, $data['residence_hall'] ?? null)
            || $this->fieldChanged($housing?->building, $data['building'] ?? null)
            || $this->fieldChanged($housing?->room, $data['room'] ?? null)
            || $this->fieldChanged($currentDate, $incomingDate)
            || $this->fieldChanged($housing?->move_in_window, $data['move_in_window'] ?? null)
            || $this->fieldChanged($housing?->delivery_notes, $data['delivery_notes'] ?? null);
    }

    private function fieldChanged(mixed $current, mixed $incoming): bool
    {
        return $this->normalizeValue($current) !== $this->normalizeValue($incoming);
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}

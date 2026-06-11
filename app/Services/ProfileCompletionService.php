<?php

namespace App\Services;

use App\Mail\OnboardingCompleteMail;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ProfileCompletionService
{
    public function __construct(
        private readonly UserStatusService $userStatusService,
        private readonly StudentPackageService $studentPackageService,
        private readonly ContainerWorkflowService $containerWorkflowService,
    ) {
    }

    /**
     * @return array{
     *     percent: int,
     *     is_complete: bool,
     *     completed_fields: int,
     *     total_fields: int,
     *     sections: list<array{
     *         key: string,
     *         label: string,
     *         step: int,
     *         complete: bool,
     *         percent: int,
     *         missing: list<string>
     *     }>,
     *     next_section: int|null
     * }
     */
    public function summary(StudentProfile $profile): array
    {
        $profile->loadMissing(['parentGuardian', 'shippingAddress', 'housingInfo']);

        $sections = [
            $this->studentSection($profile),
            $this->parentSection($profile),
            $this->shippingSection($profile),
            $this->housingSection($profile),
        ];

        $totalFields = 0;
        $completedFields = 0;

        foreach ($sections as $section) {
            $totalFields += count($section['fields']);
            $completedFields += count(array_filter($section['fields'], fn (array $field): bool => $field['filled']));
        }

        $percent = $totalFields > 0
            ? (int) round(($completedFields / $totalFields) * 100)
            : 0;

        $nextSection = null;
        foreach ($sections as $section) {
            if (!$section['complete']) {
                $nextSection = $section['step'];
                break;
            }
        }

        return [
            'percent' => $percent,
            'is_complete' => $percent === 100,
            'completed_fields' => $completedFields,
            'total_fields' => $totalFields,
            'sections' => array_map(
                fn (array $section): array => [
                    'key' => $section['key'],
                    'label' => $section['label'],
                    'step' => $section['step'],
                    'complete' => $section['complete'],
                    'percent' => $section['percent'],
                    'missing' => $section['missing'],
                ],
                $sections
            ),
            'next_section' => $nextSection,
        ];
    }

    public function isComplete(StudentProfile $profile): bool
    {
        return $this->summary($profile)['is_complete'];
    }

    /**
     * Provision the student's single move shipment (status "Container Prepared")
     * the first time onboarding completes. The package may include multiple
     * physical bins, recorded as the move quantity, but they ship and track
     * together as one shipment.
     */
    private function provisionContainers(StudentProfile $profile, User $user): void
    {
        $allowance = $this->studentPackageService->containerAllowance($profile);

        if ($profile->move_container_quantity < $allowance) {
            $profile->move_container_quantity = $allowance;
            $profile->save();
        }

        $this->containerWorkflowService->ensureMoveShipment($profile, $user);
    }

    public function resolveActiveSection(StudentProfile $profile, ?int $requestedSection = null): int
    {
        if ($requestedSection !== null && $requestedSection >= 1 && $requestedSection <= 4) {
            return $requestedSection;
        }

        $summary = $this->summary($profile);

        if ($summary['next_section'] !== null) {
            return $summary['next_section'];
        }

        $step = (int) $profile->onboarding_step;

        if ($step >= 1 && $step <= 4) {
            return $step;
        }

        return 1;
    }

    /**
     * @deprecated Use resolveActiveSection() instead.
     */
    public function resolveCurrentStep(StudentProfile $profile): int
    {
        return $this->resolveActiveSection($profile);
    }

    public function nextIncompleteSection(StudentProfile $profile, int $currentSection = 1): ?int
    {
        $summary = $this->summary($profile);

        foreach ($summary['sections'] as $section) {
            if ($section['step'] >= $currentSection && !$section['complete']) {
                return $section['step'];
            }
        }

        foreach ($summary['sections'] as $section) {
            if (!$section['complete']) {
                return $section['step'];
            }
        }

        return null;
    }

    public function syncCompletionStatus(StudentProfile $profile, bool $preserveStep = false): StudentProfile
    {
        $profile->loadMissing('user');

        $summary = $this->summary($profile);
        $wasComplete = $profile->onboarding_completed_at !== null;

        if ($summary['is_complete']) {
            $profile->onboarding_completed_at = $profile->onboarding_completed_at ?? now();

            if (!$preserveStep) {
                $profile->onboarding_step = 5;
            }
        } else {
            $profile->onboarding_completed_at = null;

            if (!$preserveStep) {
                $profile->onboarding_step = $summary['next_section'] ?? 1;
            }
        }

        $profile->save();

        $user = $profile->user;

        if ($user) {
            if ($summary['is_complete']) {
                $this->userStatusService->markOnboardingComplete($user);

                if (!$wasComplete) {
                    $this->provisionContainers($profile, $user);

                    Mail::to($user->email)->queue(new OnboardingCompleteMail($user));
                }
            } else {
                $this->userStatusService->markIncomplete($user);
            }
        }

        return $profile;
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     step: int,
     *     complete: bool,
     *     percent: int,
     *     missing: list<string>,
     *     fields: list<array{label: string, filled: bool}>
     * }
     */
    private function studentSection(StudentProfile $profile): array
    {
        $fields = [
            ['label' => 'First name', 'filled' => $this->filled($profile->first_name)],
            ['label' => 'Last name', 'filled' => $this->filled($profile->last_name)],
            ['label' => 'Phone', 'filled' => $this->filled($profile->phone)],
            ['label' => 'School', 'filled' => $this->filled($profile->school)],
            ['label' => 'Incoming year', 'filled' => $this->filled($profile->incoming_year)],
        ];

        return $this->buildSection('student', 'Student Information', 1, $fields);
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     step: int,
     *     complete: bool,
     *     percent: int,
     *     missing: list<string>,
     *     fields: list<array{label: string, filled: bool}>
     * }
     */
    private function parentSection(StudentProfile $profile): array
    {
        $parent = $profile->parentGuardian;

        $fields = [
            ['label' => 'Parent name', 'filled' => $this->filled($parent?->name)],
            ['label' => 'Parent email', 'filled' => $this->filled($parent?->email)],
            ['label' => 'Parent phone', 'filled' => $this->filled($parent?->phone)],
            ['label' => 'Relationship', 'filled' => $this->filled($parent?->relationship)],
        ];

        return $this->buildSection('parent', 'Parent / Guardian', 2, $fields);
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     step: int,
     *     complete: bool,
     *     percent: int,
     *     missing: list<string>,
     *     fields: list<array{label: string, filled: bool}>
     * }
     */
    private function shippingSection(StudentProfile $profile): array
    {
        $shipping = $profile->shippingAddress;

        $fields = [
            ['label' => 'Home street address', 'filled' => $this->filled($shipping?->line1)],
            ['label' => 'City', 'filled' => $this->filled($shipping?->city)],
            ['label' => 'State', 'filled' => $this->filled($shipping?->region)],
            ['label' => 'ZIP code', 'filled' => $this->filled($shipping?->postal_code)],
        ];

        return $this->buildSection('shipping', 'Home Address', 3, $fields);
    }

    /**
     * @return array{
     *     key: string,
     *     label: string,
     *     step: int,
     *     complete: bool,
     *     percent: int,
     *     missing: list<string>,
     *     fields: list<array{label: string, filled: bool}>
     * }
     */
    private function housingSection(StudentProfile $profile): array
    {
        $housing = $profile->housingInfo;

        $fields = [
            ['label' => 'University', 'filled' => $this->filled($housing?->university)],
            ['label' => 'Dorm / residence hall', 'filled' => $this->filled($housing?->residence_hall)],
            ['label' => 'Move-in date', 'filled' => $housing?->move_in_date !== null],
        ];

        return $this->buildSection('housing', 'University Dorm', 4, $fields);
    }

    /**
     * @param list<array{label: string, filled: bool}> $fields
     * @return array{
     *     key: string,
     *     label: string,
     *     step: int,
     *     complete: bool,
     *     percent: int,
     *     missing: list<string>,
     *     fields: list<array{label: string, filled: bool}>
     * }
     */
    private function buildSection(string $key, string $label, int $step, array $fields): array
    {
        $total = count($fields);
        $completed = count(array_filter($fields, fn (array $field): bool => $field['filled']));
        $missing = array_values(array_map(
            fn (array $field): string => $field['label'],
            array_filter($fields, fn (array $field): bool => !$field['filled'])
        ));

        return [
            'key' => $key,
            'label' => $label,
            'step' => $step,
            'complete' => $completed === $total,
            'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'missing' => $missing,
            'fields' => $fields,
        ];
    }

    private function filled(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        return true;
    }
}

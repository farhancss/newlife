<?php

namespace App\Services;

use App\Enums\ContainerStatus;
use App\Models\Container;
use App\Models\StudentProfile;

/**
 * Single source of truth for the high-level, six-step move journey shown on the
 * student dashboard, My Move page, and admin students list. The active step is
 * always derived from the primary container's detailed (1-12) workflow status
 * so every surface displays a consistent stage.
 */
class MoveProgressService
{
    public const STEP_RESERVATION = 'reservation';
    public const STEP_PROFILE = 'profile';
    public const STEP_PREPARING = 'preparing';
    public const STEP_SHIPPED = 'shipped';
    public const STEP_DELIVERED_HOME = 'delivered_home';
    public const STEP_DORM = 'dorm';

    /**
     * Container statuses grouped into the four container-driven dashboard steps.
     *
     * @var array<string, list<string>>
     */
    private const CONTAINER_STEP_MAP = [
        self::STEP_PREPARING => [
            ContainerStatus::CONTAINER_PREPARED,
            ContainerStatus::LABEL_GENERATED,
        ],
        self::STEP_SHIPPED => [
            ContainerStatus::SHIPPED_TO_HOME,
        ],
        self::STEP_DELIVERED_HOME => [
            ContainerStatus::DELIVERED_TO_HOME,
            ContainerStatus::CUSTOMER_PACKING,
            ContainerStatus::PICKUP_SCHEDULED,
            ContainerStatus::RETURN_SHIPMENT_IN_TRANSIT,
            ContainerStatus::RECEIVED_AT_NEW_LIFE_HUB,
            ContainerStatus::STORED_AT_RECEIVING_HUB,
            ContainerStatus::SCHEDULED_FOR_DORM_DELIVERY,
        ],
        self::STEP_DORM => [
            ContainerStatus::OUT_FOR_DELIVERY,
            ContainerStatus::DELIVERED_TO_DORM,
        ],
    ];

    /**
     * @return list<array{key: string, label: string, number: int, state: string}>
     */
    public function dashboardSteps(StudentProfile $profile): array
    {
        $labels = [
            self::STEP_RESERVATION => 'Reservation Confirmed',
            self::STEP_PROFILE => 'Profile Completed',
            self::STEP_PREPARING => 'Containers Preparing',
            self::STEP_SHIPPED => 'Containers Shipped',
            self::STEP_DELIVERED_HOME => 'Delivered to Home',
            self::STEP_DORM => 'Dorm Delivery',
        ];

        $activeIndex = $this->activeIndex($profile);

        $steps = [];
        $number = 1;
        foreach ($labels as $key => $label) {
            $index = $number - 1;
            $steps[] = [
                'key' => $key,
                'label' => $label,
                'number' => $number,
                'state' => match (true) {
                    $index < $activeIndex => 'done',
                    $index === $activeIndex => 'active',
                    default => 'pending',
                },
            ];
            $number++;
        }

        return $steps;
    }

    public function currentLabel(StudentProfile $profile): string
    {
        $steps = $this->dashboardSteps($profile);

        foreach ($steps as $step) {
            if ($step['state'] === 'active') {
                // The profile milestone is titled "Profile Completed" in the
                // stepper, but while it's the active (in-progress) step the
                // student is still onboarding and hasn't finished their profile.
                if ($step['key'] === self::STEP_PROFILE && ! $profile->isOnboardingComplete()) {
                    return 'Student Onboarding';
                }

                return $step['label'];
            }
        }

        return $steps[0]['label'];
    }

    /**
     * Zero-based index of the active dashboard step (0 = Reservation … 5 = Dorm).
     */
    private function activeIndex(StudentProfile $profile): int
    {
        if (!$profile->isOnboardingComplete()) {
            return 1; // Profile Completed (in progress)
        }

        $primary = $profile->containers()->moveShipments()->orderBy('id')->first();

        if (!$primary instanceof Container) {
            return 2; // Containers Preparing (auto-provisioning)
        }

        return $this->indexForStatus($primary->status);
    }

    private function indexForStatus(string $status): int
    {
        $order = [
            self::STEP_PREPARING => 2,
            self::STEP_SHIPPED => 3,
            self::STEP_DELIVERED_HOME => 4,
            self::STEP_DORM => 5,
        ];

        foreach (self::CONTAINER_STEP_MAP as $stepKey => $statuses) {
            if (in_array($status, $statuses, true)) {
                return $order[$stepKey];
            }
        }

        return 2;
    }
}

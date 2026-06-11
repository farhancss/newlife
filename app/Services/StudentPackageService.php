<?php

namespace App\Services;

use App\Enums\PackageTier;
use App\Models\Package;
use App\Models\StudentProfile;

class StudentPackageService
{
    public function resolve(StudentProfile $profile): ?Package
    {
        if ($profile->relationLoaded('package') && $profile->package instanceof Package) {
            return $profile->package;
        }

        if ($profile->package_id) {
            return Package::query()->find($profile->package_id);
        }

        $slug = PackageTier::normalize($profile->package_tier);

        if ($slug === PackageTier::UNKNOWN) {
            return null;
        }

        return Package::query()->where('slug', $slug)->first();
    }

    public function assignFromPackage(StudentProfile $profile, Package $package): StudentProfile
    {
        return $this->assignFromTier($profile, $package->slug);
    }

    public function assignFromTier(StudentProfile $profile, string $tier): StudentProfile
    {
        $slug = PackageTier::normalize($tier);
        $profile->package_tier = $slug === PackageTier::UNKNOWN ? null : $slug;

        $package = $slug !== PackageTier::UNKNOWN
            ? Package::query()->where('slug', $slug)->first()
            : null;

        $profile->package_id = $package?->id;

        if ($package && ($profile->move_container_quantity < 1 || $profile->move_container_quantity === 1)) {
            $profile->move_container_quantity = $package->container_count;
        }

        $profile->save();

        return $profile->fresh(['package']);
    }

    public function containerAllowance(StudentProfile $profile): int
    {
        $package = $this->resolve($profile);

        return $package !== null ? $package->container_count : 1;
    }

    public function containersAssigned(StudentProfile $profile): int
    {
        return $profile->containers()->count();
    }

    public function containersRemaining(StudentProfile $profile): int
    {
        return max(0, $this->containerAllowance($profile) - $this->containersAssigned($profile));
    }

    public function canAssignContainer(StudentProfile $profile): bool
    {
        return $this->containersRemaining($profile) > 0;
    }

    /**
     * @return list<array{key: string, label: string, statuses: list<string>}>
     */
    public function movePhases(): array
    {
        return [
            [
                'key' => 'prep',
                'label' => 'Preparation',
                'statuses' => ['container_prepared', 'label_generated'],
            ],
            [
                'key' => 'to_home',
                'label' => 'To your home',
                'statuses' => ['shipped_to_home', 'delivered_to_home'],
            ],
            [
                'key' => 'packing',
                'label' => 'Pack & pickup',
                'statuses' => ['customer_packing', 'pickup_scheduled'],
            ],
            [
                'key' => 'return',
                'label' => 'Return to hub',
                'statuses' => ['return_shipment_in_transit', 'received_at_new_life_hub'],
            ],
            [
                'key' => 'storage',
                'label' => 'Storage & scheduling',
                'statuses' => ['stored_at_receiving_hub', 'scheduled_for_dorm_delivery'],
            ],
            [
                'key' => 'dorm',
                'label' => 'Dorm delivery',
                'statuses' => ['out_for_delivery', 'delivered_to_dorm'],
            ],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, reached: bool, current: bool}>
     */
    public function phaseProgressFor(string $containerStatus): array
    {
        $phases = $this->movePhases();
        $currentPhaseIndex = 0;

        foreach ($phases as $index => $phase) {
            if (in_array($containerStatus, $phase['statuses'], true)) {
                $currentPhaseIndex = $index;
                break;
            }
        }

        $progress = [];
        foreach ($phases as $index => $phase) {
            $progress[] = [
                'key' => $phase['key'],
                'label' => $phase['label'],
                'reached' => $index <= $currentPhaseIndex,
                'current' => $index === $currentPhaseIndex,
            ];
        }

        return $progress;
    }
}

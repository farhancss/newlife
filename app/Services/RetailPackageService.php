<?php

namespace App\Services;

use App\Enums\RetailPackageStatus;
use App\Models\RetailPackage;
use App\Models\RetailPackageStatusHistory;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RetailPackageService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly DeadlineService $deadlines,
    ) {
    }

    /**
     * Log a new retail package for the student, enforcing the active cap and
     * recording a first-time acknowledgement of the intake terms.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(StudentProfile $profile, array $data, ?User $actor = null): RetailPackage
    {
        if ($this->activeCount($profile) >= $this->activeCap()) {
            throw ValidationException::withMessages([
                'tracking_number' => "You can track up to {$this->activeCap()} active packages at a time. Mark delivered packages complete or contact support.",
            ]);
        }

        $package = DB::transaction(function () use ($profile, $data, $actor): RetailPackage {
            $package = RetailPackage::query()->create([
                'student_profile_id' => $profile->id,
                'created_by_user_id' => $actor?->id,
                'retailer' => $data['retailer'],
                'description' => $data['description'],
                'tracking_number' => $data['tracking_number'],
                'tracking_url' => $data['tracking_url'] ?? null,
                'estimated_arrival' => $data['estimated_arrival'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => RetailPackageStatus::LOGGED,
            ]);

            $this->recordHistory($package, null, RetailPackageStatus::LOGGED, $actor, 'Package logged');

            if ($profile->retail_packages_acknowledged_at === null) {
                $profile->forceFill(['retail_packages_acknowledged_at' => now()])->save();
            }

            return $package;
        });

        // Case 03: track arrival against the estimated date.
        $package->setRelation('studentProfile', $profile);
        $this->deadlines->openRetailArrival($package);

        return $package;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RetailPackage $package, array $data): RetailPackage
    {
        if (! $package->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'This package can no longer be edited because it has been received at the hub.',
            ]);
        }

        $package->fill([
            'retailer' => $data['retailer'] ?? $package->retailer,
            'description' => $data['description'] ?? $package->description,
            'tracking_number' => $data['tracking_number'] ?? $package->tracking_number,
            'tracking_url' => array_key_exists('tracking_url', $data) ? $data['tracking_url'] : $package->tracking_url,
            'estimated_arrival' => array_key_exists('estimated_arrival', $data) ? $data['estimated_arrival'] : $package->estimated_arrival,
            'notes' => array_key_exists('notes', $data) ? $data['notes'] : $package->notes,
        ]);
        $package->save();

        // Open an arrival deadline if an estimated date was added after logging.
        if ($package->estimated_arrival !== null) {
            $this->deadlines->openRetailArrival($package);
        }

        return $package;
    }

    public function delete(RetailPackage $package, ?string $reason = null): void
    {
        if ($reason !== null && $reason !== '') {
            $package->forceFill(['removed_reason' => $reason])->save();
        }

        $package->delete();
    }

    public function transition(
        RetailPackage $package,
        string $toStatus,
        ?User $actor = null,
        ?string $note = null,
        bool $force = false,
    ): RetailPackage {
        if (! RetailPackageStatus::isValid($toStatus)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid package status.',
            ]);
        }

        $fromStatus = $package->status;

        if ($fromStatus === $toStatus) {
            return $package;
        }

        if (! $force && RetailPackageStatus::orderIndex($toStatus) < RetailPackageStatus::orderIndex($fromStatus)) {
            throw ValidationException::withMessages([
                'status' => 'Cannot move a package to an earlier workflow stage without override.',
            ]);
        }

        $fresh = DB::transaction(function () use ($package, $fromStatus, $toStatus, $actor, $note): RetailPackage {
            $package->status = $toStatus;
            $package->save();

            $this->recordHistory($package, $fromStatus, $toStatus, $actor, $note);

            return $package->fresh(['studentProfile.user']) ?? $package;
        });

        $this->notifications->retailPackageStatusChanged($fresh, $toStatus);

        // Case 03: arriving at the hub satisfies the arrival deadline.
        $this->deadlines->syncForSubject($fresh);

        return $fresh;
    }

    public function activeCount(StudentProfile $profile): int
    {
        return $profile->retailPackages()
            ->where('status', '!=', RetailPackageStatus::DELIVERED_TO_DORM)
            ->count();
    }

    public function activeCap(): int
    {
        return (int) config('portal.retail_packages.active_cap', 10);
    }

    private function recordHistory(
        RetailPackage $package,
        ?string $fromStatus,
        string $toStatus,
        ?User $actor,
        ?string $note,
    ): void {
        RetailPackageStatusHistory::query()->create([
            'retail_package_id' => $package->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $actor?->id,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}

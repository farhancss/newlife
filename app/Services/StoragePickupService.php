<?php

namespace App\Services;

use App\Enums\ContainerStatus;
use App\Enums\StoragePickupStatus;
use App\Models\Container;
use App\Models\StoragePickup;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns the end-of-year storage pickup journey: students request a dorm pickup
 * once their move is delivered, and admins schedule and fulfil it. This is kept
 * separate from the 12-stage move workflow {@see ContainerWorkflowService}.
 */
class StoragePickupService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly StorageEligibilityService $eligibility,
    ) {
    }

    /**
     * The single in-flight pickup for a student, if any. Used to decide what to
     * render on the My Move page.
     */
    public function activeFor(StudentProfile $profile): ?StoragePickup
    {
        return $profile->storagePickups()->active()->latest()->first();
    }

    /**
     * Create a pickup request from student-supplied details. Guards eligibility,
     * the container's stage, and duplicate active requests, then notifies admins.
     *
     * @param  array{requested_pickup_date: string, pickup_location: string, contact_phone?: string|null, container_count?: int|null, notes?: string|null}  $data
     */
    public function requestForStudent(
        StudentProfile $profile,
        Container $container,
        array $data,
        ?User $actor = null,
    ): StoragePickup {
        if (! $this->eligibility->isEligible($profile)) {
            throw ValidationException::withMessages([
                'storage' => 'Storage is not included in your package. Add the summer storage add-on to schedule an end-of-year pickup.',
            ]);
        }

        if ($container->status !== ContainerStatus::DELIVERED_TO_DORM) {
            throw ValidationException::withMessages([
                'storage' => 'End-of-year pickup becomes available once your container has been delivered to your dorm.',
            ]);
        }

        if ($this->activeFor($profile) !== null) {
            throw ValidationException::withMessages([
                'storage' => 'You already have an end-of-year pickup in progress.',
            ]);
        }

        $pickup = DB::transaction(function () use ($profile, $container, $data): StoragePickup {
            /** @var StoragePickup $pickup */
            $pickup = $profile->storagePickups()->create([
                'container_id' => $container->id,
                'status' => StoragePickupStatus::REQUESTED,
                'requested_pickup_date' => $data['requested_pickup_date'],
                'pickup_location' => $data['pickup_location'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'container_count' => $data['container_count'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return $pickup;
        });

        $pickup->setRelation('studentProfile', $profile->loadMissing('user'));
        $pickup->setRelation('container', $container);

        $this->notifications->storagePickupRequestedByStudent($pickup, $actor);

        return $pickup;
    }

    /**
     * Admin update: advance the status, confirm a pickup date, and record notes.
     * Notifies the student whenever the status changes.
     *
     * @param  array{status: string, confirmed_pickup_date?: string|null, admin_notes?: string|null}  $data
     */
    public function updateByAdmin(StoragePickup $pickup, array $data, ?User $actor = null): StoragePickup
    {
        if (! StoragePickupStatus::isValid($data['status'])) {
            throw ValidationException::withMessages([
                'status' => 'Invalid storage pickup status.',
            ]);
        }

        $previousStatus = $pickup->status;
        $newStatus = $data['status'];

        $pickup->fill([
            'status' => $newStatus,
            'confirmed_pickup_date' => $data['confirmed_pickup_date'] ?? $pickup->confirmed_pickup_date,
            'admin_notes' => $data['admin_notes'] ?? $pickup->admin_notes,
        ]);

        // Stamp who confirmed the schedule the first time we leave "requested".
        if ($previousStatus === StoragePickupStatus::REQUESTED
            && $newStatus !== StoragePickupStatus::REQUESTED
            && $pickup->confirmed_at === null) {
            $pickup->confirmed_by_user_id = $actor?->id;
            $pickup->confirmed_at = Carbon::now();
        }

        $pickup->save();

        if ($previousStatus !== $newStatus) {
            $pickup->loadMissing('studentProfile.user');
            $this->notifications->storagePickupStatusChanged($pickup);
        }

        return $pickup;
    }

    /**
     * @return array<int, array{status: string, label: string, reached: bool, current: bool}>
     */
    public function timelineFor(StoragePickup $pickup): array
    {
        $currentIndex = StoragePickupStatus::orderIndex($pickup->status);
        $timeline = [];

        foreach (StoragePickupStatus::ordered() as $index => $status) {
            $timeline[] = [
                'status' => $status,
                'label' => StoragePickupStatus::label($status),
                'reached' => $index <= $currentIndex,
                'current' => $status === $pickup->status,
            ];
        }

        return $timeline;
    }
}

<?php

namespace App\Services;

use App\Enums\ContainerStatus;
use App\Models\Container;
use App\Models\ContainerStatusHistory;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContainerWorkflowService
{
    public function __construct(
        private readonly ContainerCodeGenerator $codeGenerator,
        private readonly NotificationService $notifications,
    ) {
    }

    public function createForStudent(
        StudentProfile $profile,
        ?User $actor = null,
    ): Container {
        return DB::transaction(function () use ($profile, $actor): Container {
            $container = Container::query()->create([
                'student_profile_id' => $profile->id,
                'code' => $this->codeGenerator->generate(),
                'status' => ContainerStatus::CONTAINER_PREPARED,
            ]);

            $this->recordHistory($container, null, ContainerStatus::CONTAINER_PREPARED, $actor, 'Container assigned');

            return $container;
        });
    }

    /**
     * Ensure the student has a single "move shipment" container. A package may
     * include multiple physical bins, but they ship and track together, so the
     * whole move is represented by one trackable record. Idempotent.
     */
    public function ensureMoveShipment(StudentProfile $profile, ?User $actor = null): Container
    {
        $existing = $profile->containers()->orderBy('id')->first();

        if ($existing instanceof Container) {
            return $existing;
        }

        return $this->createForStudent($profile, $actor);
    }

    public function transition(
        Container $container,
        string $toStatus,
        ?User $actor = null,
        ?string $note = null,
        bool $force = false,
    ): Container {
        if (!ContainerStatus::isValid($toStatus)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid container status.',
            ]);
        }

        $fromStatus = $container->status;

        if ($fromStatus === $toStatus) {
            return $container;
        }

        if (!$force && ContainerStatus::orderIndex($toStatus) < ContainerStatus::orderIndex($fromStatus)) {
            throw ValidationException::withMessages([
                'status' => 'Cannot move container to an earlier workflow stage without override.',
            ]);
        }

        $fresh = DB::transaction(function () use ($container, $fromStatus, $toStatus, $actor, $note): Container {
            $container->status = $toStatus;

            $this->applyAutomaticDates($container, $toStatus);

            $container->save();

            $this->recordHistory($container, $fromStatus, $toStatus, $actor, $note);

            return $container->fresh(['studentProfile.user']) ?? $container;
        });

        $this->notifications->containerStatusChanged($fresh, $toStatus);

        return $fresh;
    }

    public function primaryContainer(StudentProfile $profile): ?Container
    {
        return $profile->containers()->orderBy('id')->first();
    }

    /**
     * @return array<int, array{status: string, label: string, reached: bool, current: bool, reached_at: ?\Illuminate\Support\Carbon}>
     */
    public function timelineFor(Container $container): array
    {
        $currentIndex = ContainerStatus::orderIndex($container->status);

        if (!$container->relationLoaded('statusHistories')) {
            $container->load('statusHistories');
        }

        $reachedAtByStatus = $container->statusHistories
            ->groupBy('to_status')
            ->map(fn ($histories) => $histories->sortBy('created_at')->first()?->created_at);

        $timeline = [];

        foreach (ContainerStatus::ordered() as $index => $status) {
            $timeline[] = [
                'status' => $status,
                'label' => ContainerStatus::label($status),
                'reached' => $index <= $currentIndex,
                'current' => $status === $container->status,
                'reached_at' => $reachedAtByStatus->get($status),
            ];
        }

        return $timeline;
    }

    /**
     * Keep date fields in sync with workflow progress. When a container reaches
     * "Label Generated" and no ship-by date is set yet, default it from the
     * student's move-in date (when available) or a one-week lead time.
     */
    private function applyAutomaticDates(Container $container, string $toStatus): void
    {
        if ($toStatus === ContainerStatus::LABEL_GENERATED && $container->ship_by_date === null) {
            $moveInDate = $container->studentProfile->housingInfo?->move_in_date;

            $container->ship_by_date = $moveInDate !== null && $moveInDate->isFuture()
                ? $moveInDate->copy()->subDays(7)
                : now()->addDays(7);
        }
    }

    private function recordHistory(
        Container $container,
        ?string $fromStatus,
        string $toStatus,
        ?User $actor,
        ?string $note,
    ): void {
        ContainerStatusHistory::query()->create([
            'container_id' => $container->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $actor?->id,
            'note' => $note,
            'created_at' => now(),
        ]);
    }
}

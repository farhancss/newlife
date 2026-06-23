<?php

namespace App\Services;

use App\Enums\ContainerStatus;
use App\Enums\DeadlineStatus;
use App\Enums\DeadlineType;
use App\Enums\RetailPackageStatus;
use App\Models\Container;
use App\Models\Deadline;
use App\Models\RetailPackage;
use App\Models\StudentProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Owns the lifecycle of every student deadline: opening them when a triggering
 * event occurs, completing them when the criteria are met, flipping them to
 * overdue once the date passes, and sending one-time reminder / completion /
 * overdue notifications.
 */
class DeadlineService
{
    private const PROFILE_COMPLETION_DAYS = 7;
    private const CONTAINER_PICKUP_DAYS = 3;

    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    // ---------------------------------------------------------------------
    // Opening deadlines (case 01–04)
    // ---------------------------------------------------------------------

    /**
     * Case 01 — a freshly onboarded student must complete their profile within
     * seven days of being invited.
     */
    public function openProfileCompletion(StudentProfile $profile): ?Deadline
    {
        if ($profile->isOnboardingComplete()) {
            return null;
        }

        return $this->open(
            profile: $profile,
            subject: $profile,
            type: DeadlineType::PROFILE_COMPLETION,
            title: 'Complete your profile',
            description: 'Finish your onboarding profile so we can prepare your move.',
            dueAt: now()->addDays(self::PROFILE_COMPLETION_DAYS),
        );
    }

    /**
     * Case 02 / 04 — once a container is delivered to the student's home they
     * have three days to schedule a pickup. Add-on containers follow the same
     * rule but are tracked under their own type.
     */
    public function openContainerPickup(Container $container): ?Deadline
    {
        $profile = $container->studentProfile;

        if (! $profile instanceof StudentProfile) {
            return null;
        }

        $isAddOn = $container->isAddOn();

        return $this->open(
            profile: $profile,
            subject: $container,
            type: $isAddOn ? DeadlineType::ADDON_CONTAINER_PICKUP : DeadlineType::CONTAINER_PICKUP,
            title: 'Schedule your container pickup',
            description: "Container {$container->code} was delivered to your home. Pack it and schedule a pickup within "
                . self::CONTAINER_PICKUP_DAYS . ' days.',
            dueAt: now()->addDays(self::CONTAINER_PICKUP_DAYS),
        );
    }

    /**
     * Case 03 — a retail package with an estimated arrival date must reach the
     * hub by that date.
     */
    public function openRetailArrival(RetailPackage $package): ?Deadline
    {
        $profile = $package->studentProfile;

        if (! $profile instanceof StudentProfile || $package->estimated_arrival === null) {
            return null;
        }

        return $this->open(
            profile: $profile,
            subject: $package,
            type: DeadlineType::RETAIL_ARRIVAL,
            title: 'Retail package expected at hub',
            description: "{$package->retailer} — {$package->description} should arrive at the New Life hub by its estimated date.",
            dueAt: $package->estimated_arrival->copy()->endOfDay(),
        );
    }

    // ---------------------------------------------------------------------
    // Resolution
    // ---------------------------------------------------------------------

    /**
     * Re-evaluate any open deadlines attached to the given subject and complete
     * them immediately if their criteria are now satisfied. Called from the
     * domain workflows so students get instant feedback.
     */
    public function syncForSubject(Model $subject): void
    {
        Deadline::query()
            ->where('deadlinable_type', $subject->getMorphClass())
            ->where('deadlinable_id', $subject->getKey())
            ->where('status', '!=', DeadlineStatus::COMPLETED)
            ->get()
            ->each(function (Deadline $deadline) use ($subject): void {
                $deadline->setRelation('deadlinable', $subject);

                if ($subject instanceof StudentProfile) {
                    $deadline->setRelation('studentProfile', $subject);
                }

                if ($this->isSatisfied($deadline)) {
                    $this->markCompleted($deadline);
                }
            });
    }

    /**
     * Daily sweep: complete satisfied deadlines, flip past-due ones to overdue,
     * and send reminders for deadlines due within a day. Returns a summary of
     * how many of each action were performed.
     *
     * @return array{completed: int, overdue: int, reminded: int}
     */
    public function evaluate(?Carbon $now = null): array
    {
        $now ??= now();
        $summary = ['completed' => 0, 'overdue' => 0, 'reminded' => 0];

        Deadline::query()
            ->where('status', '!=', DeadlineStatus::COMPLETED)
            ->with(['deadlinable', 'studentProfile.user'])
            ->get()
            ->each(function (Deadline $deadline) use (&$summary, $now): void {
                if ($this->isSatisfied($deadline)) {
                    $this->markCompleted($deadline);
                    $summary['completed']++;

                    return;
                }

                if ($deadline->due_at->lt($now)) {
                    if ($this->markOverdue($deadline)) {
                        $summary['overdue']++;
                    }

                    return;
                }

                // Reminder window: within one day of the due date.
                if ($deadline->reminder_sent_at === null && $deadline->due_at->lte($now->copy()->addDay())) {
                    $this->sendReminder($deadline);
                    $summary['reminded']++;
                }
            });

        return $summary;
    }

    // ---------------------------------------------------------------------
    // Queries for the UI
    // ---------------------------------------------------------------------

    /**
     * @return Collection<int, Deadline>
     */
    public function forStudent(StudentProfile $profile): Collection
    {
        return $profile->deadlines()
            ->with('deadlinable')
            ->orderByRaw($this->statusOrderSql())
            ->orderBy('due_at')
            ->get();
    }

    /**
     * @return array{upcoming: Collection<int, Deadline>, overdue: Collection<int, Deadline>, completed: Collection<int, Deadline>}
     */
    public function groupedForStudent(StudentProfile $profile): array
    {
        $deadlines = $this->forStudent($profile);

        return [
            'upcoming' => $deadlines->filter(fn ($d) => $d->effectiveStatus() === DeadlineStatus::UPCOMING)->values(),
            'overdue' => $deadlines->filter(fn ($d) => $d->effectiveStatus() === DeadlineStatus::OVERDUE)->values(),
            'completed' => $deadlines->filter(fn ($d) => $d->effectiveStatus() === DeadlineStatus::COMPLETED)->values(),
        ];
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    private function open(
        StudentProfile $profile,
        Model $subject,
        string $type,
        string $title,
        string $description,
        Carbon $dueAt,
    ): Deadline {
        /** @var Deadline $deadline */
        $deadline = Deadline::query()->firstOrCreate(
            [
                'deadlinable_type' => $subject->getMorphClass(),
                'deadlinable_id' => $subject->getKey(),
                'type' => $type,
            ],
            [
                'student_profile_id' => $profile->id,
                'title' => $title,
                'description' => $description,
                'status' => DeadlineStatus::UPCOMING,
                'due_at' => $dueAt,
            ],
        );

        // Complete right away if it was somehow created already satisfied.
        $deadline->setRelation('deadlinable', $subject);

        if ($deadline->wasRecentlyCreated && $this->isSatisfied($deadline)) {
            $this->markCompleted($deadline);
        }

        return $deadline;
    }

    private function isSatisfied(Deadline $deadline): bool
    {
        return match ($deadline->type) {
            DeadlineType::PROFILE_COMPLETION => (bool) $deadline->studentProfile?->isOnboardingComplete(),
            DeadlineType::CONTAINER_PICKUP, DeadlineType::ADDON_CONTAINER_PICKUP => $this->containerPickupSatisfied($deadline),
            DeadlineType::RETAIL_ARRIVAL => $this->retailArrivalSatisfied($deadline),
            default => false,
        };
    }

    private function containerPickupSatisfied(Deadline $deadline): bool
    {
        $container = $deadline->deadlinable;

        if (! $container instanceof Container) {
            return false;
        }

        return ContainerStatus::orderIndex($container->status)
            >= ContainerStatus::orderIndex(ContainerStatus::PICKUP_SCHEDULED);
    }

    private function retailArrivalSatisfied(Deadline $deadline): bool
    {
        $package = $deadline->deadlinable;

        if (! $package instanceof RetailPackage) {
            return false;
        }

        return RetailPackageStatus::orderIndex($package->status)
            >= RetailPackageStatus::orderIndex(RetailPackageStatus::RECEIVED_AT_HUB);
    }

    private function markCompleted(Deadline $deadline): void
    {
        $alreadyCompleted = $deadline->status === DeadlineStatus::COMPLETED;

        $deadline->forceFill([
            'status' => DeadlineStatus::COMPLETED,
            'completed_at' => $deadline->completed_at ?? now(),
        ])->save();

        if (! $alreadyCompleted && $deadline->completed_notified_at === null) {
            $this->notifications->deadlineCompleted($deadline);
            $deadline->forceFill(['completed_notified_at' => now()])->save();
        }
    }

    private function markOverdue(Deadline $deadline): bool
    {
        $wasOverdue = $deadline->status === DeadlineStatus::OVERDUE;

        $deadline->forceFill(['status' => DeadlineStatus::OVERDUE])->save();

        if (! $wasOverdue && $deadline->overdue_notified_at === null) {
            $this->notifications->deadlineOverdue($deadline);
            $deadline->forceFill(['overdue_notified_at' => now()])->save();

            return true;
        }

        return false;
    }

    private function sendReminder(Deadline $deadline): void
    {
        $this->notifications->deadlineReminder($deadline);
        $deadline->forceFill(['reminder_sent_at' => now()])->save();
    }

    private function statusOrderSql(): string
    {
        // Overdue first, then upcoming, then completed.
        return sprintf(
            "CASE status WHEN '%s' THEN 0 WHEN '%s' THEN 1 WHEN '%s' THEN 2 ELSE 3 END",
            DeadlineStatus::OVERDUE,
            DeadlineStatus::UPCOMING,
            DeadlineStatus::COMPLETED,
        );
    }
}

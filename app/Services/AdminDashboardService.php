<?php

namespace App\Services;

use App\Enums\ContainerStatus;
use App\Enums\RetailPackageStatus;
use App\Models\Container;
use App\Models\ContainerStatusHistory;
use App\Models\RetailPackage;
use App\Models\RetailPackageStatusHistory;
use App\Models\StudentProfile;
use Illuminate\Support\Collection;

/**
 * Aggregates real operational metrics for the admin dashboard so every panel
 * reflects live data rather than static placeholders.
 */
class AdminDashboardService
{
    /**
     * @var list<string>
     */
    private const IN_TRANSIT_STATUSES = [
        ContainerStatus::SHIPPED_TO_HOME,
        ContainerStatus::RETURN_SHIPMENT_IN_TRANSIT,
        ContainerStatus::OUT_FOR_DELIVERY,
    ];

    /**
     * @var list<string>
     */
    private const PENDING_DELIVERY_STATUSES = [
        ContainerStatus::SCHEDULED_FOR_DORM_DELIVERY,
        ContainerStatus::OUT_FOR_DELIVERY,
    ];

    /**
     * @return list<array{label: string, value: int, trend: string}>
     */
    public function summaryCards(): array
    {
        $totalStudents = StudentProfile::query()->count();
        $newStudents = StudentProfile::query()->where('created_at', '>=', now()->subWeek())->count();

        $activeMoves = Container::query()->where('status', '!=', ContainerStatus::DELIVERED_TO_DORM)->count();
        $deliveredMoves = Container::query()->where('status', ContainerStatus::DELIVERED_TO_DORM)->count();

        $inTransit = Container::query()->whereIn('status', self::IN_TRANSIT_STATUSES)->count();
        $arrivingToday = Container::query()
            ->whereIn('status', self::IN_TRANSIT_STATUSES)
            ->whereDate('ship_by_date', today())
            ->count();

        $pendingContainers = Container::query()->whereIn('status', self::PENDING_DELIVERY_STATUSES)->count();
        $pendingPackages = RetailPackage::query()->where('status', RetailPackageStatus::STAGED_FOR_DELIVERY)->count();
        $dueThisWeek = Container::query()
            ->whereIn('status', self::PENDING_DELIVERY_STATUSES)
            ->whereBetween('ship_by_date', [today(), today()->addDays(7)])
            ->count();

        return [
            [
                'label' => 'Total Students',
                'value' => $totalStudents,
                'trend' => $newStudents > 0 ? "+{$newStudents} this week" : 'No new signups this week',
            ],
            [
                'label' => 'Active Moves',
                'value' => $activeMoves,
                'trend' => "{$deliveredMoves} completed",
            ],
            [
                'label' => 'Containers In Transit',
                'value' => $inTransit,
                'trend' => $arrivingToday > 0 ? "{$arrivingToday} arriving today" : 'None due today',
            ],
            [
                'label' => 'Pending Deliveries',
                'value' => $pendingContainers + $pendingPackages,
                'trend' => $dueThisWeek > 0 ? "{$dueThisWeek} due this week" : 'None due this week',
            ],
        ];
    }

    /**
     * Distribution of every move (container) across five high-level buckets,
     * with percentages for the donut chart.
     *
     * @return array{total: int, segments: list<array{label: string, count: int, percent: int, color: string}>}
     */
    public function moveStatusOverview(): array
    {
        /** @var array<string, int> $counts */
        $counts = Container::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $buckets = [
            ['label' => 'In Progress', 'color' => '#0827be', 'statuses' => [
                ContainerStatus::CONTAINER_PREPARED,
                ContainerStatus::LABEL_GENERATED,
            ]],
            ['label' => 'In Transit', 'color' => '#4f6bf3', 'statuses' => [
                ContainerStatus::SHIPPED_TO_HOME,
                ContainerStatus::RETURN_SHIPMENT_IN_TRANSIT,
            ]],
            ['label' => 'At Hub', 'color' => '#7b90f6', 'statuses' => [
                ContainerStatus::DELIVERED_TO_HOME,
                ContainerStatus::CUSTOMER_PACKING,
                ContainerStatus::PICKUP_SCHEDULED,
                ContainerStatus::RECEIVED_AT_NEW_LIFE_HUB,
                ContainerStatus::STORED_AT_RECEIVING_HUB,
            ]],
            ['label' => 'Out for Delivery', 'color' => '#a7b5f9', 'statuses' => [
                ContainerStatus::SCHEDULED_FOR_DORM_DELIVERY,
                ContainerStatus::OUT_FOR_DELIVERY,
            ]],
            ['label' => 'Delivered', 'color' => '#d3dafc', 'statuses' => [
                ContainerStatus::DELIVERED_TO_DORM,
            ]],
        ];

        $total = array_sum($counts);

        $segments = array_map(function (array $bucket) use ($counts, $total): array {
            $count = 0;
            foreach ($bucket['statuses'] as $status) {
                $count += $counts[$status] ?? 0;
            }

            return [
                'label' => $bucket['label'],
                'count' => $count,
                'percent' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
                'color' => $bucket['color'],
            ];
        }, $buckets);

        return [
            'total' => $total,
            'segments' => $segments,
        ];
    }

    /**
     * Unified, time-ordered feed of recent container, package, and signup events.
     *
     * @return list<array{activity: string, name: string, type: string, time: \Illuminate\Support\Carbon}>
     */
    public function recentActivity(int $limit = 10): array
    {
        $events = collect();

        ContainerStatusHistory::query()
            ->with('container.studentProfile.user')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->each(function (ContainerStatusHistory $history) use ($events): void {
                $student = $history->container?->studentProfile;

                $events->push([
                    'activity' => ContainerStatus::label($history->to_status),
                    'name' => $this->studentName($student),
                    'type' => 'Container',
                    'time' => $history->created_at,
                ]);
            });

        RetailPackageStatusHistory::query()
            ->with('retailPackage.studentProfile.user')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->each(function (RetailPackageStatusHistory $history) use ($events): void {
                $student = $history->retailPackage?->studentProfile;

                $events->push([
                    'activity' => 'Package ' . RetailPackageStatus::label($history->to_status),
                    'name' => $this->studentName($student),
                    'type' => 'Package',
                    'time' => $history->created_at,
                ]);
            });

        StudentProfile::query()
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get()
            ->each(function (StudentProfile $profile) use ($events): void {
                $events->push([
                    'activity' => $profile->isOnboardingComplete() ? 'Profile completed' : 'New student registered',
                    'name' => $this->studentName($profile),
                    'type' => 'Student',
                    'time' => $profile->isOnboardingComplete() && $profile->onboarding_completed_at
                        ? $profile->onboarding_completed_at
                        : $profile->created_at,
                ]);
            });

        return $events
            ->filter(fn (array $event): bool => $event['time'] !== null)
            ->sortByDesc('time')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Containers heading to dorms in the given window, or already staged/out.
     *
     * @return Collection<int, Container>
     */
    public function upcomingDeliveries(int $days = 7, int $limit = 8): Collection
    {
        return Container::query()
            ->with('studentProfile.user')
            ->where(function ($query) use ($days): void {
                $query->whereIn('status', self::PENDING_DELIVERY_STATUSES)
                    ->orWhereBetween('ship_by_date', [today(), today()->addDays($days)]);
            })
            ->orderByRaw('ship_by_date is null, ship_by_date asc')
            ->limit($limit)
            ->get();
    }

    private function studentName(?StudentProfile $profile): string
    {
        if ($profile === null) {
            return 'Unknown student';
        }

        $name = $profile->fullName();

        return $name !== '' ? $name : $profile->user->name;
    }
}

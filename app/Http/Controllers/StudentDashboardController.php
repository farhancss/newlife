<?php

namespace App\Http\Controllers;

use App\Enums\DeadlineStatus;
use App\Models\Container;
use App\Models\User;
use App\Services\DeadlineService;
use App\Services\MoveProgressService;
use App\Services\StudentPackageService;
use App\Services\StudentProfileService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentDashboardController extends Controller
{
    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly StudentPackageService $studentPackageService,
        private readonly MoveProgressService $moveProgressService,
        private readonly DeadlineService $deadlineService,
    ) {
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);
        $profile->load(['package', 'containers.statusHistories', 'housingInfo', 'retailPackages']);

        $primary = $profile->containers->sortBy('id')->first();
        $recentUpdates = $this->recentUpdates($profile);

        return view('pages.portal.student.dashboard', [
            'title' => 'Student Dashboard',
            'portal' => 'student',
            'pageHeading' => 'Dashboard',
            'profile' => $profile,
            'package' => $this->studentPackageService->resolve($profile),
            'dashboardSteps' => $this->moveProgressService->dashboardSteps($profile),
            'primaryContainer' => $primary,
            'recentUpdates' => $recentUpdates,
            'deadlines' => $this->dashboardDeadlines($profile),
        ]);
    }

    /**
     * The most recent container status changes across the student's shipments,
     * newest first, for the dashboard activity card.
     *
     * @return \Illuminate\Support\Collection<int, array{label: string, code: string, date: \Illuminate\Support\Carbon}>
     */
    private function recentUpdates(\App\Models\StudentProfile $profile): \Illuminate\Support\Collection
    {
        return $profile->containers
            ->flatMap(fn (Container $container) => $container->statusHistories->map(fn ($history) => [
                'label' => $history->toStatusLabel(),
                'code' => $container->code,
                'date' => $history->created_at,
            ]))
            ->sortByDesc('date')
            ->take(3)
            ->values();
    }

    /**
     * Active deadlines for the dashboard widget — overdue first, then the
     * soonest upcoming ones. Completed deadlines are omitted to keep the widget
     * focused on what still needs attention.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Deadline>
     */
    private function dashboardDeadlines(\App\Models\StudentProfile $profile): \Illuminate\Support\Collection
    {
        return $this->deadlineService->forStudent($profile)
            ->where('status', '!=', DeadlineStatus::COMPLETED)
            ->take(5)
            ->values();
    }
}

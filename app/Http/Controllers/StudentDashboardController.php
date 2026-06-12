<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\User;
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
    ) {
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);
        $profile->load(['package', 'containers.statusHistories', 'housingInfo', 'retailPackages']);

        $primary = $profile->containers->sortBy('id')->first();
        $latestUpdate = $primary instanceof Container
            ? $primary->statusHistories->first()
            : null;

        return view('pages.portal.student.dashboard', [
            'title' => 'Student Dashboard',
            'portal' => 'student',
            'pageHeading' => 'Dashboard',
            'profile' => $profile,
            'package' => $this->studentPackageService->resolve($profile),
            'dashboardSteps' => $this->moveProgressService->dashboardSteps($profile),
            'primaryContainer' => $primary,
            'latestUpdate' => $latestUpdate,
            'deadlines' => $this->deadlines($profile),
        ]);
    }

    /**
     * Derive the student's key dates from their move-in window. Deadlines lead
     * the move-in date so prep happens on time; when no date is set yet they
     * surface as "To be set".
     *
     * @return list<array{label: string, date: ?\Illuminate\Support\Carbon, done: bool}>
     */
    private function deadlines(\App\Models\StudentProfile $profile): array
    {
        $moveInDate = $profile->housingInfo?->move_in_date;

        return [
            [
                'label' => 'Profile Completion',
                'date' => $moveInDate?->copy()->subDays(30),
                'done' => $profile->isOnboardingComplete(),
            ],
            [
                'label' => 'Add Retail Packages',
                'date' => $moveInDate?->copy()->subDays(14),
                'done' => $profile->retailPackages->isNotEmpty(),
            ],
            [
                'label' => 'Move-in Window',
                'date' => $moveInDate,
                'done' => $moveInDate !== null,
            ],
        ];
    }
}

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
        $profile->load(['package', 'containers.statusHistories', 'housingInfo']);

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
        ]);
    }
}

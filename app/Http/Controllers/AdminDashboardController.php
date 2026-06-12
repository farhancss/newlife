<?php

namespace App\Http\Controllers;

use App\Services\AdminDashboardService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $dashboardService,
    ) {
    }

    public function index(): View
    {
        $overview = $this->dashboardService->moveStatusOverview();

        return view('pages.portal.admin.dashboard', [
            'title' => 'Admin Dashboard',
            'pageHeading' => 'Dashboard Overview',
            'portal' => 'admin',
            'summaryCards' => $this->dashboardService->summaryCards(),
            'moveOverview' => $overview,
            'recentActivity' => $this->dashboardService->recentActivity(),
            'upcomingDeliveries' => $this->dashboardService->upcomingDeliveries(),
        ]);
    }
}

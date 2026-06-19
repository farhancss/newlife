<?php

namespace App\Http\Controllers;

use App\Enums\AddOnStatus;
use App\Models\StudentAddOn;
use App\Services\ContainerWorkflowService;
use App\Services\FedExLinkService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAddOnController extends Controller
{
    public function __construct(
        private readonly ContainerWorkflowService $workflowService,
        private readonly FedExLinkService $fedExLinkService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $addOns = StudentAddOn::query()
            ->with(['studentProfile.user', 'container'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhereHas('studentProfile', function ($profileQuery) use ($search): void {
                            $profileQuery->where('new_life_id', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(AddOnStatus::isValid($status), fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        // Stats are computed across all purchases, independent of the current filter.
        $all = StudentAddOn::query()->get();
        $stats = [
            'total' => $all->count(),
            'active' => $all->where('status', AddOnStatus::ACTIVE)->count(),
            'trackable' => $all->whereNotNull('container_id')->count(),
            'revenue_cents' => (int) $all->where('status', AddOnStatus::ACTIVE)->sum('price_cents'),
        ];

        return view('pages.portal.admin.add-ons', [
            'title' => 'Add-Ons Management',
            'pageHeading' => 'Add-Ons',
            'portal' => 'admin',
            'addOns' => $addOns,
            'stats' => $stats,
            'search' => $search,
            'statusFilter' => $status,
            'statuses' => AddOnStatus::all(),
        ]);
    }

    public function show(StudentAddOn $studentAddOn): View
    {
        $studentAddOn->load([
            'studentProfile.user',
            'container.statusHistories.changedBy',
            'container.photos',
        ]);

        $container = $studentAddOn->container;
        $timeline = $container !== null ? $this->workflowService->timelineFor($container) : null;

        return view('pages.portal.admin.add-on-detail', [
            'title' => $studentAddOn->name . ' — Add-on',
            'pageHeading' => $studentAddOn->name,
            'portal' => 'admin',
            'addOn' => $studentAddOn,
            'container' => $container,
            'timeline' => $timeline,
            'fedExLinkService' => $this->fedExLinkService,
        ]);
    }
}

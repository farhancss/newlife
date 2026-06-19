<?php

namespace App\Http\Controllers;

use App\Enums\ContainerStatus;
use App\Enums\RetailPackageStatus;
use App\Models\StudentProfile;
use App\Services\ContainerWorkflowService;
use App\Services\FedExLinkService;
use App\Services\MoveProgressService;
use App\Services\ProfileCompletionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminStudentController extends Controller
{
    public function __construct(
        private readonly MoveProgressService $moveProgressService,
        private readonly ContainerWorkflowService $containerWorkflowService,
        private readonly ProfileCompletionService $profileCompletionService,
        private readonly FedExLinkService $fedExLinkService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $students = StudentProfile::query()
            ->with(['user', 'package', 'housingInfo', 'containers'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('new_life_id', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $rows = $students->map(function (StudentProfile $profile): array {
            $primary = $profile->containers
                ->where('source', \App\Models\Container::SOURCE_MOVE)
                ->sortBy('id')
                ->first();

            return [
                'profile' => $profile,
                'status' => $this->moveProgressService->currentLabel($profile),
                'eta' => $primary?->ship_by_date,
            ];
        });

        return view('pages.portal.admin.students', [
            'title' => 'Student Management',
            'pageHeading' => 'Students',
            'portal' => 'admin',
            'rows' => $rows,
            'search' => $search,
        ]);
    }

    public function show(StudentProfile $studentProfile): View
    {
        $studentProfile->load([
            'user',
            'package',
            'parentGuardian',
            'shippingAddress',
            'housingInfo',
            'containers.statusHistories.changedBy',
            'containers.photos',
            'retailPackages.statusHistories',
            'addOns.container',
        ]);

        $containers = $studentProfile->containers->sortBy('id')->values();

        $containerTimelines = [];
        foreach ($containers as $container) {
            $containerTimelines[$container->id] = [
                'steps' => $this->containerWorkflowService->timelineFor($container),
                'activeIndex' => ContainerStatus::orderIndex($container->status),
            ];
        }

        $activeRetailCount = $studentProfile->retailPackages
            ->where('status', '!=', RetailPackageStatus::DELIVERED_TO_DORM)
            ->count();

        $name = $studentProfile->fullName() ?: $studentProfile->user->name;

        return view('pages.portal.admin.student-detail', [
            'title' => $name . ' — Student Detail',
            'pageHeading' => $name,
            'portal' => 'admin',
            'profile' => $studentProfile,
            'currentStage' => $this->moveProgressService->currentLabel($studentProfile),
            'completion' => $this->profileCompletionService->summary($studentProfile),
            'containers' => $containers,
            'containerTimelines' => $containerTimelines,
            'activeRetailCount' => $activeRetailCount,
            'addOns' => $studentProfile->addOns,
            'fedExLinkService' => $this->fedExLinkService,
        ]);
    }
}

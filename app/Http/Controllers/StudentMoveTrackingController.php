<?php

namespace App\Http\Controllers;

use App\Enums\ContainerStatus;
use App\Models\Container;
use App\Models\User;
use App\Services\ContainerWorkflowService;
use App\Services\FedExLinkService;
use App\Services\MoveProgressService;
use App\Services\StudentPackageService;
use App\Services\StudentProfileService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class StudentMoveTrackingController extends Controller
{
    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly ContainerWorkflowService $workflowService,
        private readonly FedExLinkService $fedExLinkService,
        private readonly StudentPackageService $studentPackageService,
        private readonly MoveProgressService $moveProgressService,
    ) {
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);
        $profile->load(['shippingAddress', 'package', 'containers.statusHistories', 'containers.photos']);

        $package = $this->studentPackageService->resolve($profile);
        $containers = $profile->containers->sortBy('id')->values();
        $primary = $containers->first();
        $timeline = $primary instanceof Container
            ? $this->workflowService->timelineFor($primary)
            : $this->defaultTimeline();
        $containerAllowance = $this->studentPackageService->containerAllowance($profile);

        return view('pages.portal.student.move-tracking', [
            'title' => 'Move Tracking',
            'portal' => 'student',
            'profile' => $profile,
            'package' => $package,
            'containers' => $containers,
            'primaryContainer' => $primary,
            'containerAllowance' => $containerAllowance,
            'timeline' => $timeline,
            'dashboardSteps' => $this->moveProgressService->dashboardSteps($profile),
            'statuses' => ContainerStatus::ordered(),
            'outboundTrackingUrl' => $primary
                ? $this->fedExLinkService->trackingUrl($primary->outbound_tracking)
                : null,
            'returnTrackingUrl' => $primary
                ? $this->fedExLinkService->trackingUrl($primary->return_tracking)
                : null,
            'showPickupInstructions' => $primary && in_array($primary->status, [
                ContainerStatus::DELIVERED_TO_HOME,
                ContainerStatus::CUSTOMER_PACKING,
            ], true),
            'fedExLinkService' => $this->fedExLinkService,
        ]);
    }

    /**
     * @return array<int, array{status: string, label: string, reached: bool, current: bool}>
     */
    private function defaultTimeline(): array
    {
        $timeline = [];

        foreach (ContainerStatus::ordered() as $status) {
            $timeline[] = [
                'status' => $status,
                'label' => ContainerStatus::label($status),
                'reached' => false,
                'current' => false,
            ];
        }

        return $timeline;
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\ContainerStatus;
use App\Models\Container;
use App\Models\User;
use App\Services\ContainerWorkflowService;
use App\Services\FedExLinkService;
use App\Services\MoveProgressService;
use App\Services\NotificationService;
use App\Services\StudentPackageService;
use App\Services\StudentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentMoveTrackingController extends Controller
{
    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly ContainerWorkflowService $workflowService,
        private readonly FedExLinkService $fedExLinkService,
        private readonly StudentPackageService $studentPackageService,
        private readonly MoveProgressService $moveProgressService,
        private readonly NotificationService $notifications,
    ) {
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);
        $profile->load(['shippingAddress', 'package', 'containers.statusHistories', 'containers.photos']);

        $package = $this->studentPackageService->resolve($profile);
        $containers = $profile->containers
            ->where('source', Container::SOURCE_MOVE)
            ->sortBy('id')
            ->values();
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
            'showPickupInstructions' => $primary && $primary->status === ContainerStatus::CUSTOMER_PACKING,
            'pickupPhotosUploaded' => $primary instanceof Container && $primary->photos->isNotEmpty(),
            'fedExLinkService' => $this->fedExLinkService,
        ]);
    }

    /**
     * Student-initiated transition from "Student Packing" to "Pickup Scheduled".
     * Only available once the student has documented the container with at least
     * one photo. Notifies site admins and records the action in history.
     */
    public function schedulePickup(Container $container): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $user->studentProfile;

        abort_unless($profile !== null && $container->student_profile_id === $profile->id, Response::HTTP_FORBIDDEN);

        if ($container->status !== ContainerStatus::CUSTOMER_PACKING) {
            return back()->withErrors([
                'pickup' => 'A pickup can only be requested while your container is in the packing stage.',
            ]);
        }

        if ($container->photos()->count() === 0) {
            return back()->withErrors([
                'pickup' => 'Please upload at least one container photo before requesting a pickup.',
            ]);
        }

        $container = $this->workflowService->transition(
            $container,
            ContainerStatus::PICKUP_SCHEDULED,
            $user,
            'Student confirmed packing complete and requested a pickup.',
        );

        $this->notifications->containerPickupRequestedByStudent($container, $user);

        return back()->with('status', 'Pickup requested. Our team has been notified and will confirm your pickup shortly.');
    }

    /**
     * @return array<int, array{status: string, label: string, reached: bool, current: bool, reached_at: null}>
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
                'reached_at' => null,
            ];
        }

        return $timeline;
    }
}

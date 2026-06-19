<?php

namespace App\Http\Controllers;

use App\Models\StudentAddOn;
use App\Models\User;
use App\Services\AddOnService;
use App\Services\ContainerWorkflowService;
use App\Services\FedExLinkService;
use App\Services\StudentProfileService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class StudentAddOnController extends Controller
{
    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly AddOnService $addOnService,
        private readonly ContainerWorkflowService $workflowService,
        private readonly FedExLinkService $fedExLinkService,
    ) {
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);

        return view('pages.portal.student.add-ons', [
            'title' => 'Add-Ons',
            'portal' => 'student',
            'profile' => $profile,
            'catalog' => $this->addOnService->catalog(),
            'purchases' => $this->addOnService->purchasesFor($profile),
        ]);
    }

    public function show(StudentAddOn $studentAddOn): View
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);

        abort_unless($studentAddOn->student_profile_id === $profile->id, Response::HTTP_FORBIDDEN);

        $catalogEntry = $this->addOnService->findInCatalog($studentAddOn->add_on_slug);

        $studentAddOn->load(['container.statusHistories', 'container.photos']);
        $container = $studentAddOn->container;

        $timeline = $container !== null ? $this->workflowService->timelineFor($container) : null;

        return view('pages.portal.student.add-on-detail', [
            'title' => $studentAddOn->name,
            'portal' => 'student',
            'addOn' => $studentAddOn,
            'catalogEntry' => $catalogEntry,
            'container' => $container,
            'timeline' => $timeline,
            'fedExLinkService' => $this->fedExLinkService,
        ]);
    }
}

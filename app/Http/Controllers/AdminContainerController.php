<?php

namespace App\Http\Controllers;

use App\Enums\ContainerStatus;
use App\Http\Requests\Admin\UpdateContainerRequest;
use App\Models\Container;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ContainerWorkflowService;
use App\Services\FedExLinkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminContainerController extends Controller
{
    public function __construct(
        private readonly ContainerWorkflowService $workflowService,
        private readonly FedExLinkService $fedExLinkService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $containers = Container::query()
            ->with(['studentProfile.user', 'studentProfile.package'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('outbound_tracking', 'like', "%{$search}%")
                        ->orWhere('return_tracking', 'like', "%{$search}%")
                        ->orWhereHas('studentProfile', function ($profileQuery) use ($search): void {
                            $profileQuery->where('new_life_id', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('updated_at')
            ->get();

        $editing = null;
        if ($request->filled('edit')) {
            $editing = Container::query()
                ->with(['studentProfile.user', 'statusHistories.changedBy', 'photos.uploadedBy'])
                ->find($request->integer('edit'));
        }

        $studentsLoaded = StudentProfile::query()->count();

        return view('pages.portal.admin.containers', [
            'title' => 'Container Management',
            'pageHeading' => 'Containers',
            'portal' => 'admin',
            'containers' => $containers,
            'studentsLoaded' => $studentsLoaded,
            'editing' => $editing,
            'search' => $search,
            'statuses' => ContainerStatus::ordered(),
            'fedExLinkService' => $this->fedExLinkService,
        ]);
    }

    public function update(UpdateContainerRequest $request, Container $container): RedirectResponse
    {
        /** @var User|null $actor */
        $actor = Auth::user();
        $validated = $request->validated();

        $this->workflowService->transition(
            $container,
            $validated['status'],
            $actor,
            $validated['status_note'] ?? null,
            (bool) ($validated['force_status'] ?? false),
        );

        $container->fill([
            'location' => $validated['location'] ?? $container->location,
            'outbound_tracking' => $validated['outbound_tracking'] ?? $container->outbound_tracking,
            'return_tracking' => $validated['return_tracking'] ?? $container->return_tracking,
            'ship_by_date' => $validated['ship_by_date'] ?? $container->ship_by_date,
            'internal_notes' => $validated['internal_notes'] ?? $container->internal_notes,
        ]);
        $container->save();

        return redirect()
            ->route('admin.containers', ['edit' => $container->id])
            ->with('status', 'Container updated.');
    }
}

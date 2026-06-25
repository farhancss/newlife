<?php

namespace App\Http\Controllers;

use App\Enums\StoragePickupStatus;
use App\Http\Requests\Admin\UpdateStoragePickupRequest;
use App\Models\StoragePickup;
use App\Models\User;
use App\Services\StoragePickupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminStoragePickupController extends Controller
{
    public function __construct(
        private readonly StoragePickupService $storagePickupService,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $pickups = StoragePickup::query()
            ->with(['studentProfile.user', 'container'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('pickup_location', 'like', "%{$search}%")
                        ->orWhereHas('studentProfile', function ($profileQuery) use ($search): void {
                            $profileQuery->where('new_life_id', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(StoragePickupStatus::isValid($status), fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = '" . StoragePickupStatus::REQUESTED . "' THEN 0 ELSE 1 END")
            ->orderBy('requested_pickup_date')
            ->get();

        $all = StoragePickup::query()->get();
        $stats = [
            'total' => $all->count(),
            'requested' => $all->where('status', StoragePickupStatus::REQUESTED)->count(),
            'in_storage' => $all->where('status', StoragePickupStatus::IN_STORAGE)->count(),
            'returned' => $all->where('status', StoragePickupStatus::RETURNED)->count(),
        ];

        $editing = null;
        if ($request->filled('edit')) {
            $editing = StoragePickup::query()
                ->with(['studentProfile.user', 'container', 'confirmedBy'])
                ->find($request->integer('edit'));
        }

        return view('pages.portal.admin.storage-pickups', [
            'title' => 'End-of-Year Storage Pickups',
            'pageHeading' => 'Storage Pickups',
            'portal' => 'admin',
            'pickups' => $pickups,
            'stats' => $stats,
            'search' => $search,
            'statusFilter' => $status,
            'statuses' => StoragePickupStatus::all(),
            'editing' => $editing,
        ]);
    }

    public function update(UpdateStoragePickupRequest $request, StoragePickup $storagePickup): RedirectResponse
    {
        /** @var User|null $actor */
        $actor = Auth::user();

        $this->storagePickupService->updateByAdmin($storagePickup, $request->validated(), $actor);

        $student = $storagePickup->studentProfile->fullName() ?: $storagePickup->studentProfile->user?->name;

        return redirect()
            ->route('admin.storage-pickups')
            ->with('status', "Storage pickup for {$student} updated — status is now {$storagePickup->statusLabel()}.");
    }
}

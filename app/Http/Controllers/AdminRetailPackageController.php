<?php

namespace App\Http\Controllers;

use App\Enums\RetailPackageStatus;
use App\Http\Requests\Admin\RemoveRetailPackageRequest;
use App\Http\Requests\Admin\StoreRetailPackageRequest;
use App\Http\Requests\Admin\UpdateRetailPackageStatusRequest;
use App\Models\RetailPackage;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\CarrierLinkBuilder;
use App\Services\RetailPackageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminRetailPackageController extends Controller
{
    public function __construct(
        private readonly RetailPackageService $retailPackageService,
        private readonly CarrierLinkBuilder $carrierLinkBuilder,
    ) {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $retailer = (string) $request->query('retailer', '');

        $packages = RetailPackage::query()
            ->with(['studentProfile.user'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('tracking_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('studentProfile', function ($profileQuery) use ($search): void {
                            $profileQuery->where('new_life_id', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(RetailPackageStatus::isValid($status), fn ($query) => $query->where('status', $status))
            ->when($retailer !== '', fn ($query) => $query->where('retailer', $retailer))
            ->orderByDesc('updated_at')
            ->get();

        $editing = null;
        if ($request->filled('edit')) {
            $editing = RetailPackage::query()
                ->with(['studentProfile.user', 'statusHistories.changedBy'])
                ->find($request->integer('edit'));
        }

        return view('pages.portal.admin.retail-packages', [
            'title' => 'Retail Package Management',
            'pageHeading' => 'Retail Packages',
            'portal' => 'admin',
            'packages' => $packages,
            'editing' => $editing,
            'search' => $search,
            'statusFilter' => $status,
            'retailerFilter' => $retailer,
            'statuses' => RetailPackageStatus::ordered(),
            'retailers' => config('portal.retail_packages.retailers', []),
            'students' => StudentProfile::query()->orderBy('first_name')->get(),
            'carrierLinkBuilder' => $this->carrierLinkBuilder,
        ]);
    }

    public function store(StoreRetailPackageRequest $request): RedirectResponse
    {
        /** @var User|null $actor */
        $actor = Auth::user();
        $validated = $request->validated();

        $profile = StudentProfile::query()->findOrFail($validated['student_profile_id']);

        $this->retailPackageService->create($profile, $validated, $actor);

        return redirect()
            ->route('admin.retail-packages')
            ->with('status', 'Package added on behalf of the student.');
    }

    public function update(UpdateRetailPackageStatusRequest $request, RetailPackage $retailPackage): RedirectResponse
    {
        /** @var User|null $actor */
        $actor = Auth::user();
        $validated = $request->validated();

        $this->retailPackageService->transition(
            $retailPackage,
            $validated['status'],
            $actor,
            $validated['status_note'] ?? null,
            (bool) ($validated['force_status'] ?? false),
        );

        return redirect()
            ->route('admin.retail-packages', ['edit' => $retailPackage->id])
            ->with('status', 'Package status updated.');
    }

    public function destroy(RemoveRetailPackageRequest $request, RetailPackage $retailPackage): RedirectResponse
    {
        $this->retailPackageService->delete($retailPackage, $request->validated()['removed_reason']);

        return redirect()
            ->route('admin.retail-packages')
            ->with('status', 'Package removed.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreRetailPackageRequest;
use App\Http\Requests\Student\UpdateRetailPackageRequest;
use App\Models\RetailPackage;
use App\Models\User;
use App\Services\CarrierLinkBuilder;
use App\Services\RetailEligibilityService;
use App\Services\RetailPackageService;
use App\Services\StudentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StudentRetailPackageController extends Controller
{
    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly RetailPackageService $retailPackageService,
        private readonly RetailEligibilityService $retailEligibility,
        private readonly CarrierLinkBuilder $carrierLinkBuilder,
    ) {
    }

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);

        $packages = $profile->retailPackages()->get();

        $eligible = $this->retailEligibility->isEligible($profile);
        $cap = $this->retailEligibility->maxPackages($profile);
        $purchasedCount = $this->retailPackageService->purchasedCount($profile);

        $editing = null;
        if ($eligible && $request->filled('edit')) {
            $editing = $profile->retailPackages()->find($request->integer('edit'));
        }

        return view('pages.portal.student.retail-packages', [
            'title' => 'Retail Packages',
            'portal' => 'student',
            'profile' => $profile,
            'packages' => $packages,
            'editing' => $editing,
            'showForm' => $eligible && ($request->boolean('add') || $editing !== null),
            'retailers' => config('portal.retail_packages.retailers', []),
            'eligible' => $eligible,
            'eligibilityReason' => $this->retailEligibility->reason($profile),
            'cap' => $cap,
            'purchasedCount' => $purchasedCount,
            'atCap' => $cap > 0 && $purchasedCount >= $cap,
            'acknowledged' => $profile->hasAcknowledgedRetailTerms(),
            'carrierLinkBuilder' => $this->carrierLinkBuilder,
        ]);
    }

    public function store(StoreRetailPackageRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);

        $this->retailPackageService->create($profile, $request->validated(), $user);

        return redirect()
            ->route('student.retail-packages')
            ->with('status', 'Package logged. We will update you as it moves.');
    }

    public function update(UpdateRetailPackageRequest $request, RetailPackage $retailPackage): RedirectResponse
    {
        Gate::authorize('update', $retailPackage);

        $this->retailPackageService->update($retailPackage, $request->validated());

        return redirect()
            ->route('student.retail-packages')
            ->with('status', 'Package updated.');
    }

    public function destroy(RetailPackage $retailPackage): RedirectResponse
    {
        Gate::authorize('delete', $retailPackage);

        $this->retailPackageService->delete($retailPackage);

        return redirect()
            ->route('student.retail-packages')
            ->with('status', 'Package removed.');
    }
}

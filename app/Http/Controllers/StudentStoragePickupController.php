<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreStoragePickupRequest;
use App\Models\Container;
use App\Models\User;
use App\Services\StoragePickupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentStoragePickupController extends Controller
{
    public function __construct(
        private readonly StoragePickupService $storagePickupService,
    ) {
    }

    /**
     * Student schedules an end-of-year dorm pickup for their delivered move
     * container. Eligibility and stage are enforced by the service.
     */
    public function store(StoreStoragePickupRequest $request, Container $container): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $profile = $user->studentProfile;

        abort_unless($profile !== null && $container->student_profile_id === $profile->id, Response::HTTP_FORBIDDEN);

        $this->storagePickupService->requestForStudent(
            $profile,
            $container,
            $request->validated(),
            $user,
        );

        return back()->with('status', 'End-of-year pickup requested. Our team has been notified and will confirm your pickup date shortly.');
    }
}

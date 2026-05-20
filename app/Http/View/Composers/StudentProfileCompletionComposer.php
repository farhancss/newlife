<?php

namespace App\Http\View\Composers;

use App\Enums\UserRole;
use App\Services\ProfileCompletionService;
use App\Services\StudentProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class StudentProfileCompletionComposer
{
    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly ProfileCompletionService $profileCompletionService,
    ) {
    }

    public function compose(View $view): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== UserRole::STUDENT) {
            $view->with('profileCompletion', null);

            return;
        }

        $profile = $this->studentProfileService->ensureForUser($user);
        $completion = $this->profileCompletionService->summary($profile);

        $view->with('profileCompletion', $completion);
    }
}

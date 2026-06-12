<?php

namespace App\Http\View\Composers;

use App\Enums\UserRole;
use App\Services\NotificationService;
use App\Services\ProfileCompletionService;
use App\Services\StudentProfileService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class StudentProfileCompletionComposer
{
    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly ProfileCompletionService $profileCompletionService,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function compose(View $view): void
    {
        $user = Auth::user();

        if (!$user || $user->role !== UserRole::STUDENT) {
            $view->with('profileCompletion', null);
            $view->with('notificationUnreadCount', 0);

            return;
        }

        $profile = $this->studentProfileService->ensureForUser($user);
        $completion = $this->profileCompletionService->summary($profile);

        $view->with('profileCompletion', $completion);
        $view->with('notificationUnreadCount', $this->notificationService->unreadCount($user));
    }
}

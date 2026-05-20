<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Services\ProfileCompletionService;
use App\Services\StudentProfileService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    /** @var list<string> */
    private array $exceptRouteNames = [
        'student.profile',
        'student.profile.update',
        'student.onboarding',
        'student.onboarding.submit',
        'student.change-password',
        'student.change-password.submit',
        'logout',
    ];

    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly ProfileCompletionService $profileCompletionService,
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (in_array($routeName, $this->exceptRouteNames, true)) {
            return $next($request);
        }

        if ($user && $user->role === UserRole::STUDENT) {
            $profile = $this->studentProfileService->ensureForUser($user);

            if (!$this->profileCompletionService->isComplete($profile)) {
                return redirect()
                    ->route('student.profile')
                    ->with('warning', 'Please complete your profile to access the portal.');
            }
        }

        return $next($request);
    }
}

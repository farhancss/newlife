<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\UpdateProfileSectionRequest;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\OnboardingService;
use App\Services\ProfileCompletionService;
use App\Services\StudentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentProfileController extends Controller
{
    public function __construct(
        private readonly StudentProfileService $studentProfileService,
        private readonly ProfileCompletionService $profileCompletionService,
        private readonly OnboardingService $onboardingService,
    ) {
    }

    public function show(Request $request): View
    {
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);
        $profile->load(['parentGuardian', 'shippingAddress', 'housingInfo', 'package']);

        $completion = $this->profileCompletionService->summary($profile);
        $requestedSection = $request->filled('section') ? $request->integer('section') : null;
        $activeSection = $this->profileCompletionService->resolveActiveSection($profile, $requestedSection);

        return view('pages.portal.student.profile', [
            'title' => 'My Profile',
            'pageHeading' => 'My Profile',
            'portal' => 'student',
            'profile' => $profile,
            'user' => $user,
            'completion' => $completion,
            'activeSection' => $activeSection,
        ]);
    }

    public function update(UpdateProfileSectionRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);
        $profile->load(['parentGuardian', 'shippingAddress', 'housingInfo', 'package']);

        $section = $request->resolvedSection();
        $action = $request->resolvedAction();
        $validated = $request->sectionData();

        return $this->handleSave(
            user: $user,
            profile: $profile,
            section: $section,
            validated: $validated,
            advance: $action === 'next',
        );
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function handleSave(
        User $user,
        StudentProfile $profile,
        int $section,
        array $validated,
        bool $advance,
    ): RedirectResponse {
        $hasChanges = $this->onboardingService->hasSectionChanges($profile, $section, $validated);

        if ($hasChanges) {
            $this->onboardingService->saveStep($user, $section, $validated);
            $profile = $profile->fresh(['parentGuardian', 'shippingAddress', 'housingInfo']);
        }

        $this->profileCompletionService->syncCompletionStatus($profile, preserveStep: true);
        $completion = $this->profileCompletionService->summary($profile->fresh());

        if ($advance && $completion['is_complete']) {
            $profile->onboarding_step = 5;
            $profile->save();

            $redirect = redirect()->route('student.dashboard');

            return $hasChanges
                ? $redirect->with('status', 'Your profile is complete. Welcome to your portal!')
                : $redirect;
        }

        $nextSection = $advance
            ? $this->profileCompletionService->nextIncompleteSection($profile, $section)
            : null;

        if ($advance && $nextSection !== null) {
            $profile->onboarding_step = $nextSection;
            $profile->save();

            $redirect = redirect()->route('student.profile', ['section' => $nextSection]);

            return $hasChanges
                ? $redirect->with('status', 'Your changes have been saved.')
                : $redirect;
        }

        if ($advance && !$completion['is_complete'] && $section === 4) {
            return redirect()
                ->route('student.profile', ['section' => 4])
                ->with('warning', 'Please complete all required fields in the university dorm section.');
        }

        if ($advance && $nextSection === null && !$completion['is_complete']) {
            $fallbackSection = $completion['next_section'] ?? $section;
            $profile->onboarding_step = $fallbackSection;
            $profile->save();

            $redirect = redirect()->route('student.profile', ['section' => $fallbackSection]);

            return $hasChanges
                ? $redirect->with('status', 'Your changes have been saved.')
                : $redirect;
        }

        $redirect = redirect()->route('student.profile', ['section' => $section]);

        if (!$hasChanges) {
            return $redirect;
        }

        return $completion['is_complete']
            ? $redirect->with('status', 'Profile updated successfully.')
            : $redirect->with('status', 'Your changes have been saved.');
    }
}

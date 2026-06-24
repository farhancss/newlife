<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\UpdateProfileSectionRequest;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\OnboardingService;
use App\Services\ProfileCompletionService;
use App\Services\StudentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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

    public function updateAvatar(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $disk = (string) config('portal.avatars.disk', 'public');
        $maxKb = (int) config('portal.avatars.max_size_kb', 4096);
        /** @var list<string> $allowed */
        $allowed = (array) config('portal.avatars.allowed_mimes', ['jpeg', 'jpg', 'png', 'webp']);

        $request->validate([
            'avatar' => ['required', 'image', 'mimes:' . implode(',', $allowed), 'max:' . $maxKb],
        ]);

        $previous = $user->avatar_path;
        $path = $request->file('avatar')->store("avatars/{$user->id}", $disk);

        $user->forceFill(['avatar_path' => $path])->save();

        if ($previous !== null && $previous !== '' && $previous !== $path) {
            Storage::disk($disk)->delete($previous);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profile photo updated.',
                'avatar_url' => $user->fresh()->avatarUrl(),
            ]);
        }

        return redirect()
            ->route('student.profile', ['section' => 1])
            ->with('status', 'Profile photo updated.');
    }

    public function destroyAvatar(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->avatar_path !== null && $user->avatar_path !== '') {
            Storage::disk((string) config('portal.avatars.disk', 'public'))->delete($user->avatar_path);
            $user->forceFill(['avatar_path' => null])->save();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Profile photo removed.',
                'avatar_url' => null,
            ]);
        }

        return redirect()
            ->route('student.profile', ['section' => 1])
            ->with('status', 'Profile photo removed.');
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

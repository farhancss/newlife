<?php

namespace App\Http\Controllers;

use App\Services\OnboardingService;
use App\Services\StudentProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly StudentProfileService $studentProfileService,
    ) {
    }

    public function show(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);
        $completion = app(\App\Services\ProfileCompletionService::class)->summary($profile);

        if ($completion['is_complete']) {
            return redirect()->route('student.dashboard');
        }

        return redirect()->route('student.profile');
    }

    public function submit(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = $this->studentProfileService->ensureForUser($user);

        $step = (int) $request->input('step', 1);
        $action = $request->input('action', 'next');

        $validated = $this->validateStep($request, $step);
        $this->onboardingService->saveStep($user, $step, $validated);

        app(\App\Services\ProfileCompletionService::class)->syncCompletionStatus($profile->fresh());
        $completion = app(\App\Services\ProfileCompletionService::class)->summary($profile->fresh());

        if ($completion['is_complete']) {
            return redirect()
                ->route('student.dashboard')
                ->with('status', 'Profile completed. Welcome to your portal!');
        }

        return redirect()->route('student.profile');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStep(Request $request, int $step): array
    {
        return match ($step) {
            1 => $request->validate([
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['required', 'string', 'max:100'],
                'phone' => ['required', 'string', 'max:30'],
                'school' => ['required', 'string', 'max:150'],
                'incoming_year' => ['required', 'string', 'max:20'],
            ]),
            2 => $request->validate([
                'parent_name' => ['required', 'string', 'max:150'],
                'parent_email' => ['required', 'email', 'max:255'],
                'parent_phone' => ['required', 'string', 'max:30'],
                'parent_relationship' => ['required', 'string', 'max:50'],
            ]),
            3 => $request->validate([
                'line1' => ['required', 'string', 'max:255'],
                'line2' => ['nullable', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:100'],
                'region' => ['required', 'string', 'max:100'],
                'postal_code' => ['required', 'string', 'max:20'],
                'country_code' => ['nullable', 'string', 'size:2'],
                'shipping_notes' => ['nullable', 'string', 'max:1000'],
            ]),
            4 => $request->validate([
                'university' => ['required', 'string', 'max:150'],
                'residence_hall' => ['required', 'string', 'max:150'],
                'building' => ['nullable', 'string', 'max:100'],
                'room' => ['nullable', 'string', 'max:50'],
                'move_in_date' => ['required', 'date'],
                'move_in_window' => ['nullable', 'string', 'max:100'],
                'delivery_notes' => ['nullable', 'string', 'max:1000'],
            ]),
            default => [],
        };
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ChangePasswordRequest;
use App\Mail\PasswordChangedMail;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ProfileCompletionService;
use App\Services\UserStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function __construct(
        private readonly UserStatusService $userStatusService,
        private readonly ProfileCompletionService $profileCompletionService,
    ) {
    }

    public function show(Request $request): View
    {
        $portal = str_starts_with((string) $request->route()?->getName(), 'admin.') ? 'admin' : 'student';

        return view('pages.portal.common.change-password', [
            'title' => 'Change Password',
            'portal' => $portal,
            'mustReset' => (bool) Auth::user()?->must_reset_password,
        ]);
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $portal = $user->role === UserRole::ADMIN ? 'admin' : 'student';
        $mustReset = $request->mustResetPassword();

        $validated = $request->validated();

        if (!$mustReset && !Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->password = Hash::make($validated['password']);
        $user->must_reset_password = false;
        $user->password_changed_at = now();
        $user->save();

        if ($mustReset) {
            $this->userStatusService->markPasswordChanged($user);
        }

        Mail::to($user->email)->queue(new PasswordChangedMail($user, wasFirstReset: $mustReset));

        if ($portal === 'student') {
            return $this->redirectStudentAfterUpdate($user);
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Password updated successfully.');
    }

    private function redirectStudentAfterUpdate(User $user): RedirectResponse
    {
        $profile = $user->studentProfile;

        if (!$profile instanceof StudentProfile) {
            return redirect()
                ->route('student.profile')
                ->with('status', 'Password updated. Please complete your profile.');
        }

        $completion = $this->profileCompletionService->summary($profile);

        if (!$completion['is_complete']) {
            return redirect()
                ->route('student.profile')
                ->with('status', 'Password updated. Please complete your profile.');
        }

        return redirect()
            ->route('student.dashboard')
            ->with('status', 'Password updated successfully.');
    }
}

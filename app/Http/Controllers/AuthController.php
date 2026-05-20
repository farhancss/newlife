<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ProfileCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_DECAY_SECONDS = 60;

    public function __construct(
        private readonly ProfileCompletionService $profileCompletionService,
    ) {
    }

    public function showLogin(): View
    {
        return view('pages.portal.login', [
            'title' => 'Login',
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey($credentials['email'], $request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return redirect()
                ->route('login')
                ->withInput(['email' => $credentials['email']])
                ->withErrors([
                    'login' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ]);
        }

        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user && $user->isSuspended()) {
            RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

            return redirect()
                ->route('login')
                ->withInput(['email' => $credentials['email']])
                ->withErrors([
                    'login' => 'This account has been suspended. Please contact support.',
                ]);
        }

        if (!Auth::attempt($credentials)) {
            RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

            return redirect()
                ->route('login')
                ->withInput(['email' => $credentials['email']])
                ->withErrors([
                    'login' => 'The provided credentials are incorrect.',
                ]);
        }

        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();

        $authenticated = Auth::user();

        if ($authenticated->role === UserRole::ADMIN) {
            return redirect()->route('admin.dashboard');
        }

        return $this->redirectStudentAfterLogin($authenticated);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectStudentAfterLogin(User $user): RedirectResponse
    {
        if ($user->must_reset_password) {
            return redirect()->route('student.change-password');
        }

        $profile = $user->studentProfile;

        if (!$profile instanceof StudentProfile) {
            return redirect()->route('student.profile');
        }

        $completion = $this->profileCompletionService->summary($profile);

        if (!$completion['is_complete']) {
            return redirect()->route('student.profile');
        }

        return redirect()->route('student.dashboard');
    }

    private function throttleKey(string $email, Request $request): string
    {
        return 'login:' . Str::lower($email) . '|' . $request->ip();
    }
}

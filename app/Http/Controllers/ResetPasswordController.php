<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ResetPasswordController extends Controller
{
    public function show(Request $request, string $token): View
    {
        return view('pages.portal.reset-password', [
            'title' => 'Reset Password',
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user instanceof User && $user->isSuspended()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'This account cannot reset its password. Contact support.']);
        }

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'must_reset_password' => false,
                    'password_changed_at' => now(),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('password.reset.success')
                ->with('password_reset_success', true);
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    public function success(Request $request): View|RedirectResponse
    {
        // TODO: Re-enable after preview — require one-time session flag from successful reset.
        if (! $request->session()->pull('password_reset_success')) {
            return redirect()->route('login');
        }

        return view('pages.portal.reset-password-success', [
            'title' => 'Password Updated',
        ]);
    }
}

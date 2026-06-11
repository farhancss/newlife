<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function show(): View
    {
        return view('pages.portal.forgot-password', [
            'title' => 'Forgot Password',
        ]);
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = $request->validated('email');
        $user = User::query()->where('email', $email)->first();

        if ($user instanceof User && !$user->isSuspended()) {
            Password::broker()->sendResetLink(['email' => $email]);
        }

        return back()->with('status', 'If an account exists for that email, we sent password reset instructions.');
    }
}

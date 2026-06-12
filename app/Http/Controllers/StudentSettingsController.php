<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentSettingsController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {
    }

    public function show(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $preference = $this->notifications->preferenceFor($user);

        return view('pages.portal.student.settings', [
            'title' => 'Settings',
            'portal' => 'student',
            'preference' => $preference,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $preference = $this->notifications->preferenceFor($user);

        $validated = $request->validate([
            'email_enabled' => ['nullable', 'boolean'],
            'sms_enabled' => ['nullable', 'boolean'],
            'sms_number' => ['nullable', 'string', 'max:32'],
            'parent_cc_enabled' => ['nullable', 'boolean'],
        ]);

        $preference->update([
            'email_enabled' => $request->boolean('email_enabled'),
            'sms_enabled' => $request->boolean('sms_enabled'),
            'sms_number' => $validated['sms_number'] ?? null,
            'parent_cc_enabled' => $request->boolean('parent_cc_enabled'),
        ]);

        return redirect()
            ->route('student.settings')
            ->with('status', 'Notification preferences saved.');
    }
}

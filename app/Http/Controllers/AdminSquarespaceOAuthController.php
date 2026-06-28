<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Squarespace\SquarespaceOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AdminSquarespaceOAuthController extends Controller
{
    public function __construct(
        private readonly SquarespaceOAuthService $oauth,
    ) {
    }

    /**
     * Kick off the OAuth authorization-code flow by redirecting the admin to
     * Squarespace's consent screen.
     */
    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->oauth->isConfigured()) {
            return redirect()
                ->route('admin.squarespace')
                ->with('error', 'Set SQUARESPACE_CLIENT_ID and SQUARESPACE_CLIENT_SECRET before connecting.');
        }

        $state = $this->oauth->generateState();
        $request->session()->put('squarespace_oauth_state', $state);

        return redirect()->away($this->oauth->authorizationUrl($state));
    }

    /**
     * Handle Squarespace's redirect back, verify the state, and exchange the
     * authorization code for tokens.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('admin.squarespace')
                ->with('error', 'Squarespace authorization was denied: ' . $request->string('error'));
        }

        $expectedState = $request->session()->pull('squarespace_oauth_state');
        $providedState = (string) $request->query('state', '');

        if ($expectedState === null || ! hash_equals((string) $expectedState, $providedState)) {
            return redirect()
                ->route('admin.squarespace')
                ->with('error', 'OAuth state mismatch. Please try connecting again.');
        }

        $code = (string) $request->query('code', '');

        if ($code === '') {
            return redirect()
                ->route('admin.squarespace')
                ->with('error', 'Squarespace did not return an authorization code.');
        }

        try {
            /** @var User|null $actor */
            $actor = Auth::user();
            $this->oauth->handleCallback($code, $actor);
        } catch (Throwable $e) {
            return redirect()
                ->route('admin.squarespace')
                ->with('error', 'Failed to connect Squarespace: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.squarespace')
            ->with('status', 'Squarespace connected successfully. You can now register the webhook subscription.');
    }
}

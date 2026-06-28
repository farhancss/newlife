<?php

namespace App\Http\Controllers;

use App\Models\SquarespaceWebhookSubscription;
use App\Services\Squarespace\SquarespaceOAuthService;
use App\Services\Squarespace\SquarespaceWebhookSubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Throwable;

class AdminSquarespaceController extends Controller
{
    public function __construct(
        private readonly SquarespaceOAuthService $oauth,
        private readonly SquarespaceWebhookSubscriptionService $subscriptions,
    ) {
    }

    public function index(): View
    {
        $credential = $this->oauth->current();

        return view('pages.portal.admin.squarespace.index', [
            'title' => 'Squarespace Integration',
            'portal' => 'admin',
            'configured' => $this->oauth->isConfigured(),
            'connected' => $this->oauth->isConnected(),
            'credential' => $credential,
            'scopes' => $this->oauth->scopes(),
            'redirectUri' => $this->oauth->redirectUri(),
            'endpointUrl' => $this->subscriptions->endpointUrl(),
            'topics' => $this->subscriptions->defaultTopics(),
            'subscriptions' => SquarespaceWebhookSubscription::query()->latest('id')->get(),
            'logViewerUrl' => $this->logViewerUrl(),
            'logChannel' => (string) config('squarespace.log_channel', 'squarespace'),
        ]);
    }

    /**
     * URL to the Log Viewer UI, where the dated "squarespace-*.log" files appear.
     */
    private function logViewerUrl(): string
    {
        return Route::has('log-viewer.index') ? route('log-viewer.index') : url('/log-viewer');
    }

    public function disconnect(): RedirectResponse
    {
        $this->oauth->disconnect();

        return redirect()->route('admin.squarespace')->with('status', 'Squarespace connection removed.');
    }

    public function registerWebhook(): RedirectResponse
    {
        try {
            $subscription = $this->subscriptions->create();
        } catch (Throwable $e) {
            return redirect()->route('admin.squarespace')->with('error', 'Could not register webhook: ' . $e->getMessage());
        }

        return redirect()->route('admin.squarespace')
            ->with('status', 'Webhook subscription registered (' . $subscription->subscription_id . ').');
    }

    public function deleteWebhook(SquarespaceWebhookSubscription $subscription): RedirectResponse
    {
        try {
            $this->subscriptions->delete($subscription->subscription_id);
        } catch (Throwable $e) {
            return redirect()->route('admin.squarespace')->with('error', 'Could not delete webhook: ' . $e->getMessage());
        }

        return redirect()->route('admin.squarespace')->with('status', 'Webhook subscription deleted.');
    }

    public function testWebhook(Request $request, SquarespaceWebhookSubscription $subscription): RedirectResponse
    {
        $topic = (string) $request->input('topic', $subscription->topics[0] ?? 'order.create');

        try {
            $status = $this->subscriptions->sendTest($subscription->subscription_id, $topic);
        } catch (Throwable $e) {
            return redirect()->route('admin.squarespace')->with('error', 'Test notification failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.squarespace')
            ->with('status', "Test '{$topic}' sent — endpoint responded with HTTP {$status}.");
    }

    public function rotateSecret(SquarespaceWebhookSubscription $subscription): RedirectResponse
    {
        try {
            $this->subscriptions->rotateSecret($subscription->subscription_id);
        } catch (Throwable $e) {
            return redirect()->route('admin.squarespace')->with('error', 'Could not rotate secret: ' . $e->getMessage());
        }

        return redirect()->route('admin.squarespace')->with('status', 'Webhook signing secret rotated.');
    }
}

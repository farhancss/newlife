<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Squarespace\SquarespaceWebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SquarespaceSimulationController extends Controller
{
    public function simulate(
        Request $request,
        SquarespaceWebhookDispatcher $dispatcher,
    ): JsonResponse {
        $validated = $request->validate([
            'topic' => ['required', 'string'],
            'payload' => ['nullable', 'array'],
        ]);

        $notification = $this->buildNotification(
            $validated['topic'],
            $validated['payload'] ?? []
        );

        $event = $dispatcher->dispatch($notification);

        return response()->json([
            'simulated' => true,
            'notification_id' => $event->notification_id,
            'topic' => $event->topic,
            'status' => $event->status,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function buildNotification(string $topic, array $payload): array
    {
        if (isset($payload['id'], $payload['topic'])) {
            return $payload;
        }

        return array_merge([
            'id' => 'sim-' . uniqid(),
            'websiteId' => config('squarespace.website_id', 'sim-website'),
            'subscriptionId' => 'sim-subscription',
            'topic' => $topic,
            'createdOn' => now()->toIso8601String(),
            'data' => $payload,
        ], $payload);
    }
}

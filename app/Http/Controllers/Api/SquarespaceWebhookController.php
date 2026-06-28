<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Squarespace\SquarespaceLogger;
use App\Services\Squarespace\SquarespaceSignatureVerifier;
use App\Services\Squarespace\SquarespaceWebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SquarespaceWebhookController extends Controller
{
    public function handle(
        Request $request,
        SquarespaceSignatureVerifier $verifier,
        SquarespaceWebhookDispatcher $dispatcher,
        SquarespaceLogger $logger,
    ): JsonResponse {
        $payload = $request->getContent();
        $signature = $request->header('Squarespace-Signature');

        /** @var array<string, mixed> $notification */
        $notification = json_decode($payload, true) ?? [];
        $topic = isset($notification['topic']) ? (string) $notification['topic'] : null;
        $notificationId = isset($notification['id']) ? (string) $notification['id'] : null;

        $signatureValid = $verifier->verify($payload, $signature);

        if (! $signatureValid) {
            $logger->logIncomingWebhook($request, false, 401, '{"message":"Invalid signature."}', $topic, $notificationId);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        try {
            $dispatcher->dispatch($notification);
        } catch (Throwable $e) {
            $logger->logIncomingWebhook($request, true, 422, '{"message":"' . addslashes($e->getMessage()) . '"}', $topic, $notificationId);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        $logger->logIncomingWebhook($request, true, 200, '{"received":true}', $topic, $notificationId);

        return response()->json(['received' => true]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Squarespace\SquarespaceSignatureVerifier;
use App\Services\Squarespace\SquarespaceWebhookDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SquarespaceWebhookController extends Controller
{
    public function handle(
        Request $request,
        SquarespaceSignatureVerifier $verifier,
        SquarespaceWebhookDispatcher $dispatcher,
    ): JsonResponse {
        $payload = $request->getContent();
        $signature = $request->header('Squarespace-Signature');

        if (!$verifier->verify($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        /** @var array<string, mixed> $notification */
        $notification = json_decode($payload, true) ?? [];

        $dispatcher->dispatch($notification);

        return response()->json(['received' => true]);
    }
}

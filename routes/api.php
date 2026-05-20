<?php

use App\Http\Controllers\Api\SquarespaceSimulationController;
use App\Http\Controllers\Api\SquarespaceWebhookController;
use App\Http\Middleware\VerifySquarespaceSimulationToken;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/squarespace', [SquarespaceWebhookController::class, 'handle'])
    ->name('api.webhooks.squarespace');

Route::middleware([
    \App\Http\Middleware\EnsureLocalEnvironment::class,
    VerifySquarespaceSimulationToken::class,
])
    ->prefix('dev/squarespace')
    ->group(function () {
        Route::post('/simulate', [SquarespaceSimulationController::class, 'simulate'])
            ->name('api.dev.squarespace.simulate');
    });

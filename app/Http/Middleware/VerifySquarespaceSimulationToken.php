<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySquarespaceSimulationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = config('squarespace.simulation_token');
        $auth = $request->bearerToken();

        if (!$token || !$auth || !hash_equals($token, $auth)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}

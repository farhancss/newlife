<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /** @var list<string> */
    private array $exceptRouteNames = [
        'student.change-password',
        'student.change-password.submit',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();

        if (
            $user
            && $user->must_reset_password
            && !in_array($routeName, $this->exceptRouteNames, true)
        ) {
            return redirect()->route('student.change-password');
        }

        return $next($request);
    }
}

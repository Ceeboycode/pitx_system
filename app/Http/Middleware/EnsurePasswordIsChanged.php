<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->must_change_password) {
            return $next($request);
        }

        $allowedRoutes = [
            'force-password.edit',
            'force-password.update',
            'logout',
        ];

        if ($request->route()?->named($allowedRoutes)) {
            return $next($request);
        }

        return redirect()->route('force-password.edit');
    }
}

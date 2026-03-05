<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRoleType
{
    public function handle(Request $request, Closure $next, string $type)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // roles.type must be 'internal' or 'external'
        $hasType = $user->roles()->where('type', $type)->exists();

        if ($hasType) {
            return $next($request);
        }

        // Redirect user to their correct "home"
        $isExternal = $user->roles()->where('type', 'external')->exists();
        $isInternal = $user->roles()->where('type', 'internal')->exists();

        if ($isExternal) {
            // send external users to their portal (status handles unverified)
            return redirect()->route('registration.status');
        }

        if ($isInternal) {
            return redirect()->route('dashboard');
        }

        // no roles at all -> safest default
        return redirect()->route('dashboard');
    }
}

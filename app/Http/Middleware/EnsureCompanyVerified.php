<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCompanyVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $company = $user?->company;

        if (! $user) {
            return redirect()->route('login');
        }

        // If no company yet, send to status page (status will handle it safely)
        if (! $company) {
            return redirect()->route('registration.status');
        }

        if ($company->status !== 'verified') {
            return redirect()->route('registration.status');
        }

        return $next($request);
    }
}

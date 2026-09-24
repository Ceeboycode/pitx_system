<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCompanyVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $company = $user?->company;

        if (! $user) {
            return redirect()->route('login');
        }

        $isInternal = $user->roles()->where('type', 'internal')->exists();
        if ($isInternal) {
            return $next($request);
        }

        // If no company yet, send to status page (status will handle it safely)
        if (! $company) {
            return redirect()->route('registration.status');
        }

        if ($company->status !== 'verified') {
            if ($user->hasRole('operator')) {
                return redirect()->route('registration.status');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'Your company documents are not yet verified.');
        }

        return $next($request);
    }
}

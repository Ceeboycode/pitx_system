<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CompanyVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $isInternal = $user->roles()->where('type', 'internal')->exists();
        if ($isInternal) {
            return $next($request);
        }

        $company = $user->company;

        if (! $company) {
            return redirect()->route('company-registration.show');
        }

        if ($company->status !== 'verified') {
            if ($user->hasRole('operator')) {
                return redirect()
                    ->route('registration.status')
                    ->with('error', 'Your company documents are still pending approval.');
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

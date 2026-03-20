<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $company = $user->company;

        if (! $company) {
            return redirect()->route('company-registration.show');
        }

        if ($company->status !== 'verified') {
            return redirect()
                ->route('registration.status')
                ->with('error', 'Your company documents are still pending approval.');
        }

        return $next($request);
    }
}

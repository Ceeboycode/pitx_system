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

        // Safety: if someone forgot to add auth middleware upstream
        if (! $user) {
            return redirect()->route('login');
        }

        $company = $user->company;

        // No company yet → go to registration wizard
        if (! $company) {
            return redirect()->route('company-registration.show');
        }

        // Not verified → go to status page
        if ($company->status !== 'verified') {
            return redirect()->route('registration.status');
        }

        return $next($request);
    }
}

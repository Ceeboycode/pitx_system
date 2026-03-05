<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class RoleBasedLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        $fallback = route('dashboard');

        if (! $user) {
            return redirect()->intended($fallback);
        }

        $isInternal = $user->roles()->where('type', 'internal')->exists();
        $isExternal = $user->roles()->where('type', 'external')->exists();

        // Prefer internal if both exist
        if ($isInternal) {
            return redirect()->intended(route('dashboard'));
        }

        if ($isExternal) {
            return redirect()->intended(route('company.dashboard'));
        }

        return redirect()->intended($fallback);
    }
}

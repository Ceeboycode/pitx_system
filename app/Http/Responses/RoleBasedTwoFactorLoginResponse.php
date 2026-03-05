<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class RoleBasedTwoFactorLoginResponse implements TwoFactorLoginResponseContract
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

        if ($isInternal) {
            return redirect()->intended(route('dashboard'));
        }

        if ($isExternal) {
            return redirect()->intended(route('company.dashboard'));
        }

        return redirect()->intended($fallback);
    }
}

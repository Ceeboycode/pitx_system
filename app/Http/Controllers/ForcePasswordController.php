<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForcePasswordController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('auth/ForcePasswordReset', [
            'mustChangePassword' => (bool) $request->user()?->must_change_password,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        abort_if(! $user, 403);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        return redirect()
            ->route($user->hasAnyRole(['driver', 'dispatcher']) ? 'company.dashboard' : 'dashboard')
            ->with('success', 'Password updated successfully.');
    }
}

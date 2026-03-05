<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthController extends Controller
{
    /**
     * Show login page (Inertia).
     * If already logged in, redirect by role type.
     */
    public function create(Request $request): InertiaResponse|RedirectResponse
    {
        if ($request->user()) {
            return $this->redirectByRole($request);
        }

        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login and redirect by role type.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $remember = (bool) $request->boolean('remember');

        $ok = Auth::attempt(
            $request->only('email', 'password'),
            $remember
        );

        if (! $ok) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        $request->session()->regenerate();

        return $this->redirectByRole($request);
    }

    /**
     * Logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Decide where to go after login based on role type.
     * - external -> company.dashboard (External/Dashboard.vue)
     * - internal -> dashboard (Dashboard.vue)
     */
    private function redirectByRole(Request $request): RedirectResponse
    {
        $user = $request->user();

        // If you have roles.type column (recommended)
        $isExternal = $user
            ->roles()
            ->where('type', 'external')
            ->exists();

        // If user has BOTH, you can prefer internal by flipping logic.
        if ($isExternal) {
            // If company not verified, your company.verified middleware should send them to status page.
            return redirect()->intended(route('company.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }
}

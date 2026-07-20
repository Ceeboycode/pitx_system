<?php

namespace App\Providers;

// DEAD CODE: Only used by Fortify's disabled web self-registration flow.
// use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Responses\RoleBasedLoginResponse;
use App\Http\Responses\RoleBasedTwoFactorLoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, RoleBasedLoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, RoleBasedTwoFactorLoginResponse::class);
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureAuthentication();
        $this->configureRateLimiting();
    }

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        // DEAD CODE: Mobile users register through POST /api/v1/auth/register.
        // Fortify::createUsersUsing(CreateNewUser::class);
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        // DEAD CODE: Fortify web registration is disabled in config/fortify.php.
        // Fortify::registerView(fn () => Inertia::render('auth/Register'));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));

        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $login = trim((string) $request->input('login'));
            $password = (string) $request->input('password');

            if ($login === '' || $password === '') {
                return null;
            }

            $loginLower = Str::lower($login);

            $user = User::query()
                ->with('roles')
                ->where(function ($query) use ($login, $loginLower) {
                    $query->whereRaw('LOWER(username) = ?', [$loginLower])
                        ->orWhereRaw('LOWER(email) = ?', [$loginLower]);
                })
                ->first();

            if (! $user) {
                return null;
            }

            if (! Hash::check($password, $user->password)) {
                return null;
            }

            if (in_array($user->status, ['inactive', 'suspended'], true)) {
                return null;
            }

            return $user;
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $value = trim((string) $request->input(Fortify::username()));
            $throttleKey = Str::transliterate(Str::lower($value) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });
    }
}

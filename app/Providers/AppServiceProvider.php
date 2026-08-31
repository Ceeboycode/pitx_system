<?php

namespace App\Providers;

use App\Observers\AuditableObserver;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn (): Password => Password::min(12)->uncompromised());

        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        $this->registerAuditObservers();
        $this->registerAuditAuthEvents();

        // ── External Vehicles ─────────────────────────────────────────
        Gate::define('external_vehicles.viewAny', fn ($user) => $user->hasPermissionTo('external_vehicles.viewAny')
        );

        Gate::define('external_vehicles.view', fn ($user) => $user->hasPermissionTo('external_vehicles.view')
        );

        Gate::define('external_vehicles.create', fn ($user) => $user->hasPermissionTo('external_vehicles.create')
        );

        Gate::define('external_vehicles.update', fn ($user) => $user->hasPermissionTo('external_vehicles.update')
        );

        Gate::define('external_vehicles.toggleStatus', fn ($user) => $user->hasPermissionTo('external_vehicles.toggleStatus')
        );

        // ── External Vehicle Documents ────────────────────────────────
        Gate::define('external_vehicle_documents.viewAny', fn ($user) => $user->hasPermissionTo('external_vehicle_documents.viewAny')
        );

        Gate::define('external_vehicle_documents.view', fn ($user) => $user->hasPermissionTo('external_vehicle_documents.view')
        );

        Gate::define('external_vehicle_documents.download', fn ($user) => $user->hasPermissionTo('external_vehicle_documents.download')
        );

        Gate::define('external_vehicle_documents.upload', fn ($user) => $user->hasPermissionTo('external_vehicle_documents.upload')
        );
    }

    private function registerAuditObservers(): void
    {
        $models = config('audit.models', []);

        if (config('audit.discover_models', false)) {
            $models = array_merge($models, $this->discoverModelClasses());
        }

        $excludedModels = config('audit.excluded_models', []);

        foreach (array_unique($models) as $modelClass) {
            if (! is_string($modelClass) || ! class_exists($modelClass)) {
                continue;
            }

            if (! is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            if (in_array($modelClass, $excludedModels, true)) {
                continue;
            }

            $modelClass::observe(AuditableObserver::class);
        }
    }

    /**
     * @return array<int, string>
     */
    private function discoverModelClasses(): array
    {
        $modelFiles = File::allFiles(app_path('Models'));
        $classes = [];

        foreach ($modelFiles as $file) {
            $relativePath = str_replace([app_path().DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
            $class = 'App\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

            if (class_exists($class) && is_subclass_of($class, Model::class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    private function registerAuditAuthEvents(): void
    {
        Event::listen(Login::class, function (Login $event) {
            $auditable = $event->user instanceof Model ? $event->user : null;

            app(AuditLogger::class)->log(
                action: 'auth.login',
                actor: $event->user,
                auditable: $auditable,
                metadata: [
                    'guard' => $event->guard,
                ],
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            $auditable = $event->user instanceof Model ? $event->user : null;

            app(AuditLogger::class)->log(
                action: 'auth.logout',
                actor: $event->user,
                auditable: $auditable,
                metadata: [
                    'guard' => $event->guard,
                ],
            );
        });

        Event::listen(Failed::class, function (Failed $event) {
            $auditable = $event->user instanceof Model ? $event->user : null;

            app(AuditLogger::class)->log(
                action: 'auth.login_failed',
                actor: null,
                auditable: $auditable,
                metadata: [
                    'guard' => $event->guard,
                    'login' => $event->credentials['login'] ?? $event->credentials['email'] ?? null,
                ],
                companyId: null,
            );
        });
    }
}

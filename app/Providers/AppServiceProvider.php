<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        // ── External Vehicles ─────────────────────────────────────────
        Gate::define('external_vehicles.viewAny', fn ($user) =>
            $user->hasPermissionTo('external_vehicles.viewAny')
        );

        Gate::define('external_vehicles.view', fn ($user) =>
            $user->hasPermissionTo('external_vehicles.view')
        );

        Gate::define('external_vehicles.create', fn ($user) =>
            $user->hasPermissionTo('external_vehicles.create')
        );

        Gate::define('external_vehicles.update', fn ($user) =>
            $user->hasPermissionTo('external_vehicles.update')
        );

        Gate::define('external_vehicles.toggleStatus', fn ($user) =>
            $user->hasPermissionTo('external_vehicles.toggleStatus')
        );

        // ── External Vehicle Documents ────────────────────────────────
        Gate::define('external_vehicle_documents.viewAny', fn ($user) =>
            $user->hasPermissionTo('external_vehicle_documents.viewAny')
        );

        Gate::define('external_vehicle_documents.view', fn ($user) =>
            $user->hasPermissionTo('external_vehicle_documents.view')
        );

        Gate::define('external_vehicle_documents.download', fn ($user) =>
            $user->hasPermissionTo('external_vehicle_documents.download')
        );

        Gate::define('external_vehicle_documents.upload', fn ($user) =>
            $user->hasPermissionTo('external_vehicle_documents.upload')
        );
    }
}

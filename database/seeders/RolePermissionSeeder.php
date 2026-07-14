<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::query()->pluck('name')->all();
        $internalPermissions = Permission::query()
            ->where('name', 'not like', 'external_%')
            ->pluck('name')
            ->all();

        $terminalManagerPermissions = Permission::query()
            ->where(function ($query) {
                $query->where('name', 'like', 'companies.%')
                    ->orWhere('name', 'like', 'company_documents.%')
                    ->orWhere('name', 'like', 'vehicles.%')
                    ->orWhere('name', 'like', 'vehicle_documents.%')
                    ->orWhere('name', 'like', 'gates.%')
                    ->orWhere('name', 'like', 'routes.%')
                    ->orWhere('name', 'like', 'dispatches.%')
                    ->orWhere('name', 'audit_logs.viewOwn');
            })
            ->pluck('name')
            ->all();

        $itPermissions = Permission::query()
            ->where(function ($query) {
                $query->where('name', 'like', 'roles.%')
                    ->orWhere('name', 'like', 'users.%')
                    ->orWhere('name', 'like', 'audit_logs.%');
            })
            ->pluck('name')
            ->all();

        $externalPermissions = Permission::query()
            ->where('name', 'like', 'external_%')
            ->pluck('name')
            ->all();

        $superAdmin = Role::query()
            ->where('name', 'super-admin')
            ->where('type', 'internal')
            ->firstOrFail();

        $admin = Role::query()
            ->where('name', 'admin')
            ->where('type', 'internal')
            ->firstOrFail();

        // $it = Role::query()
        //     ->where('name', 'it')
        //     ->where('type', 'internal')
        //     ->firstOrFail();

        $terminalManager = Role::query()
            ->where('name', 'terminal manager')
            ->where('type', 'internal')
            ->firstOrFail();

        $operator = Role::query()
            ->where('name', 'operator')
            ->where('type', 'external')
            ->firstOrFail();

        $dispatcher = Role::query()
            ->where('name', 'dispatcher')
            ->where('type', 'external')
            ->firstOrFail();

        $driver = Role::query()
            ->where('name', 'driver')
            ->where('type', 'external')
            ->firstOrFail();

        $commuter = Role::query()
            ->where('name', 'commuter')
            ->where('type', 'external')
            ->firstOrFail();

        $superAdmin->syncPermissions($allPermissions);
        $admin->syncPermissions($internalPermissions);

        $it->syncPermissions($itPermissions);
        $terminalManager->syncPermissions($terminalManagerPermissions);

        $operator->syncPermissions($externalPermissions);

        // No permissions yet unless you want them too
        $dispatcher->syncPermissions([]);
        $driver->syncPermissions([]);
        $commuter->syncPermissions([]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $modules = [
        // ── Internal ─────────────────────────────────────────
        'companies' => [
            'viewAny',
            'view',
            'delete',
            'restore',
            'forceDelete',
        ],

        'company_documents' => [
            'viewAny',
            'download',
            'verify',
            'update',
            'reject',
            'delete',
        ],

        'vehicles' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'forceDelete',
            'toggleStatus',
        ],

        'vehicle_documents' => [
            'verify',
            'invalidate',
            'unverify',
        ],

        'gates' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'forceDelete',
            'viewTrash',
        ],

        'routes' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'forceDelete',
            'viewTrash',
            'toggleStatus',
        ],

        'users' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'viewTrash',
            'toggleStatus',
            'resetPassword',
        ],

        'roles' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'viewTrash',
        ],

        'dispatches' => [
            'viewAny',
            'view',
        ],

        'audit_logs' => [
            'viewAny',
        ],

        // ── External ──────────────────────────────────────────
        // prefix ALL external modules with external_
        // e.g. if companies can be managed by external users too:
        'external_companies' => [
            'view',
            'update',
        ],

        'external_dispatches' => [
            'viewAny',
            'view',
        ],

        'external_vehicles' => [
            'viewAny',
            'view',
            'create',
            'update',
            'toggleStatus',
        ],

        'external_vehicle_documents' => [
            'download',
            'upload',
        ],

        'external_users' => [
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
            'toggleStatus',
            'resetPassword',
        ],

    ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::query()->firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

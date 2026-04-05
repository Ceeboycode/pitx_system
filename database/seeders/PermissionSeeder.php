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
            'archive',
            'restore',
        ],

        'company_documents' => [
            'viewAny',
            'download',
            'verify',
            'update',
            'reject',
            'archive',
        ],

        'vehicles' => [
            'viewAny',
            'view',
            'create',
            'update',
            'archive',
            'restore',
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
            'archive',
            'restore',
            'viewTrash',
        ],

        'routes' => [
            'viewAny',
            'view',
            'create',
            'update',
            'archive',
            'restore',
            'viewTrash',
            'toggleStatus',
        ],

        'users' => [
            'viewAny',
            'view',
            'create',
            'update',
            'archive',
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
            'archive',
            'restore',
            'viewTrash',
        ],

        'dispatches' => [
            'viewAny',
            'view',
        ],

        'audit_logs' => [
            'viewAny',
            'viewOwn',
        ],

        // ── External ──────────────────────────────────────────
        // prefix ALL external modules with external_
        // e.g. if companies can be managed by external users too:
        'external_companies_settings' => [
            'view',
            'update',
        ],

        'external_dispatches' => [
            'viewAny',
            'view',
            'create',
            'update',
            'depart',
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
            'archive',
            'toggleStatus',
            'resetPassword',
        ],

    ];

        Permission::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%.delete')
                    ->orWhere('name', 'like', '%.forceDelete')
                    ->orWhere('name', 'like', 'external_companies.%');
            })
            ->delete();

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

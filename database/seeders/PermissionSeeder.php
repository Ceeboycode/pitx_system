<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cached permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.viewAny',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            'roles.viewAny',
            'roles.view',
            'roles.create',
            'roles.edit',
            'roles.delete',
        ];

        // 1️ Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => config('auth.defaults.guard'),
            ]);
        }

        // 2️ Create role
        $admin = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['guard_name' => config('auth.defaults.guard')]
        );

        // 3️ Assign permissions to role
        $admin->syncPermissions($permissions);
    }
}

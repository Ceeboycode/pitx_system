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
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'users.viewAny','users.view','users.create','users.edit','users.delete',
            'roles.viewAny','roles.view','roles.create','roles.edit','roles.delete',
            'company.viewAny','company.view','company.create','company.update',
            'company.delete','company.restore','company.forceDelete',
            'vehicle_type.viewAny','vehicle_type.view','vehicle_type.create',
            'vehicle_type.update','vehicle_type.delete',
            'vehicle_type.restore','vehicle_type.forceDelete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}

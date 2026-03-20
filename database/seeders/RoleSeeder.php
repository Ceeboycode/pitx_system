<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            ['name' => 'super-admin', 'type' => 'internal'],
            ['name' => 'admin', 'type' => 'internal'],
            ['name' => 'it', 'type' => 'internal'],
            ['name' => 'terminal manager', 'type' => 'internal'],

            ['name' => 'operator', 'type' => 'external'],
            ['name' => 'dispatcher', 'type' => 'external'],
            ['name' => 'driver', 'type' => 'external'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                [
                    'name' => $role['name'],
                    'guard_name' => 'web',
                ],
                [
                    'type' => $role['type'],
                ]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

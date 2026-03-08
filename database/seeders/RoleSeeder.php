<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $operator = Role::firstOrCreate(['name' => 'operator', 'guard_name' => 'web', 'type' => 'external']);
        $dispatcher = Role::firstOrCreate(['name' => 'dispatcher', 'guard_name' => 'web', 'type' => 'external']);
        $commuter = Role::firstOrCreate(['name' => 'commuter', 'guard_name' => 'web', 'type' => 'commuter']);

        $superAdmin->syncPermissions(Permission::all());
    }
}

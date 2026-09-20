<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Internal roles no longer create vehicles (companies do, through the
     * external_vehicles.* permissions), so the permission is removed from
     * existing databases. Deleting it also detaches it from every role and user.
     */
    public function up(): void
    {
        Permission::query()
            ->where('name', 'vehicles.create')
            ->where('guard_name', 'web')
            ->get()
            ->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Recreates the permission row only; role assignments are not restored.
     */
    public function down(): void
    {
        Permission::query()->firstOrCreate([
            'name' => 'vehicles.create',
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'companies.update')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'companies.update',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('role_has_permissions')) {
            return;
        }

        DB::table('roles')
            ->whereIn('name', ['super-admin', 'admin', 'terminal manager'])
            ->where('type', 'internal')
            ->pluck('id')
            ->each(function (int $roleId) use ($permissionId): void {
                DB::table('role_has_permissions')->updateOrInsert([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            ! Schema::hasTable('permissions')
            || ! Schema::hasTable('roles')
            || ! Schema::hasTable('role_has_permissions')
        ) {
            return;
        }

        $permissionId = DB::table('permissions')
            ->where('name', 'companies.update')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['super-admin', 'admin', 'terminal manager'])
            ->where('type', 'internal')
            ->pluck('id');

        DB::table('role_has_permissions')
            ->where('permission_id', $permissionId)
            ->whereIn('role_id', $roleIds)
            ->delete();

        DB::table('permissions')
            ->where('id', $permissionId)
            ->delete();
    }
};

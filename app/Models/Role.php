<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use SoftDeletes;

    /**
     * Commuters self-register through POST /api/v1/auth/register from the
     * mobile app (see RegistrationTest) - this role must never be
     * assignable from the internal Users module or the company Employee
     * module, both of which only manage staff accounts.
     */
    public const NAME_COMMUTER = 'commuter';

    /**
     * Drivers hold no permissions (they have no portal yet), yet are the
     * only role that can be assigned to dispatches (see DispatchController),
     * so features that depend on being dispatched key off this name.
     */
    public const NAME_DRIVER = 'driver';

    protected $fillable = [
        'name',
        'guard_name',
        'type',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * The ids, out of the given ones, that a role of this type may hold.
     * External roles hold the "external_" permissions; internal roles hold all the others.
     *
     * @param  array<int, int|string>  $permissionIds
     * @return array<int, int>
     */
    public static function permissionIdsForType(string $type, array $permissionIds): array
    {
        return Permission::query()
            ->whereIn('id', $permissionIds)
            ->get(['id', 'name'])
            ->filter(fn (Permission $permission): bool => str_starts_with($permission->name, 'external_') === ($type === 'external'))
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * Whether this role has been granted at least one permission that
     * belongs to a given group. Permission names in this app follow a
     * "group.action" pattern (e.g. "external_users.viewAny",
     * "external_users.create"), so $prefix here is the "group" part,
     * without the trailing dot.
     *
     * Used to gate access to a whole feature area (e.g. a UI tab) instead
     * of checking one permission at a time. Reads the role's own
     * permissions, so any role - seeded or created later - is handled the
     * same way, and eager loading `permissions` avoids extra queries.
     */
    public function hasAnyPermissionInGroup(string $prefix): bool
    {
        return $this->permissions->contains(
            fn (Permission $permission): bool => str_starts_with($permission->name, "{$prefix}."),
        );
    }
}

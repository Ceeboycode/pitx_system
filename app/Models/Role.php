<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use SoftDeletes;

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
     * Whether this role has been granted every permission that belongs to
     * a given group. Permission names in this app follow a "group.action"
     * pattern (e.g. "external_users.viewAny", "external_users.create"), so
     * $prefix here is the "group" part, without the trailing dot.
     *
     * Used to gate access to a whole feature area (e.g. a UI tab) instead
     * of checking one permission at a time.
     */
    public function hasAllPermissionsInGroup(string $prefix): bool
    {
        // "%" is the SQL LIKE wildcard, so this matches every permission
        // name that starts with "{$prefix}.", e.g. "external_users.".
        $groupPermissions = Permission::query()
            ->where('name', 'like', "{$prefix}.%")
            ->pluck('name');

        // If the group has no permissions defined at all, there is nothing
        // to have "all" of — treat that as false rather than vacuously true.
        if ($groupPermissions->isEmpty()) {
            return false;
        }

        // Collection::diff() returns the items in $groupPermissions that are
        // NOT present in the role's own permission names. If that leftover
        // list is empty, the role already has every permission in the group.
        return $groupPermissions->diff($this->permissions->pluck('name'))->isEmpty();
    }
}

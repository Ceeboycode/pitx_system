<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('audit_logs.viewAny');
    }

    public function viewOwn(User $user): bool
    {
        return $this->hasRoleType($user, 'internal');
    }

    public function viewOwnExternal(User $user): bool
    {
        return $this->hasRoleType($user, 'external');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->can('audit_logs.viewAny');
    }

    private function hasRoleType(User $user, string $type): bool
    {
        return $user->roles()->where('type', $type)->exists();
    }
}

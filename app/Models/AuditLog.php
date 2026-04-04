<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'company_id',
        'action',
        'auditable_type',
        'auditable_id',
        'changed_fields',
        'metadata',
        'ip_address',
        'user_agent',
        'request_method',
        'request_url',
    ];

    protected $casts = [
        'changed_fields' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        $search = $filters['search'] ?? null;
        $action = $filters['action'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $entityType = $filters['entity_type'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        return $query
            ->when($search, function (Builder $q) use ($search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('action', 'like', '%' . $search . '%')
                        ->orWhere('auditable_type', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%'));
                });
            })
            ->when($action, fn (Builder $q) => $q->where('action', $action))
            ->when($userId, fn (Builder $q) => $q->where('user_id', $userId))
            ->when($entityType, fn (Builder $q) => $q->where('auditable_type', $entityType))
            ->when($dateFrom, fn (Builder $q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $q) => $q->whereDate('created_at', '<=', $dateTo));
    }
}

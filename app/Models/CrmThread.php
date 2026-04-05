<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmThread extends Model
{
    protected $fillable = [
        'company_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'category',          // compliance | system
        'subject',
        'is_closed',
        'closed_at',
        'last_message_at',
        'details',           // json (optional)
    ];

    protected $casts = [
        'is_closed'        => 'boolean',
        'closed_at'        => 'datetime',
        'last_message_at'  => 'datetime',
        'details'          => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CrmMessage::class, 'thread_id');
    }
}
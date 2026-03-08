<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmMessage extends Model
{
    protected $fillable = [
        'thread_id',
        'sender_user_id',
        'body',
        'is_internal', // terminal-only note (optional)
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CrmThread::class, 'thread_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CrmMessageAttachment::class, 'message_id');
    }
}
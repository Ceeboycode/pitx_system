<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmMessageAttachment extends Model
{
    protected $fillable = [
        'thread_id',
        'message_id',
        'uploaded_by_user_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(CrmThread::class, 'thread_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(CrmMessage::class, 'message_id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
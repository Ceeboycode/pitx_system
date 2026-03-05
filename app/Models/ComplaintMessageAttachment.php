<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintMessageAttachment extends Model
{
    protected $fillable = [
        'complaint_message_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'uploaded_by_user_id',
    ];

    public function message()
    {
        return $this->belongsTo(ComplaintMessage::class, 'complaint_message_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
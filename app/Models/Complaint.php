<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'reference_no',
        'commuter_user_id',
        'complaint_category_id',
        'subject',
        'description',
        'incident_at',
        'incident_location',
        'assigned_to_user_id',
        'status',
        'resolution_notes',
        'rejected_reason',
        'resolved_at',
    ];

    protected $casts = [
        'incident_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ComplaintCategory::class, 'complaint_category_id');
    }

    public function commuter()
    {
        return $this->belongsTo(User::class, 'commuter_user_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages()
    {
        return $this->hasMany(ComplaintMessage::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintCategory extends Model
{
    protected $fillable = ['name', 'sort_order', 'is_active'];

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'complaint_category_id');
    }
}
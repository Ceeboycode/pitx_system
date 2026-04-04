<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteFavorite extends Model
{
    protected $fillable = ['user_id', 'origin', 'destination'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

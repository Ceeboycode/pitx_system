<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteStop extends Model
{
    //
    use HasFactory;

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}

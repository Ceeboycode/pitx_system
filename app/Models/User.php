<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'phone_number',
        'password',
        'company_id',
        'status',
        'must_change_password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'api_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'must_change_password' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when(
            filled($search),
            fn (Builder $q) => $q->where(function (Builder $qq) use ($search) {
                $like = '%' . $search . '%';

                $qq->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone_number', 'like', $like)
                    ->orWhereHas('company', fn (Builder $cq) => $cq
                        ->where('company_name', 'like', $like)
                        ->orWhere('company_code', 'like', $like)
                    );
            })
        );
    }

    public function dispatchesAsDispatcher(): HasMany
    {
        return $this->hasMany(Dispatch::class, 'dispatcher_user_id');
    }
}

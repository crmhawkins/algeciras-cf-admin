<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        // Cualquier usuario autenticado puede entrar al panel /admin
        // (más adelante restringimos por rol con Spatie Permission)
        return true;
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        // 2026-06-03: estas columnas existen en BD desde la migración
        // 2026_05_26_..._add_auth_fields, pero faltaban en fillable →
        // $user->update(['profile_image' => …]) se ignoraba silenciosamente.
        'profile_image',
        'push_token',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // 2026-06-03: faltaba este cast → AuthController llamaba a
            // ->toIso8601String() sobre un string y reventaba el login
            // entero con un 500. Reproducido y arreglado.
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
        ];
    }
}

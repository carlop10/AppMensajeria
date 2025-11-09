<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'role' => 'user',
        'is_active' => true,
    ];

    protected $appends = ['is_admin', 'is_courier'];

    public function getIsAdminAttribute()
    {
        return $this->isAdmin();
    }

    public function getIsCourierAttribute()
    {
        return $this->isCourier();
    }


    public function courier()
    {
        return $this->hasOne(Courier::class);
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isCourier()
    {
        return $this->role === 'courier' || $this->courier !== null;
    }

    public function getRoleDisplayAttribute()
    {
        return $this->isAdmin() ? 'Administrador' :
               ($this->isCourier() ? 'Mensajero' : 'Usuario');
    }
}

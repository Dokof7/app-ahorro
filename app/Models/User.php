<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'phone', 'is_active'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'is_active'         => 'boolean',
    ];

    const ROLES = [
        'admin'      => 'Administrador',
        'tesorero'   => 'Tesorero',
        'secretario' => 'Secretario',
        'observador' => 'Observador',
    ];

    public function ownedGroups() { return $this->hasMany(Group::class); }
    public function groups() { return $this->belongsToMany(Group::class); }

    public function isAdmin()      { return $this->role === 'admin'; }
    public function isTesorero()   { return $this->role === 'tesorero'; }
    public function isSecretario() { return $this->role === 'secretario'; }
    public function isObservador() { return $this->role === 'observador'; }

    public function canEdit() { return !$this->isObservador(); }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }
}

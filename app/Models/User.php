<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'password', 'phone', 'is_active'];
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
        'miembro'    => 'Miembro',
    ];

    const ROLE_COLORS = [
        'admin'      => 'danger',
        'tesorero'   => 'warning',
        'secretario' => 'info',
        'observador' => 'secondary',
        'miembro'    => 'success',
    ];

    public function ownedGroups() { return $this->hasMany(Group::class); }
    public function groups() { return $this->belongsToMany(Group::class); }
    public function member() { return $this->hasOne(Member::class); }

    public function isAdmin()      { return $this->hasRole('admin'); }
    public function isTesorero()   { return $this->hasRole('tesorero'); }
    public function isSecretario() { return $this->hasRole('secretario'); }
    public function isObservador() { return $this->hasRole('observador'); }
    public function isMiembro()    { return $this->hasRole('miembro'); }

    public function canEdit() { return !$this->hasRole(['observador', 'miembro']); }

    public function getRoleAttribute(): string
    {
        return $this->getRoleNames()->first() ?? '';
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst($this->role);
    }

    public function getRoleColorAttribute(): string
    {
        return self::ROLE_COLORS[$this->role] ?? 'primary';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'description', 'start_date',
        'status', 'share_value', 'default_shares', 'default_savings', 'default_emergency',
        'membership_fee', 'registration_mode',
    ];

    protected $casts = ['start_date' => 'date'];

    public function user() { return $this->belongsTo(User::class); }
    public function members() { return $this->hasMany(Member::class); }
    public function activeMembers() { return $this->hasMany(Member::class)->where('status', 'active'); }
    public function meetings() { return $this->hasMany(Meeting::class)->orderBy('meeting_number'); }
    public function loans() { return $this->hasMany(Loan::class); }
    public function bankExpenses() { return $this->hasMany(BankExpense::class); }
    public function activities() { return $this->hasMany(Activity::class); }

    public function isPartial(): bool { return $this->registration_mode === 'partial'; }

    protected static function booted()
    {
        static::saving(function ($group) {
            if (($group->share_value ?? 0) <= 0) {
                throw new \InvalidArgumentException('El valor por acción debe ser mayor a 0.');
            }
            if ($group->default_shares !== null && (($group->default_shares ?? 0) < 1 || ($group->default_shares ?? 0) > 25)) {
                throw new \InvalidArgumentException('Las acciones por defecto deben estar entre 1 y 25.');
            }
            if ($group->exists && $group->isDirty('registration_mode') && $group->meetings()->exists()) {
                throw new \RuntimeException('No se puede cambiar el tipo de registro de un grupo que ya tiene reuniones.');
            }
        });

        static::deleting(function ($group) {
            if ($group->loans()->whereIn('status', ['pending', 'overdue'])->exists()) {
                throw new \RuntimeException('No se puede eliminar un grupo con préstamos pendientes o vencidos.');
            }
            if ($group->meetings()->where('status', 'open')->exists()) {
                throw new \RuntimeException('No se puede eliminar un grupo con reuniones abiertas.');
            }
        });
    }
}

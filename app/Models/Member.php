<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_id', 'full_name', 'document_number',
        'phone', 'address', 'join_date', 'status',
        'membership_paid', 'membership_paid_at',
    ];

    protected $casts = [
        'join_date'          => 'date',
        'membership_paid'    => 'boolean',
        'membership_paid_at' => 'datetime',
    ];

    public function group() { return $this->belongsTo(Group::class); }
    public function contributions() { return $this->hasMany(MeetingContribution::class); }
    public function attendances() { return $this->hasMany(Attendance::class); }
    public function loans() { return $this->hasMany(Loan::class); }
    public function fines() { return $this->hasMany(Fine::class); }
    public function pendingFines() { return $this->hasMany(Fine::class)->where('status', 'pending'); }

    public function getTotalSavingsAttribute()
    {
        return $this->contributions()->sum('savings');
    }

    public function getTotalEmergencyAttribute()
    {
        return $this->contributions()->sum('emergency_fund');
    }

    protected static function booted()
    {
        static::deleting(function ($member) {
            if ($member->loans()->whereIn('status', ['pending', 'overdue'])->exists()) {
                throw new \RuntimeException('No se puede eliminar un miembro con préstamos pendientes o vencidos.');
            }
        });

        static::updating(function ($member) {
            if ($member->isDirty('status') && $member->status === 'inactive') {
                if ($member->loans()->whereIn('status', ['pending', 'overdue'])->exists()) {
                    throw new \RuntimeException('No se puede desactivar un miembro con préstamos pendientes o vencidos.');
                }
            }
        });
    }
}

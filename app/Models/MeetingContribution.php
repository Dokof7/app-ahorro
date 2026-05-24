<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id', 'member_id', 'shares', 'savings',
        'emergency_fund', 'fine', 'total', 'confirmed', 'observations'
    ];

    protected $casts = ['confirmed' => 'boolean'];

    public function meeting() { return $this->belongsTo(Meeting::class); }
    public function member() { return $this->belongsTo(Member::class); }

    protected static function booted()
    {
        static::saving(function ($contribution) {
            $shareValue = $contribution->meeting?->group?->share_value ?? 25;
            $contribution->savings = ($contribution->shares ?? 0) * $shareValue;
            $contribution->total = $contribution->savings
                + ($contribution->emergency_fund ?? 0)
                + ($contribution->fine ?? 0);
        });

        static::saving(function ($contribution) {
            if (($contribution->shares ?? 0) < 0 || ($contribution->shares ?? 0) > 25) {
                throw new \InvalidArgumentException('Las acciones deben estar entre 0 y 25. (0 para miembro ausente)');
            }
            if (($contribution->emergency_fund ?? 0) < 0) {
                throw new \InvalidArgumentException('El fondo de emergencia no puede ser negativo.');
            }
            if (($contribution->fine ?? 0) < 0) {
                throw new \InvalidArgumentException('La multa no puede ser negativa.');
            }
        });

        static::saved(function ($contribution) {
            optional($contribution->meeting)->recalculateSummary();
        });

        static::deleted(function ($contribution) {
            optional($contribution->meeting)->recalculateSummary();
        });
    }
}

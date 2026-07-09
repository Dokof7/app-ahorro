<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingTotal extends Model
{
    use HasFactory;

    protected $table = 'meeting_totals';

    protected $fillable = [
        'meeting_id', 'shares', 'savings', 'emergency_fund', 'fine', 'observations',
    ];

    protected $casts = [
        'shares'         => 'integer',
        'savings'        => 'decimal:2',
        'emergency_fund' => 'decimal:2',
        'fine'           => 'decimal:2',
    ];

    public function meeting() { return $this->belongsTo(Meeting::class); }

    protected static function booted()
    {
        static::saving(function ($total) {
            $shareValue = $total->meeting?->group?->share_value ?? 0;
            $total->savings = ($total->shares ?? 0) * $shareValue;
        });

        static::saving(function ($total) {
            if (($total->shares ?? 0) < 0) {
                throw new \InvalidArgumentException('Las acciones no pueden ser negativas.');
            }
            if (($total->emergency_fund ?? 0) < 0) {
                throw new \InvalidArgumentException('El fondo de emergencia no puede ser negativo.');
            }
            if (($total->fine ?? 0) < 0) {
                throw new \InvalidArgumentException('La multa no puede ser negativa.');
            }
        });

        static::saved(function ($total) {
            optional($total->meeting)->recalculateSummary();
        });

        static::deleted(function ($total) {
            optional($total->meeting)->recalculateSummary();
        });
    }
}

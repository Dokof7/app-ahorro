<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fine extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id', 'meeting_id', 'amount',
        'reason', 'status', 'paid_date', 'observations'
    ];

    protected $casts = ['paid_date' => 'date'];

    public function member() { return $this->belongsTo(Member::class); }
    public function meeting() { return $this->belongsTo(Meeting::class); }

    protected static function booted()
    {
        static::saving(function ($fine) {
            if ($fine->amount <= 0) {
                throw new \InvalidArgumentException('El monto de la multa debe ser mayor a 0.');
            }
            if ($fine->status === 'paid' && empty($fine->paid_date)) {
                $fine->paid_date = now();
            }
        });

        static::saved(function ($fine) {
            optional($fine->meeting)->recalculateSummary();
        });

        static::deleted(function ($fine) {
            optional($fine->meeting)->recalculateSummary();
        });
    }
}

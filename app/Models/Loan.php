<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id', 'group_id', 'meeting_id', 'amount',
        'interest_rate', 'interest_amount', 'total_to_return',
        'amount_paid', 'balance', 'delivery_date', 'due_date',
        'status', 'observations'
    ];

    protected $casts = ['delivery_date' => 'date', 'due_date' => 'date'];

    public function member() { return $this->belongsTo(Member::class); }
    public function group() { return $this->belongsTo(Group::class); }
    public function meeting() { return $this->belongsTo(Meeting::class); }
    public function payments() { return $this->hasMany(LoanPayment::class); }

    public function isOverdue()
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    protected static function booted()
    {
        static::saving(function ($loan) {
            $loan->interest_amount = round($loan->amount * ($loan->interest_rate / 100), 2);
            $loan->total_to_return = round($loan->amount + $loan->interest_amount, 2);
            $amountPaid            = $loan->amount_paid ?? 0;
            $loan->balance         = round($loan->total_to_return - $amountPaid, 2);

            if ($loan->balance <= 0) {
                $loan->status = 'paid';
            } elseif ($loan->due_date && \Carbon\Carbon::parse($loan->due_date)->isPast()) {
                $loan->status = 'overdue';
            } else {
                $loan->status = $loan->status === 'paid' ? 'pending' : ($loan->status ?? 'pending');
            }
        });

        static::saved(function ($loan) {
            optional($loan->meeting)->recalculateSummary();
        });

        static::deleted(function ($loan) {
            optional($loan->meeting)->recalculateSummary();
        });
    }
}

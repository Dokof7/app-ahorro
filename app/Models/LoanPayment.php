<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id', 'meeting_id', 'payment_date',
        'amount_paid', 'interest_paid', 'remaining_balance', 'observations'
    ];

    protected $casts = ['payment_date' => 'date'];

    public function loan() { return $this->belongsTo(Loan::class); }
    public function meeting() { return $this->belongsTo(Meeting::class); }

    protected static function booted()
    {
        static::saved(function ($payment) {
            self::recalculateLoan($payment);
        });

        static::deleted(function ($payment) {
            self::recalculateLoan($payment);
        });
    }

    private static function recalculateLoan(self $payment): void
    {
        $loan = $payment->loan;
        if (!$loan) return;

        $loan->amount_paid = round(
            $loan->payments()->sum('amount_paid') + $loan->payments()->sum('interest_paid'),
            2
        );
        $loan->balance     = round($loan->total_to_return - $loan->amount_paid, 2);

        if ($loan->balance <= 0) {
            $loan->status = 'paid';
        } elseif ($loan->due_date && \Carbon\Carbon::parse($loan->due_date)->isPast()) {
            $loan->status = 'overdue';
        } else {
            $loan->status = 'pending';
        }

        $loan->saveQuietly();
        optional($loan->meeting)->recalculateSummary();
        optional($payment->meeting)->recalculateSummary();
    }
}

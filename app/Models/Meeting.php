<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'group_id', 'meeting_number', 'meeting_date',
        'month', 'observations', 'status'
    ];

    protected $casts = ['meeting_date' => 'date'];

    public function group() { return $this->belongsTo(Group::class); }
    public function contributions() { return $this->hasMany(MeetingContribution::class); }
    public function totals() { return $this->hasOne(MeetingTotal::class); }
    public function attendances() { return $this->hasMany(Attendance::class); }
    public function loans() { return $this->hasMany(Loan::class); }
    public function loanPayments() { return $this->hasMany(LoanPayment::class); }
    public function fines() { return $this->hasMany(Fine::class); }
    public function bankExpenses() { return $this->hasMany(BankExpense::class); }
    public function summary() { return $this->hasOne(GeneralSummary::class); }

    public function isOpen() { return $this->status === 'open'; }
    public function isClosed() { return $this->status === 'closed'; }

    protected static function booted()
    {
        static::deleting(function ($meeting) {
            if ($meeting->loans()->whereIn('status', ['pending', 'overdue'])->exists()) {
                throw new \RuntimeException('No se puede eliminar una reunión con préstamos pendientes o vencidos.');
            }
        });
    }

    public function getTotalSavingsAttribute()
    {
        if ($this->group->isPartial()) {
            return $this->totals?->savings ?? 0;
        }
        return $this->contributions()->sum('savings');
    }

    public function getTotalEmergencyAttribute()
    {
        if ($this->group->isPartial()) {
            return $this->totals?->emergency_fund ?? 0;
        }
        return $this->contributions()->sum('emergency_fund');
    }

    public function getTotalFinesAttribute()
    {
        if ($this->group->isPartial()) {
            return $this->totals?->fine ?? 0;
        }
        return $this->contributions()->sum('fine');
    }

    public function getTotalLoansOutAttribute()
    {
        return $this->loans()->sum('amount');
    }

    public function getTotalInterestInAttribute()
    {
        return $this->loanPayments()->sum('interest_paid');
    }

    public function getTotalBankExpensesAttribute()
    {
        return $this->bankExpenses()->sum('amount');
    }

    public function recalculateSummary()
    {
        $previousMeeting = Meeting::where('group_id', $this->group_id)
            ->where('meeting_number', '<', $this->meeting_number)
            ->orderBy('meeting_number', 'desc')
            ->first();

        $previousTotal = $previousMeeting?->summary?->total_group_funds ?? 0;
        $incomeSavings = $this->total_savings;
        $loanOutflow = $this->total_loans_out;
        $bankExpenses = $this->total_bank_expenses;
        $totalGroupFunds = $previousTotal + $incomeSavings - $loanOutflow - $bankExpenses;
        $totalEmergencyFunds = $this->total_emergency;
        $incomeFines = $this->total_fines;
        $incomeInterest = $this->total_interest_in;

        $this->summary()->updateOrCreate(
            ['meeting_id' => $this->id],
            [
                'previous_total'        => $previousTotal,
                'income_savings'        => $incomeSavings,
                'loan_outflow'          => $loanOutflow,
                'total_group_funds'     => $totalGroupFunds,
                'total_emergency_funds' => $totalEmergencyFunds,
                'income_fines'          => $incomeFines,
                'income_interest'       => $incomeInterest,
                'bank_expenses_total'   => $bankExpenses,
            ]
        );
    }
}

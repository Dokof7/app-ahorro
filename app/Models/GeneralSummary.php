<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id', 'previous_total', 'income_savings',
        'loan_outflow', 'total_group_funds', 'total_emergency_funds',
        'income_fines', 'income_interest', 'bank_expenses_total'
    ];

    public function meeting() { return $this->belongsTo(Meeting::class); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id', 'meeting_id', 'expense_date',
        'concept', 'amount', 'observations'
    ];

    protected $casts = ['expense_date' => 'date'];

    public function group() { return $this->belongsTo(Group::class); }
    public function meeting() { return $this->belongsTo(Meeting::class); }

    protected static function booted()
    {
        static::saved(function ($expense) {
            optional($expense->meeting)->recalculateSummary();
        });

        static::deleted(function ($expense) {
            optional($expense->meeting)->recalculateSummary();
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id', 'name', 'activity_date',
        'location', 'notes', 'amount_raised',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'amount_raised' => 'decimal:2',
    ];

    public function group() { return $this->belongsTo(Group::class); }

    public function isCompleted(): bool
    {
        return $this->amount_raised !== null;
    }
}

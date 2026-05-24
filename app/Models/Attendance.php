<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id', 'member_id', 'attended', 'excused_absence',
        'paid_savings', 'paid_emergency', 'has_fine', 'observations'
    ];

    protected $casts = [
        'attended'        => 'boolean',
        'excused_absence' => 'boolean',
        'paid_savings'    => 'boolean',
        'paid_emergency'  => 'boolean',
        'has_fine'        => 'boolean',
    ];

    public function meeting() { return $this->belongsTo(Meeting::class); }
    public function member() { return $this->belongsTo(Member::class); }
}

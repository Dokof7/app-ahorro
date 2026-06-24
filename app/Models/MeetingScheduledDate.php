<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingScheduledDate extends Model
{
    protected $fillable = ['group_id', 'scheduled_date', 'notes', 'used'];

    protected $casts = [
        'scheduled_date' => 'date',
        'used'           => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}

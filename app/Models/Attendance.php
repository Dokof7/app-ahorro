<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    // 4-state attendance status
    public const STATUS_PRESENT = 'present';
    public const STATUS_LATE    = 'late';
    public const STATUS_ABSENT  = 'absent';
    public const STATUS_EXCUSED = 'excused';

    /**
     * Shared source of truth for status display: label, symbol, Bootstrap color.
     * Used by views and controllers to avoid duplication.
     */
    public static array $statusConfig = [
        self::STATUS_PRESENT => ['label' => 'Asistió',          'symbol' => '✓', 'color' => 'success'],
        self::STATUS_LATE    => ['label' => 'Atraso',           'symbol' => '○', 'color' => 'warning'],
        self::STATUS_ABSENT  => ['label' => 'Falta',            'symbol' => '✗', 'color' => 'danger'],
        self::STATUS_EXCUSED => ['label' => 'Falta c/Permiso',  'symbol' => 'L', 'color' => 'info'],
    ];

    /**
     * Only 'status' and 'observations' are written by the application.
     * The legacy boolean columns (attended, excused_absence, paid_savings,
     * paid_emergency, has_fine) remain in the DB for non-destructive migration
     * but are no longer read or written by code.
     */
    protected $fillable = [
        'meeting_id', 'member_id', 'status', 'observations',
    ];

    public function meeting() { return $this->belongsTo(Meeting::class); }
    public function member()  { return $this->belongsTo(Member::class); }
}

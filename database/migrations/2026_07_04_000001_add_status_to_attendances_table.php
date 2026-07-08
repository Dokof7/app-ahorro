<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status', 10)->default('absent')->after('member_id');
        });

        // Backfill existing rows:
        // - attended=1                         => 'present'
        // - excused_absence=1 AND attended=0   => 'excused'
        // - otherwise                          => 'absent'
        DB::statement("
            UPDATE attendances
            SET status = CASE
                WHEN attended = 1 THEN 'present'
                WHEN excused_absence = 1 AND attended = 0 THEN 'excused'
                ELSE 'absent'
            END
        ");
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

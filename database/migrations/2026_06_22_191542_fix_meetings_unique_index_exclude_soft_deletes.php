<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropUnique(['group_id', 'meeting_number']);
        });

        // MySQL treats NULLs as distinct in unique indexes, so active rows
        // (deleted_at IS NULL) enforce uniqueness while soft-deleted rows don't collide.
        DB::statement('ALTER TABLE meetings ADD UNIQUE KEY meetings_group_meeting_active_unique (group_id, meeting_number, deleted_at)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE meetings DROP INDEX meetings_group_meeting_active_unique');

        Schema::table('meetings', function (Blueprint $table) {
            $table->unique(['group_id', 'meeting_number']);
        });
    }
};

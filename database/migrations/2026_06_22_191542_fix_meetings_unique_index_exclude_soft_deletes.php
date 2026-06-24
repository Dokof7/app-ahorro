<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL blocks dropping a unique index when a FK uses it as its backing index.
        // We drop the FK on loans.meeting_id first, then remove the unique, then restore the FK.
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['meeting_id']);
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropUnique(['group_id', 'meeting_number']);
        });

        // Restore the FK now that the unique index is gone (MySQL will use the PK to back it).
        Schema::table('loans', function (Blueprint $table) {
            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
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

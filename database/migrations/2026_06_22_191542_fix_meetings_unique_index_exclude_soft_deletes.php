<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only these 4 FKs exist in production pointing at meetings.id.
        // meeting_contributions, attendances and loans have no FK in this DB.
        Schema::table('bank_expenses',     fn($t) => $t->dropForeign(['meeting_id']));
        Schema::table('fines',             fn($t) => $t->dropForeign(['meeting_id']));
        Schema::table('general_summaries', fn($t) => $t->dropForeign(['meeting_id']));
        Schema::table('loan_payments',     fn($t) => $t->dropForeign(['meeting_id']));

        Schema::table('meetings', fn($t) => $t->dropUnique(['group_id', 'meeting_number']));

        // Restore FKs (now backed by PK, not the unique index).
        Schema::table('bank_expenses',     fn($t) => $t->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade'));
        Schema::table('fines',             fn($t) => $t->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade'));
        Schema::table('general_summaries', fn($t) => $t->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade'));
        Schema::table('loan_payments',     fn($t) => $t->foreign('meeting_id')->references('id')->on('meetings')->onDelete('set null'));

        // MySQL treats NULLs as distinct in unique indexes, so active rows
        // (deleted_at IS NULL) enforce uniqueness while soft-deleted rows don't collide.
        DB::statement('ALTER TABLE meetings ADD UNIQUE KEY meetings_group_meeting_active_unique (group_id, meeting_number, deleted_at)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE meetings DROP INDEX meetings_group_meeting_active_unique');

        Schema::table('meetings', fn($t) => $t->unique(['group_id', 'meeting_number']));
    }
};

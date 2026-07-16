<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // sqlite (used by the test suite) doesn't have MySQL's FK-vs-unique-index
        // coupling, so it can drop/add the unique index directly without the
        // FK-drop/restore dance MySQL requires. End schema state is identical:
        // unique(group_id, meeting_number, deleted_at) replaces
        // unique(group_id, meeting_number).
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('meetings', fn($t) => $t->dropUnique(['group_id', 'meeting_number']));
            Schema::table('meetings', fn($t) => $t->unique(['group_id', 'meeting_number', 'deleted_at'], 'meetings_group_meeting_active_unique'));
            return;
        }

        // The unique index (group_id, meeting_number) is the backing index for
        // meetings_group_id_foreign (meetings.group_id → groups.id).
        // MySQL refuses to drop the unique while that FK exists, so we drop it first.
        // The other 4 FKs (bank_expenses, fines, general_summaries, loan_payments)
        // were already dropped by a previous failed run, so we guard with IF EXISTS.
        DB::statement('ALTER TABLE meetings DROP FOREIGN KEY meetings_group_id_foreign');

        Schema::table('meetings', fn($t) => $t->dropUnique(['group_id', 'meeting_number']));

        // Restore meetings.group_id FK (now backed by the PK-adjacent index).
        Schema::table('meetings', fn($t) =>
            $t->foreign('group_id')->references('id')->on('groups')->onDelete('cascade')
        );

        // Re-add the 4 child FKs only if they were lost in previous failed runs.
        $existing = collect(DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND CONSTRAINT_TYPE = "FOREIGN KEY" AND TABLE_NAME IN ("bank_expenses","fines","general_summaries","loan_payments")'
        ))->pluck('CONSTRAINT_NAME');

        if (!$existing->contains('bank_expenses_meeting_id_foreign')) {
            Schema::table('bank_expenses', fn($t) => $t->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade'));
        }
        if (!$existing->contains('fines_meeting_id_foreign')) {
            Schema::table('fines', fn($t) => $t->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade'));
        }
        if (!$existing->contains('general_summaries_meeting_id_foreign')) {
            Schema::table('general_summaries', fn($t) => $t->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade'));
        }
        if (!$existing->contains('loan_payments_meeting_id_foreign')) {
            Schema::table('loan_payments', fn($t) => $t->foreign('meeting_id')->references('id')->on('meetings')->onDelete('set null'));
        }

        // MySQL treats NULLs as distinct in unique indexes, so active rows
        // (deleted_at IS NULL) enforce uniqueness while soft-deleted rows don't collide.
        DB::statement('ALTER TABLE meetings ADD UNIQUE KEY meetings_group_meeting_active_unique (group_id, meeting_number, deleted_at)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('meetings', fn($t) => $t->dropUnique('meetings_group_meeting_active_unique'));
            Schema::table('meetings', fn($t) => $t->unique(['group_id', 'meeting_number']));
            return;
        }

        DB::statement('ALTER TABLE meetings DROP INDEX meetings_group_meeting_active_unique');

        Schema::table('meetings', fn($t) => $t->unique(['group_id', 'meeting_number']));
    }
};

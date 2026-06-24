<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // All tables that have a FK pointing at meetings.id.
    // MySQL uses the unique index as the backing index for any of these,
    // so every FK must be dropped before the unique index can be removed.
    private array $fkTables = [
        'meeting_contributions',
        'attendances',
        'loans',
        'loan_payments',
        'fines',
        'bank_expenses',
        'general_summaries',
    ];

    public function up(): void
    {
        foreach ($this->fkTables as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropForeign(['meeting_id']);
            });
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropUnique(['group_id', 'meeting_number']);
        });

        // Restore all FKs (now backed by the PK, not the unique index).
        foreach ($this->fkTables as $t) {
            $onDelete = $t === 'loan_payments' ? 'set null' : 'cascade';
            Schema::table($t, function (Blueprint $table) use ($onDelete) {
                $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete($onDelete);
            });
        }

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

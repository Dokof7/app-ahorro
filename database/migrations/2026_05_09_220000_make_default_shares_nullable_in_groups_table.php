<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The raw MODIFY COLUMN statement is MySQL-specific syntax and is a
     * no-op on other drivers (e.g. sqlite, used in the test suite), where
     * columns are not NOT NULL-enforced the same way and this migration
     * would otherwise abort the whole migration run.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `groups` MODIFY `default_shares` tinyint unsigned NULL DEFAULT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `groups` MODIFY `default_shares` tinyint unsigned NOT NULL DEFAULT 1');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE users SET role = 'secretario' WHERE role = 'user'");

        // The ENUM redefinition is MySQL-specific syntax and is a no-op on
        // other drivers (e.g. sqlite, used in the test suite). The `role`
        // column is dropped entirely by a later migration
        // (sync_existing_roles_to_spatie), so skipping the constraint
        // narrowing here has no effect on the final schema.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','tesorero','secretario','observador') NOT NULL DEFAULT 'observador'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'user' WHERE role IN ('tesorero','secretario','observador')");

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user'");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE users SET role = 'secretario' WHERE role = 'user'");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','tesorero','secretario','observador') NOT NULL DEFAULT 'observador'");
    }

    public function down(): void
    {
        DB::statement("UPDATE users SET role = 'user' WHERE role IN ('tesorero','secretario','observador')");
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user'");
    }
};

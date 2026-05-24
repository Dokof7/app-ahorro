<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meeting_contributions', function (Blueprint $table) {
            $table->unsignedTinyInteger('shares')->default(1)->after('member_id');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_contributions', function (Blueprint $table) {
            $table->dropColumn('shares');
        });
    }
};

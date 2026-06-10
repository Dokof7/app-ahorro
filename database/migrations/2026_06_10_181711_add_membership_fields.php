<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->decimal('membership_fee', 8, 2)->default(0)->after('default_emergency');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->boolean('membership_paid')->default(false)->after('status');
            $table->timestamp('membership_paid_at')->nullable()->after('membership_paid');
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('membership_fee');
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['membership_paid', 'membership_paid_at']);
        });
    }
};

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
        Schema::table('groups', function (Blueprint $table) {
            $table->decimal('share_value', 10, 2)->default(25)->after('default_savings');
            $table->unsignedTinyInteger('default_shares')->default(1)->after('share_value');
            // default_savings se mantiene para compatibilidad pero ya no se usa en el formulario
        });
    }

    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['share_value', 'default_shares']);
        });
    }
};

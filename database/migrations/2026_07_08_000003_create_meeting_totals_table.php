<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_totals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('shares')->default(0);
            $table->decimal('savings', 10, 2)->default(0);
            $table->decimal('emergency_fund', 10, 2)->default(0);
            $table->decimal('fine', 10, 2)->default(0);
            $table->string('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_totals');
    }
};

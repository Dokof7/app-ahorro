<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->boolean('attended')->default(false);
            $table->boolean('paid_savings')->default(false);
            $table->boolean('paid_emergency')->default(false);
            $table->boolean('has_fine')->default(false);
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->unique(['meeting_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_scheduled_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->string('notes')->nullable();
            $table->boolean('used')->default(false);
            $table->timestamps();

            $table->unique(['group_id', 'scheduled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_scheduled_dates');
    }
};

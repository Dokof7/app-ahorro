<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->decimal('previous_total', 10, 2)->default(0);
            $table->decimal('income_savings', 10, 2)->default(0);
            $table->decimal('loan_outflow', 10, 2)->default(0);
            $table->decimal('total_group_funds', 10, 2)->default(0);
            $table->decimal('total_emergency_funds', 10, 2)->default(0);
            $table->decimal('income_fines', 10, 2)->default(0);
            $table->decimal('income_interest', 10, 2)->default(0);
            $table->decimal('bank_expenses_total', 10, 2)->default(0);
            $table->timestamps();
            $table->unique('meeting_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_summaries');
    }
};

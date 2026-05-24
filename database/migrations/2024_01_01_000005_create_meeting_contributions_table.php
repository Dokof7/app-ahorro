<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->decimal('savings', 10, 2)->default(0);
            $table->decimal('emergency_fund', 10, 2)->default(0);
            $table->decimal('fine', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->boolean('confirmed')->default(false);
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->unique(['meeting_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_contributions');
    }
};

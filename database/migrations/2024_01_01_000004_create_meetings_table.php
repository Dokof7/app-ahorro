<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->integer('meeting_number');
            $table->date('meeting_date');
            $table->string('month', 20);
            $table->text('observations')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['group_id', 'meeting_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};

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
        Schema::create('meeting_event_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_event_id')->constrained('meeting_events')->cascadeOnDelete();
            $table->integer('duration')->default(60);
            $table->string('timezone', 50)->default('UTC');
            $table->enum('schedule_mode', ['future_days', 'date_range'])->default('future_days');
            $table->integer('future_days')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_event_schedules');
    }
};

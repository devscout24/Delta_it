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
        Schema::create('meeting_event_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('meeting_event_schedule_id')
                ->constrained('meeting_event_schedules')
                ->cascadeOnDelete();

            $table->time('start_time');
            $table->time('end_time');

            $table->boolean('is_booked')->default(false);

            $table->timestamps();

            $table->index(['meeting_event_schedule_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_event_slots');
    }
};

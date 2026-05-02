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

            $table->foreignId('event_id')
                ->constrained('meeting_events')
                ->cascadeOnDelete();

            $table->date('date');

            $table->time('start_time');
            $table->time('end_time');

            $table->boolean('is_booked')->default(false);

            $table->timestamps();

            $table->index(['event_id', 'date']);
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

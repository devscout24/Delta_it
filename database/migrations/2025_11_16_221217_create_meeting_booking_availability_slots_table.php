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
        Schema::create('meeting_booking_availability_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('meeting_booking_schedules')->cascadeOnDelete();
            $table->enum('day', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_booking_availability_slots');
    }
};

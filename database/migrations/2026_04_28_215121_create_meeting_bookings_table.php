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
        Schema::create('meeting_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('meeting_events')
                ->cascadeOnDelete();

            $table->date('date');

            $table->time('start_time');
            $table->time('end_time');

            $table->string('name')->nullable(); // user name (mobile)
            $table->string('email')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending');

            $table->timestamps();

            // ⚡ prevent duplicate bookings
            $table->index(['event_id', 'date', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_bookings');
    }
};

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
            $table->foreignId('meeting_event_id')->constrained('meeting_events')->onDelete('cascade');
            $table->date('date');
            $table->time('slot_start');
            $table->time('slot_end');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // invitee
            $table->timestamps();
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

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
        Schema::create('room_book_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_book_weely_schedule_id')->constrained('room_book_weely_schedules')->onDelete('cascade');
            $table->foreignId('room_book_id')->nullable()->constrained('room_books')->onDelete('cascade');
            $table->foreignId('room_id')->references('id')->on('rooms')->onDelete('cascade');
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time');  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_book_slots');
    }
};

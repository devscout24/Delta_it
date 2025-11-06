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
        Schema::create('meeting_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // event owner
            $table->string('event_name');
            $table->string('location')->nullable();
            $table->string('color')->nullable();
            $table->string('meeting_link')->nullable(); // zoom/google meet
            $table->integer('max_invitees')->default(1); // default 1 person
            $table->text('description')->nullable();
            $table->integer('duration'); // duration in minutes (e.g., 60)
            $table->string('timezone')->default('UTC');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_events');
    }
};

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

            $table->string('title');

            $table->enum('type', ['virtual', 'physical']);

            $table->string('location')->nullable(); // room name
            $table->string('meeting_link')->nullable();

            $table->integer('duration'); // minutes
            $table->integer('max_invitees')->default(1);

            $table->text('description')->nullable();
            $table->string('color')->nullable();

            $table->string('timezone')->nullable(); // IMPORTANT ADD

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

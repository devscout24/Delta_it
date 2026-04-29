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
            $table->string('type')->default('virtual'); // virtual / physical

            $table->string('location')->nullable(); // if physical
            $table->string('meeting_link')->nullable(); // if virtual

            $table->integer('duration'); // in minutes
            $table->integer('max_invitees')->default(1);

            $table->text('description')->nullable();

            $table->string('color')->nullable(); // UI

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

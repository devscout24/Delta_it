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
        Schema::create('meeting_event_schedule_days', function (Blueprint $table) {
            $table->id();

            $table->foreignId('schedule_id')
                ->constrained('meeting_event_schedules')
                ->cascadeOnDelete();

            $table->string('day_of_week'); // mon, tue, wed

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
        Schema::dropIfExists('meeting_event_schedule_days');
    }
};

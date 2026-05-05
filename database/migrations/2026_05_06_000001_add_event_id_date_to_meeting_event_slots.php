<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Old slots have no event_id or date — they are unusable. Clear them
        // so the NOT NULL columns can be added without constraint failures.
        DB::table('meeting_event_slots')->truncate();

        Schema::table('meeting_event_slots', function (Blueprint $table) {
            $table->foreignId('event_id')
                ->after('id')
                ->constrained('meeting_events')
                ->cascadeOnDelete();

            $table->date('date')->after('event_id');

            $table->index(['event_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('meeting_event_slots', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropIndex(['event_id', 'date']);
            $table->dropColumn(['event_id', 'date']);
        });
    }
};

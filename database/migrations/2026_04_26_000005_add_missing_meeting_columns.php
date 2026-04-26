<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('meetings', 'add_emails')) {
                $table->json('add_emails')->nullable();
            }
        });

        DB::statement("ALTER TABLE meetings MODIFY meeting_type ENUM('virtual', 'office', 'physical') NOT NULL DEFAULT 'virtual'");
        DB::statement("ALTER TABLE meetings MODIFY status ENUM('pending', 'completed', 'cancelled', 'requested', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            if (Schema::hasColumn('meetings', 'add_emails')) {
                $table->dropColumn('add_emails');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update the status enum to include new values
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open', 'pending', 'in_progress', 'in-progress', 'unsolved', 'solved', 'resolved', 'closed') DEFAULT 'open'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open'");
    }
};

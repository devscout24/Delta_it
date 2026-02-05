<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contract_notification_logs')) {
            Schema::create('contract_notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
                $table->integer('days_remaining')->comment('Number of days remaining before expiry when reminder was sent');
                $table->timestamp('sent_at')->nullable()->comment('When the notification was sent');
                $table->timestamps();

                // Create unique index to prevent duplicate reminders
                $table->unique(['contract_id', 'days_remaining', 'sent_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_notification_logs');
    }
};

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
        // Add missing columns to tickets table
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'unique_id')) {
                $table->string('unique_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('tickets', 'requester_id')) {
                $table->foreignId('requester_id')->nullable()->after('user_id')->constrained('users')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('tickets', 'requester_role')) {
                $table->string('requester_role')->nullable()->after('requester_id');
            }
            if (!Schema::hasColumn('tickets', 'date')) {
                $table->date('date')->nullable()->after('status');
            }
            if (!Schema::hasColumn('tickets', 'action')) {
                $table->string('action')->nullable()->after('date');
            }
        });

        // Add missing columns to ticket_messages table
        Schema::table('ticket_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_messages', 'sender_id')) {
                $table->foreignId('sender_id')->nullable()->after('user_id')->constrained('users')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('ticket_messages', 'message_type')) {
                $table->string('message_type')->default('text')->after('message');
            }
            if (!Schema::hasColumn('ticket_messages', 'message_text')) {
                $table->text('message_text')->nullable()->after('message_type');
            }
            if (!Schema::hasColumn('ticket_messages', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('message_text');
            }
        });

        // Add missing columns to ticket_attachments table
        Schema::table('ticket_attachments', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_attachments', 'file_type')) {
                $table->string('file_type')->nullable()->after('file_path');
            }
            if (!Schema::hasColumn('ticket_attachments', 'file_size')) {
                $table->bigInteger('file_size')->nullable()->after('file_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_attachments', function (Blueprint $table) {
            $table->dropColumn(['file_type', 'file_size']);
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropColumn(['sender_id', 'message_type', 'message_text', 'is_read']);
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['unique_id', 'requester_id', 'requester_role', 'date', 'action']);
        });
    }
};

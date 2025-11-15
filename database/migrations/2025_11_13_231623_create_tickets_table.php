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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();

            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->string('subject');
            $table->string('type')->nullable();
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();

            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->enum('requester_role', ['admin', 'user']);

            $table->date('date')->nullable();

            $table->string('action')->nullable();

            $table->enum('status', ['pending', 'in-progress', 'unsolved', 'solved'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

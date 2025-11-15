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
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('job_position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_extension')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('access_card_number', 50)->nullable();
            $table->boolean('parking_card')->default(false);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaborators');
    }
};

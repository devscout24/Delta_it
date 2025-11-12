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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('fiscal_name', 255)->nullable();
            $table->string('nif', 50)->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('incubation_type', ['virtual', 'on-site', 'cowork', 'colab'])->nullable();
            $table->string('business_area')->nullable();
            $table->string('manager', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};

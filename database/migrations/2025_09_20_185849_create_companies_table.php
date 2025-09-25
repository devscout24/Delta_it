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
            $table->string('commercial_name', 255);
            $table->string('fiscal_name', 255)->nullable();
            $table->string('company_email')->unique();
            $table->integer('nif')->nullable();
            $table->string('phone_number', 11)->nullable();
            $table->enum('incubation_type', ['virtual', 'on-site', 'cowork', 'colab']);
            $table->longText('occupied_office', 11)->nullable();
            $table->string('occupied_area', 11)->nullable();
            $table->longText('bussiness_area', 11)->nullable();
            $table->string('company_manager', 100)->nullable();
            $table->string('description', 255)->nullable();
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

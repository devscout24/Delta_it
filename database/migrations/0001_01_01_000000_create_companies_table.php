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

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('nif')->nullable(); // tax id

            $table->string('incubation_type')->nullable(); // virtual, onsite, etc.
            $table->string('business_area')->nullable();

            $table->string('manager_name')->nullable();

            $table->text('description')->nullable();
            $table->string('logo')->nullable();

            $table->enum('status', ['active', 'archived'])->default('active');

            $table->boolean('is_active')->default(true);

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

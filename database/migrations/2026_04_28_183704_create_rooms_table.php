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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('floor_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name'); // Room A, Office 201
            $table->decimal('area', 8, 2)->nullable();

            $table->json('polygon_points')->nullable(); // for map drawing

            $table->enum('status', ['available', 'occupied', 'maintenance'])
                ->default('available');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};

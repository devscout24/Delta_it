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
        Schema::create('access_cards', function (Blueprint $table) {
               $table->id();
            $table->integer('active_card')->default(0);
            $table->integer('lost_damage_card')->default(0);
            $table->integer('active_parking_card')->default(0);
            $table->integer('max_parking_card')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_cards');
    }
};

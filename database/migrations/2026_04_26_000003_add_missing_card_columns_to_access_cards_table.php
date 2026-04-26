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
        Schema::table('access_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('access_cards', 'active_card')) {
                $table->integer('active_card')->default(0);
            }

            if (!Schema::hasColumn('access_cards', 'lost_damage_card')) {
                $table->integer('lost_damage_card')->default(0);
            }

            if (!Schema::hasColumn('access_cards', 'active_parking_card')) {
                $table->integer('active_parking_card')->default(0);
            }

            if (!Schema::hasColumn('access_cards', 'max_parking_card')) {
                $table->integer('max_parking_card')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_cards', function (Blueprint $table) {
            foreach ([
                'max_parking_card',
                'active_parking_card',
                'lost_damage_card',
                'active_card',
            ] as $column) {
                if (Schema::hasColumn('access_cards', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

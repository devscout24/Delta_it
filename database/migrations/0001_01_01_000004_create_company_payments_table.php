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
        Schema::create('company_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');

            $table->year('year');
            $table->unsignedTinyInteger('month');

            $table->decimal('value_non_vat', 10, 2)->default(0);
            $table->decimal('value_vat', 10, 2)->default(0);
            $table->decimal('printings_non_vat', 10, 2)->default(0);
            $table->decimal('printings_vat', 10, 2)->default(0);

            $table->decimal('total_vat', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);

            $table->enum('status', ['unpaid', 'paid'])->default('unpaid');
            $table->timestamps();

            $table->unique(['company_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_payments');
    }
};

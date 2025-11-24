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
        Schema::create('internal_document_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_document_id')->constrained('internal_documents')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_type')->nullable();  // pdf / word / image
            $table->string('file_name')->nullable();  // original name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_document_files');
    }
};

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
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            }

            if (!Schema::hasColumn('documents', 'document_name')) {
                $table->string('document_name')->nullable();
            }

            if (!Schema::hasColumn('documents', 'document_type')) {
                $table->string('document_type')->nullable();
            }

            if (!Schema::hasColumn('documents', 'document_path')) {
                $table->string('document_path')->nullable();
            }
        });

        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
                $table->string('tag');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');

        Schema::table('documents', function (Blueprint $table) {
            foreach ([
                'document_path',
                'document_type',
                'document_name',
                'company_id',
            ] as $column) {
                if (Schema::hasColumn('documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

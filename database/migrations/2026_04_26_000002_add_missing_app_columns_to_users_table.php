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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'company_id')) {
                $table->integer('company_id')->nullable();
            }

            if (!Schema::hasColumn('users', 'job_position')) {
                $table->string('job_position')->nullable();
            }

            if (!Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable();
            }

            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 50)->nullable()->unique();
            }

            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable();
            }

            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable();
            }

            if (!Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type', 255)->default('user');
            }

            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }

            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            }

            if (!Schema::hasColumn('users', 'terms_and_conditions')) {
                $table->boolean('terms_and_conditions')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'terms_and_conditions',
                'status',
                'email_verified_at',
                'user_type',
                'phone',
                'last_name',
                'username',
                'profile_photo',
                'job_position',
                'company_id',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

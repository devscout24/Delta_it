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
        Schema::create('users', function (Blueprint $table) {
            // user personal info
            $table->id();
            $table->integer('company_id')->nullable();
            $table->string('username', 50)->nullable()->unique();
            $table->string('name', 100);
            $table->string('last_name')->nullable();
            $table->string('email', 100)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('zipcode', 20)->nullable();
            $table->string('password');
            $table->string('profile_photo')->nullable();
            $table->string('user_type', 255);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_otp')->nullable();
            $table->timestamp('email_otp_expires_at')->nullable();
            $table->string('password_otp', 10)->nullable();
            $table->timestamp('password_otp_expired_at')->nullable();
            $table->timestamp('password_otp_verified_at')->nullable();
            $table->string('password_reset_token')->nullable();
            $table->timestamp('password_reset_token_expires_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'banned'])->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->string('account_delete_reason', 255)->nullable();
            $table->boolean('terms_and_conditions')->default(false);
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

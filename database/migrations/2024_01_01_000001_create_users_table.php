<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Users Table Migration
 * Enterprise Email Validation Platform
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable(); // nullable for social login
            $table->string('avatar')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('company')->nullable();
            $table->string('country', 2)->nullable(); // ISO 2 country code
            $table->string('timezone')->default('UTC');
            $table->enum('status', ['active', 'inactive', 'suspended', 'banned'])->default('active');
            $table->enum('role', ['admin', 'reseller', 'user'])->default('user');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_token')->nullable()->index();
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->string('password_reset_token')->nullable()->index();
            $table->timestamp('password_reset_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable(); // supports IPv6
            $table->bigInteger('credit_balance')->default(0)->unsigned(); // credits in cents
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('reseller_id')->nullable()->index();
            $table->json('settings')->nullable(); // user preferences
            $table->string('white_label_domain')->nullable()->unique();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            // Indexes for performance
            $table->index(['email', 'status']);
            $table->index(['role', 'status']);
            $table->index('created_at');
        });

        // Social Auth Providers
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('provider'); // google, github, facebook
            $table->string('provider_id')->index();
            $table->string('provider_token')->nullable();
            $table->string('provider_refresh_token')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['provider', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
        Schema::dropIfExists('users');
    }
};

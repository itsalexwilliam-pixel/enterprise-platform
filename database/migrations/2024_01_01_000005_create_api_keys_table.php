<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name'); // human-readable name
            $table->string('key', 64)->unique(); // the actual API key
            $table->string('key_prefix', 8); // first 8 chars for display
            $table->enum('status', ['active', 'inactive', 'revoked'])->default('active');
            $table->integer('rate_limit_per_minute')->default(60);
            $table->integer('rate_limit_per_day')->default(10000);
            $table->bigInteger('total_requests')->default(0)->unsigned();
            $table->bigInteger('requests_today')->default(0)->unsigned();
            $table->json('allowed_ips')->nullable(); // IP whitelist
            $table->json('permissions')->nullable(); // scope limits
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['key', 'status']);
        });

        // API Request Logs
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('api_key_id')->nullable()->index();
            $table->string('method', 10);
            $table->string('endpoint', 255);
            $table->integer('response_code');
            $table->integer('response_time_ms'); // milliseconds
            $table->string('ip_address', 45);
            $table->string('email_validated')->nullable()->index(); // if it was an email validation
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('created_at'); // No updated_at needed for logs

            $table->index(['created_at', 'user_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('api_keys');
    }
};

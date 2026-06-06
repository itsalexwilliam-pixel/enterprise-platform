<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Webhooks
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->string('url');
            $table->string('secret', 64)->nullable(); // HMAC signing secret
            $table->json('events'); // array of events to listen to
            $table->enum('status', ['active', 'inactive', 'failed'])->default('active');
            $table->integer('success_count')->default(0)->unsigned();
            $table->integer('failure_count')->default(0)->unsigned();
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();
            $table->integer('timeout_seconds')->default(10);
            $table->integer('retry_count')->default(3);
            $table->softDeletes();
            $table->timestamps();
        });

        // Webhook Delivery Logs
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('webhook_id')->index();
            $table->string('event');
            $table->json('payload');
            $table->integer('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->enum('status', ['success', 'failed', 'pending', 'retrying'])->default('pending');
            $table->integer('attempt')->default(1);
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('created_at');

            $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('cascade');
            $table->index(['webhook_id', 'status', 'created_at']);
        });

        // SMTP Validation Logs (detailed SMTP conversation)
        Schema::create('smtp_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('validation_result_id')->nullable()->index();
            $table->unsignedBigInteger('smtp_server_id')->nullable()->index();
            $table->string('email', 320)->index();
            $table->string('mx_host', 255);
            $table->string('mx_ip', 45);
            $table->integer('port')->default(25);
            $table->text('conversation')->nullable(); // full SMTP conversation
            $table->string('helo_response')->nullable();
            $table->string('mail_from_response')->nullable();
            $table->string('rcpt_to_response')->nullable();
            $table->integer('rcpt_to_code')->nullable();
            $table->boolean('connection_success')->default(false);
            $table->boolean('helo_success')->default(false);
            $table->boolean('mail_from_success')->default(false);
            $table->boolean('rcpt_to_success')->nullable(); // null = unknown
            $table->integer('duration_ms')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('created_at');

            $table->index('created_at');
        });

        // Audit Logs (security trail)
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 100)->index(); // e.g. 'user.login', 'api_key.created'
            $table->string('entity_type', 100)->nullable(); // model name
            $table->unsignedBigInteger('entity_id')->nullable(); // model ID
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('context')->nullable(); // extra context
            $table->timestamp('created_at');

            $table->index(['user_id', 'action', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });

        // Worker Status Tracking
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('worker_id')->unique(); // e.g. smtp_1, dns_2
            $table->string('hostname');
            $table->string('container_id')->nullable();
            $table->enum('type', ['smtp', 'dns', 'bulk', 'webhook', 'report'])->index();
            $table->enum('status', ['running', 'idle', 'stopped', 'crashed'])->default('idle');
            $table->bigInteger('jobs_processed')->default(0)->unsigned();
            $table->bigInteger('jobs_failed')->default(0)->unsigned();
            $table->integer('current_job_id')->nullable();
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->integer('memory_usage_mb')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamps();
        });

        // Failed Jobs
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('workers');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('smtp_logs');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
    }
};

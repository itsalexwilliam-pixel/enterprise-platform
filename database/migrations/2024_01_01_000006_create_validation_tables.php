<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Validation Jobs (Bulk Uploads)
        Schema::create('validation_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('uuid', 36)->unique(); // public job reference
            $table->string('name'); // job name
            $table->string('filename')->nullable(); // original filename
            $table->string('file_path')->nullable(); // storage path
            $table->enum('file_type', ['csv', 'xlsx', 'txt'])->nullable();
            $table->enum('status', [
                'pending',      // uploaded, waiting
                'processing',   // actively validating
                'completed',    // all done
                'failed',       // critical error
                'cancelled',    // user cancelled
                'partial',      // some failed
            ])->default('pending');
            $table->bigInteger('total_emails')->default(0)->unsigned();
            $table->bigInteger('processed_emails')->default(0)->unsigned();
            $table->bigInteger('valid_emails')->default(0)->unsigned();
            $table->bigInteger('invalid_emails')->default(0)->unsigned();
            $table->bigInteger('risky_emails')->default(0)->unsigned();
            $table->bigInteger('unknown_emails')->default(0)->unsigned();
            $table->bigInteger('disposable_count')->default(0)->unsigned();
            $table->bigInteger('catch_all_count')->default(0)->unsigned();
            $table->bigInteger('credits_used')->default(0)->unsigned();
            $table->bigInteger('credits_refunded')->default(0)->unsigned();
            $table->decimal('progress', 5, 2)->default(0); // 0-100%
            $table->string('error_message')->nullable();
            $table->string('download_token', 64)->nullable()->unique(); // for secure download
            $table->timestamp('download_expires_at')->nullable();
            $table->integer('processing_speed')->nullable(); // emails/sec
            $table->integer('estimated_seconds')->nullable(); // ETA
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('settings')->nullable(); // validation options
            $table->json('summary')->nullable(); // final report summary
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('uuid');
        });

        // Individual Validation Results
        Schema::create('validation_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id')->nullable()->index(); // null for single API checks
            $table->unsignedBigInteger('user_id')->index();
            $table->string('email', 320)->index(); // max RFC email length
            $table->string('local_part', 64); // part before @
            $table->string('domain', 255)->index();

            // Status
            $table->enum('status', [
                'valid',
                'invalid',
                'risky',
                'unknown',
                'catch_all',
                'disposable',
                'spam_trap',
                'unverifiable',
            ])->index();

            // Score (0-100)
            $table->tinyInteger('score')->unsigned()->default(0);

            // Syntax Checks
            $table->boolean('syntax_valid')->default(false);
            $table->string('syntax_error')->nullable();

            // DNS Checks
            $table->boolean('mx_found')->default(false);
            $table->string('mx_record')->nullable();
            $table->integer('mx_priority')->nullable();
            $table->boolean('a_record_found')->default(false);
            $table->boolean('spf_found')->default(false);
            $table->string('spf_record')->nullable();
            $table->boolean('dmarc_found')->default(false);
            $table->string('dmarc_record')->nullable();

            // SMTP Checks
            $table->boolean('smtp_connectable')->default(false);
            $table->boolean('smtp_valid')->default(false);
            $table->string('smtp_banner')->nullable();
            $table->string('smtp_response')->nullable();
            $table->integer('smtp_response_code')->nullable();
            $table->boolean('catch_all')->default(false);
            $table->boolean('greylisted')->default(false);

            // Classification Flags
            $table->boolean('is_disposable')->default(false);
            $table->boolean('is_role_based')->default(false);
            $table->boolean('is_free_email')->default(false);
            $table->boolean('is_catch_all')->default(false);
            $table->boolean('is_spam_trap')->default(false);
            $table->boolean('is_honeypot')->default(false);
            $table->boolean('is_toxic_domain')->default(false);
            $table->boolean('is_recently_active')->default(false);

            // Provider Detection
            $table->string('mailbox_provider')->nullable(); // gmail, outlook, yahoo, etc.
            $table->string('provider_type')->nullable(); // free, paid, corporate

            // Scoring Factors (JSON breakdown)
            $table->json('score_breakdown')->nullable();

            // Timing
            $table->integer('validation_time_ms')->nullable();
            $table->string('validated_from_ip', 45)->nullable(); // which SMTP server was used

            // Cache flag
            $table->boolean('from_cache')->default(false);
            $table->timestamp('cache_expires_at')->nullable();

            $table->timestamps();

            // Composite indexes for reporting
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['job_id', 'status']);
            $table->index(['domain', 'status']);
            $table->index('created_at');

            // For deduplication lookups
            $table->index(['email', 'created_at']);

            // Foreign key
            $table->foreign('job_id')->references('id')->on('validation_jobs')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validation_results');
        Schema::dropIfExists('validation_jobs');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Domain Cache Table (speeds up repeated domain lookups)
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255)->unique()->index();
            $table->boolean('mx_found')->default(false);
            $table->boolean('a_record_found')->default(false);
            $table->boolean('spf_found')->default(false);
            $table->boolean('dmarc_found')->default(false);
            $table->boolean('is_disposable')->default(false);
            $table->boolean('is_free_email')->default(false);
            $table->boolean('is_catch_all')->default(false);
            $table->boolean('is_toxic')->default(false);
            $table->boolean('is_spam_trap')->default(false);
            $table->tinyInteger('reputation_score')->default(50); // 0-100
            $table->string('mailbox_provider')->nullable();
            $table->bigInteger('validation_count')->default(0)->unsigned();
            $table->decimal('valid_rate', 5, 2)->default(0); // % valid
            $table->json('mx_records')->nullable();
            $table->string('spf_record')->nullable();
            $table->string('dmarc_record')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('cache_expires_at')->nullable();
            $table->timestamps();

            $table->index(['is_disposable', 'is_toxic']);
            $table->index('reputation_score');
        });

        // MX Records Cache
        Schema::create('mx_records', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255)->index();
            $table->string('mx_host', 255);
            $table->integer('priority')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_reachable')->default(false);
            $table->integer('port_25_open')->default(0);
            $table->integer('port_465_open')->default(0);
            $table->integer('port_587_open')->default(0);
            $table->string('banner')->nullable();
            $table->string('smtp_server_type')->nullable(); // postfix, exchange, etc.
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['domain', 'mx_host']);
            $table->index('mx_host');
        });

        // Disposable Email Domains Database (100,000+)
        Schema::create('disposable_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255)->unique()->index();
            $table->string('source')->nullable(); // where it came from
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        // Free Email Providers
        Schema::create('free_email_providers', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255)->unique()->index();
            $table->string('provider_name')->nullable(); // Gmail, Yahoo, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Role-based email prefixes
        Schema::create('role_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 100)->unique()->index();
            $table->enum('type', ['role', 'abuse', 'generic'])->default('role');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Known Spam Trap Domains
        Schema::create('spam_trap_domains', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255)->unique()->index();
            $table->enum('type', ['spam_trap', 'honeypot', 'toxic'])->default('spam_trap');
            $table->string('source')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // SMTP Server Configuration (for validation)
        Schema::create('smtp_servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address', 45);
            $table->string('hostname');
            $table->string('helo_domain');
            $table->string('from_email');
            $table->enum('status', ['active', 'inactive', 'banned', 'cooling_down'])->default('active');
            $table->integer('reputation_score')->default(100);
            $table->bigInteger('total_connections')->default(0)->unsigned();
            $table->bigInteger('successful_connections')->default(0)->unsigned();
            $table->bigInteger('failed_connections')->default(0)->unsigned();
            $table->integer('max_connections_per_minute')->default(50);
            $table->integer('current_connections')->default(0);
            $table->timestamp('banned_until')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->json('blacklisted_on')->nullable(); // RBL listings
            $table->timestamps();

            $table->index(['status', 'reputation_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smtp_servers');
        Schema::dropIfExists('spam_trap_domains');
        Schema::dropIfExists('role_keywords');
        Schema::dropIfExists('free_email_providers');
        Schema::dropIfExists('disposable_domains');
        Schema::dropIfExists('mx_records');
        Schema::dropIfExists('domains');
    }
};

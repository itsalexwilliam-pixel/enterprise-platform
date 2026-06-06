<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plans Table
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['free', 'starter', 'professional', 'enterprise', 'custom'])->default('starter');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'one_time', 'pay_as_you_go'])->default('monthly');
            $table->decimal('price', 10, 2)->default(0);
            $table->bigInteger('credits_included')->default(0); // emails per cycle
            $table->decimal('price_per_credit', 10, 6)->default(0); // overage
            $table->integer('api_rate_limit')->default(60); // per minute
            $table->integer('bulk_limit')->default(100000); // max emails per bulk job
            $table->boolean('smtp_validation')->default(true);
            $table->boolean('ai_scoring')->default(false);
            $table->boolean('webhook_support')->default(false);
            $table->boolean('white_label')->default(false);
            $table->boolean('reseller_access')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->integer('max_team_members')->default(1);
            $table->integer('max_api_keys')->default(3);
            $table->string('stripe_price_id')->nullable()->index();
            $table->json('features')->nullable(); // extra feature flags
            $table->boolean('is_active')->default(true);
            $table->boolean('is_visible')->default(true); // show on pricing page
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'is_visible', 'sort_order']);
        });

        // Subscriptions Table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('plan_id')->index();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->string('stripe_status')->nullable();
            $table->enum('status', ['active', 'cancelled', 'expired', 'paused', 'trial'])->default('active');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('plan_id')->references('id')->on('plans');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};

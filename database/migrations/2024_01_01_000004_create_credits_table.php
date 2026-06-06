<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transactions Table (Credit Ledger)
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('reference', 64)->unique(); // unique transaction ID
            $table->enum('type', [
                'purchase',          // bought credits
                'subscription',      // subscription grant
                'deduction',         // email validation used
                'refund',            // refund on failed validation
                'bonus',             // admin bonus credits
                'referral',          // referral bonus
                'adjustment',        // manual admin adjustment
                'transfer',          // team credit transfer
                'payout',            // reseller payout
            ]);
            $table->bigInteger('amount'); // can be negative for deductions
            $table->bigInteger('balance_before');
            $table->bigInteger('balance_after');
            $table->decimal('price_paid', 10, 2)->nullable(); // actual money
            $table->string('currency', 3)->default('USD');
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_invoice_id')->nullable()->index();
            $table->unsignedBigInteger('validation_job_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type', 'created_at']);
            $table->index('created_at');
        });

        // Credit Packages (for purchase)
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('credits'); // number of email credits
            $table->decimal('price', 10, 2);
            $table->decimal('price_per_credit', 10, 6);
            $table->integer('bonus_credits')->default(0); // extra credits
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('stripe_price_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_packages');
        Schema::dropIfExists('transactions');
    }
};

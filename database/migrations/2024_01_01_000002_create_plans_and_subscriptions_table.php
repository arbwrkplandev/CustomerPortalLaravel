<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plans Table
 * Purpose: Subscription plan definitions (pricing tiers).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0);
            $table->decimal('quarterly_price', 10, 2)->default(0);
            $table->decimal('annual_price', 10, 2)->default(0);
            $table->json('features')->nullable()->comment('List of plan features');
            $table->integer('max_users')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('customer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('plan_id');
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annual']);
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_renewal_date')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 5)->default('USD');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->comment('Admin user ID');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_subscriptions');
        Schema::dropIfExists('plans');
    }
};

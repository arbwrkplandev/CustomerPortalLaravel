<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invoices & Payments Tables
 * Purpose: Billing records and payment tracking per tenant.
 * Future .NET SQL Server mapping: Direct mapping, decimal precision preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('invoice_number')->unique();
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 5)->default('USD');
            $table->json('line_items')->nullable()->comment('Detailed billing breakup');
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('subscription_id')->nullable();
            $table->string('payment_reference')->nullable()->unique();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 5)->default('USD');
            $table->enum('payment_mode', ['online', 'bank_transfer', 'cheque', 'cash', 'manual'])->default('manual');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->date('payment_date');
            $table->string('transaction_id')->nullable();
            $table->string('gateway')->nullable()->comment('Stripe, PayPal, etc.');
            $table->json('gateway_response')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable()->comment('Admin who recorded manual payment');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
    }
};

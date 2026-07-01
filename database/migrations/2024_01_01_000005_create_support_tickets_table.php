<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Support Tickets and Messages Tables
 * Purpose: Customer-to-admin support communication system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('created_by')->index()->comment('Customer user ID');
            $table->unsignedBigInteger('assigned_to')->nullable()->comment('Admin user ID');
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'waiting_response', 'resolved', 'closed'])->default('open');
            $table->enum('category', ['billing', 'technical', 'subscription', 'contract', 'general'])->default('general');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->index();
            $table->unsignedBigInteger('sender_id');
            $table->enum('sender_type', ['customer', 'admin'])->default('customer');
            $table->text('message');
            $table->json('attachments')->nullable()->comment('Array of file paths');
            $table->boolean('is_internal')->default(false)->comment('Admin-only note');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};

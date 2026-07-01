<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contracts, Sign Fields, and Contract Files Tables
 * Purpose: Full contract lifecycle management with e-sign support.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('contract_number')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['service', 'nda', 'sla', 'custom'])->default('service');
            $table->enum('status', ['draft', 'sent', 'pending_signature', 'signed', 'expired', 'cancelled'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('signed_at')->nullable();
            $table->string('original_pdf_path')->nullable()->comment('Admin-uploaded or system-generated PDF');
            $table->string('signed_pdf_path')->nullable()->comment('Final signed version');
            $table->string('signer_name')->nullable();
            $table->string('signer_email')->nullable();
            $table->string('signer_ip')->nullable();
            $table->text('html_content')->nullable()->comment('In-app contract editor content');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('contract_sign_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();
            $table->enum('field_type', ['signature', 'initials', 'date', 'text', 'checkbox'])->default('signature');
            $table->string('label');
            $table->integer('page_number')->default(1);
            $table->decimal('x_position', 8, 2)->default(0);
            $table->decimal('y_position', 8, 2)->default(0);
            $table->integer('width')->default(200);
            $table->integer('height')->default(60);
            $table->boolean('required')->default(true);
            $table->text('value')->nullable()->comment('Filled value after signing');
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
        });

        Schema::create('contract_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->enum('file_type', ['original', 'signed', 'amendment', 'attachment'])->default('original');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('file_size')->nullable()->comment('In bytes');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_files');
        Schema::dropIfExists('contract_sign_fields');
        Schema::dropIfExists('contracts');
    }
};

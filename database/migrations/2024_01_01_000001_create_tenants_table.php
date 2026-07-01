<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenants Table
 * Purpose: Core multi-tenant isolation. Each customer company = one tenant.
 * Future .NET SQL Server mapping: Direct mapping. tenant_id is the universal FK.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('slug')->unique()->comment('URL-safe unique identifier');
            $table->string('contact_name');
            $table->string('contact_email')->unique();
            $table->string('contact_phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('timezone', 60)->default('UTC');
            $table->string('logo')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'trial'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();
            $table->json('settings')->nullable()->comment('Tenant-specific preferences');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

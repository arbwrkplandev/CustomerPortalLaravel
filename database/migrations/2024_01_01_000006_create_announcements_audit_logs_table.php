<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Announcements and Audit Logs Tables
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['info', 'warning', 'success', 'maintenance', 'feature'])->default('info');
            $table->enum('visibility', ['all', 'specific_tenants', 'plan_based'])->default('all');
            $table->json('target_tenant_ids')->nullable()->comment('Null = all tenants');
            $table->json('target_plan_slugs')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('action', 100)->comment('create, update, delete, login, etc.');
            $table->string('module', 100)->comment('tenant, subscription, contract, etc.');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('Affected record ID');
            $table->string('entity_type', 100)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['module', 'action']);
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('auth_session_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('session_token', 512)->unique()->comment('External or internal session token');
            $table->string('provider', 50)->default('laravel')->comment('laravel|dotnet');
            $table->json('payload')->nullable()->comment('Standardized session payload');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_session_map');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('announcements');
    }
};

<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Admin\TenantController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\ContractController as AdminContractController;
use App\Http\Controllers\Api\V1\Admin\InvoiceController;
use App\Http\Controllers\Api\V1\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Api\V1\Admin\AnnouncementController;
use App\Http\Controllers\Api\V1\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Api\V1\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Api\V1\Customer\DashboardController;
use App\Http\Controllers\Api\V1\Customer\ContractController as CustomerContractController;
use App\Http\Controllers\Api\V1\Customer\SupportTicketController as CustomerSupportTicketController;
use App\Http\Controllers\Api\V1\Customer\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WrkPlan REST API Routes - Version 1
|--------------------------------------------------------------------------
| All endpoints are versioned at /api/v1/...
| Auth: session-based (swappable to .NET auth provider via config)
*/

Route::prefix('v1')->group(function () {

    // ─────────────────────────────────────────
    // Public Auth Routes
    // ─────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
        Route::get('/me', [AuthController::class, 'me'])->middleware('auth');
    });

    // ─────────────────────────────────────────
    // Admin API Routes
    // ─────────────────────────────────────────
    Route::prefix('admin')->middleware(['auth', 'admin.only'])->group(function () {

        // Admin Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Tenant / Customer Management
        Route::get('/tenants', [TenantController::class, 'index']);
        Route::post('/tenants', [TenantController::class, 'store']);
        Route::get('/tenants/{tenant}', [TenantController::class, 'show']);
        Route::put('/tenants/{tenant}', [TenantController::class, 'update']);
        Route::post('/tenants/{tenant}/toggle-status', [TenantController::class, 'toggleStatus']);
        Route::post('/tenants/{tenant}/assign-subscription', [TenantController::class, 'assignSubscription']);
        Route::patch('/tenants/{tenant}/subscription', [TenantController::class, 'updateSubscription']);
        Route::post('/tenants/{tenant}/users/{user}/reset-password', [TenantController::class, 'resetUserPassword']);

        // Plan Management
        Route::get('/plans', [AdminPlanController::class, 'index']);
        Route::post('/plans', [AdminPlanController::class, 'store']);
        Route::get('/plans/{plan}', [AdminPlanController::class, 'show']);
        Route::put('/plans/{plan}', [AdminPlanController::class, 'update']);
        Route::post('/plans/{plan}/toggle-status', [AdminPlanController::class, 'toggleStatus']);

        // Contracts
        Route::get('/contracts', [AdminContractController::class, 'index']);
        Route::post('/contracts', [AdminContractController::class, 'store']);
        Route::get('/contracts/{contract}', [AdminContractController::class, 'show']);
        Route::post('/contracts/{contract}/send', [AdminContractController::class, 'sendToCustomer']);
        Route::post('/contracts/{contract}/revoke', [AdminContractController::class, 'revokeFromCustomer']);
        Route::get('/contracts/{contract}/download/{type?}', [AdminContractController::class, 'download']);
        Route::get('/contracts/{contract}/stream/{type?}', [AdminContractController::class, 'stream']);

        // Invoices & Payments
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::post('/invoices', [InvoiceController::class, 'store']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::post('/invoices/{invoice}/payment', [InvoiceController::class, 'recordPayment']);
        Route::get('/invoices/{invoice}/download-pdf', [InvoiceController::class, 'downloadPdf']);

        // Support Tickets (Admin Inbox)
        Route::get('/tickets', [AdminSupportTicketController::class, 'index']);
        Route::get('/tickets/{supportTicket}', [AdminSupportTicketController::class, 'show']);
        Route::post('/tickets/{supportTicket}/reply', [AdminSupportTicketController::class, 'reply']);
        Route::patch('/tickets/{supportTicket}/status', [AdminSupportTicketController::class, 'updateStatus']);
        Route::patch('/tickets/{supportTicket}/assign', [AdminSupportTicketController::class, 'assign']);

        // Announcements
        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::post('/announcements', [AnnouncementController::class, 'store']);
        Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show']);
        Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update']);
        Route::post('/announcements/{announcement}/toggle-publish', [AnnouncementController::class, 'togglePublish']);
        Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);

        // Audit Logs
        Route::get('/audit', [AdminAuditController::class, 'index']);
    });

    // ─────────────────────────────────────────
    // Customer Portal API Routes
    // ─────────────────────────────────────────
    Route::prefix('customer')->middleware(['auth', 'tenant.scope'])->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Subscriptions & Invoices
        Route::get('/subscription', [SubscriptionController::class, 'currentSubscription']);
        Route::get('/plans', [SubscriptionController::class, 'plans']);
        Route::get('/invoices', [SubscriptionController::class, 'invoices']);
        Route::get('/invoices/{id}', [SubscriptionController::class, 'invoiceDetail']);
        Route::get('/invoices/{id}/download', [SubscriptionController::class, 'downloadInvoice']);
        Route::get('/renewal-preview', [SubscriptionController::class, 'renewalPreview']);

        // Contracts
        Route::get('/contracts', [CustomerContractController::class, 'index']);
        Route::get('/contracts/{id}', [CustomerContractController::class, 'show']);
        Route::post('/contracts/{id}/sign', [CustomerContractController::class, 'sign']);
        Route::post('/contracts/{id}/upload-signed', [CustomerContractController::class, 'uploadSigned']);
        Route::get('/contracts/{id}/download/{type?}', [CustomerContractController::class, 'download']);
        Route::get('/contracts/{id}/stream/{type?}', [CustomerContractController::class, 'stream']);

        // Support Tickets
        Route::get('/tickets', [CustomerSupportTicketController::class, 'index']);
        Route::post('/tickets', [CustomerSupportTicketController::class, 'store']);
        Route::get('/tickets/{id}', [CustomerSupportTicketController::class, 'show']);
        Route::post('/tickets/{id}/reply', [CustomerSupportTicketController::class, 'reply']);
    });

    // Health check
    Route::get('/health', fn() => response()->json(['status' => 'ok', 'version' => 'v1']))->name('api.health');
});

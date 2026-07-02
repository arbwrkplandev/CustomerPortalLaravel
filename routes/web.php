<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Admin\AdminDashboardController;
use App\Http\Controllers\Web\Admin\AdminTenantController;
use App\Http\Controllers\Web\Admin\AdminContractController;
use App\Http\Controllers\Web\Admin\AdminInvoiceController;
use App\Http\Controllers\Web\Admin\AdminTicketController;
use App\Http\Controllers\Web\Admin\AdminAnnouncementController;
use App\Http\Controllers\Web\Admin\AdminAuditController;
use App\Http\Controllers\Web\Admin\AdminPlanController;
use App\Http\Controllers\Web\Customer\CustomerDashboardController;
use App\Http\Controllers\Web\Customer\CustomerContractController;
use App\Http\Controllers\Web\Customer\CustomerInvoiceController;
use App\Http\Controllers\Web\Customer\CustomerTicketController;
use App\Http\Controllers\Web\Customer\CustomerSubscriptionController;
use Illuminate\Support\Facades\Route;

// ─── Root ──────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('customer.dashboard');
    }
    return redirect()->route('auth.login');
});

// ─── Auth ───────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('auth.login');
    Route::post('/login', [LoginController::class, 'login'])->name('auth.login.post');
});
Route::post('/logout', [LoginController::class, 'logout'])->name('auth.logout')->middleware('auth');

// ─── Admin Hub ──────────────────────────────────────────
Route::prefix('admin')->middleware(['auth', 'admin.only'])->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Tenants
    Route::get('/customers', [AdminTenantController::class, 'index'])->name('tenants.index');
    Route::get('/customers/create', [AdminTenantController::class, 'create'])->name('tenants.create');
    Route::post('/customers', [AdminTenantController::class, 'store'])->name('tenants.store');
    Route::get('/customers/{tenant}', [AdminTenantController::class, 'show'])->name('tenants.show');
    Route::get('/customers/{tenant}/edit', [AdminTenantController::class, 'edit'])->name('tenants.edit');
    Route::put('/customers/{tenant}', [AdminTenantController::class, 'update'])->name('tenants.update');
    Route::post('/customers/{tenant}/toggle-status', [AdminTenantController::class, 'toggleStatus'])->name('tenants.toggle-status');
    Route::post('/customers/{tenant}/subscription', [AdminTenantController::class, 'assignSubscription'])->name('tenants.assign-subscription');
    Route::patch('/customers/{tenant}/subscription', [AdminTenantController::class, 'updateSubscription'])->name('tenants.update-subscription');
    Route::post('/customers/{tenant}/users/{user}/reset-password', [AdminTenantController::class, 'resetUserPassword'])->name('tenants.reset-password');

    // Plans
    Route::get('/plans', [AdminPlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [AdminPlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [AdminPlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [AdminPlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [AdminPlanController::class, 'update'])->name('plans.update');
    Route::post('/plans/{plan}/toggle-status', [AdminPlanController::class, 'toggleStatus'])->name('plans.toggle-status');

    // Contracts
    Route::get('/contracts', [AdminContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/create', [AdminContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts', [AdminContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{contract}', [AdminContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/send', [AdminContractController::class, 'send'])->name('contracts.send');
    Route::post('/contracts/{contract}/revoke', [AdminContractController::class, 'revoke'])->name('contracts.revoke');
    Route::get('/contracts/{contract}/download/{type?}', [AdminContractController::class, 'download'])->name('contracts.download');
    Route::get('/contracts/{contract}/stream/{type?}', [AdminContractController::class, 'streamPdf'])->name('contracts.stream');

    // Invoices
    Route::get('/invoices', [AdminInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [AdminInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [AdminInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [AdminInvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/invoices/{invoice}/payment', [AdminInvoiceController::class, 'recordPayment'])->name('invoices.payment');
    Route::get('/invoices/{invoice}/pdf', [AdminInvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

    // Tickets
    Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
    Route::patch('/tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('tickets.status');
    Route::patch('/tickets/{ticket}/assign', [AdminTicketController::class, 'assign'])->name('tickets.assign');

    // Announcements
    Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/create', [AdminAnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/announcements/{announcement}/edit', [AdminAnnouncementController::class, 'edit'])->name('announcements.edit');
    Route::put('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('announcements.update');
    Route::post('/announcements/{announcement}/publish', [AdminAnnouncementController::class, 'togglePublish'])->name('announcements.publish');
    Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');

    // Audit Logs
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');
});

// ─── Customer Portal ────────────────────────────────────
Route::prefix('portal')->middleware(['auth', 'tenant.scope'])->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/subscription', [CustomerSubscriptionController::class, 'index'])->name('subscription');
    Route::get('/invoices', [CustomerInvoiceController::class, 'index'])->name('invoices');
    Route::get('/invoices/{invoice}', [CustomerInvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [CustomerInvoiceController::class, 'download'])->name('invoices.download');
    Route::get('/contracts', [CustomerContractController::class, 'index'])->name('contracts');
    Route::get('/contracts/{contract}', [CustomerContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{contract}/sign', [CustomerContractController::class, 'sign'])->name('contracts.sign');
    Route::post('/contracts/{contract}/upload-signed', [CustomerContractController::class, 'uploadSigned'])->name('contracts.upload-signed');
    Route::get('/contracts/{contract}/download/{type?}', [CustomerContractController::class, 'download'])->name('contracts.download');
    Route::get('/contracts/{contract}/stream/{type?}', [CustomerContractController::class, 'streamPdf'])->name('contracts.stream');
    Route::get('/support', [CustomerTicketController::class, 'index'])->name('tickets');
    Route::get('/support/create', [CustomerTicketController::class, 'create'])->name('tickets.create');
    Route::post('/support', [CustomerTicketController::class, 'store'])->name('tickets.store');
    Route::get('/support/{ticket}', [CustomerTicketController::class, 'show'])->name('tickets.show');
    Route::post('/support/{ticket}/reply', [CustomerTicketController::class, 'reply'])->name('tickets.reply');
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\MarketplaceController as SuperAdminMarketplaceController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;
use App\Http\Controllers\SuperAdmin\PlanController as SuperAdminPlanController;
use App\Http\Controllers\SuperAdmin\SettingsController as SuperAdminSettingsController;
use App\Http\Controllers\SuperAdmin\ReportController as SuperAdminReportController;
use App\Http\Controllers\SuperAdmin\SubscriptionController as SuperAdminSubscriptionController;
use App\Http\Controllers\SuperAdmin\TicketController as SuperAdminTicketController;
use App\Http\Controllers\SuperAdmin\ReferralController as SuperAdminReferralController;
use App\Http\Controllers\SuperAdmin\CustomerController as SuperAdminCustomerController;
use App\Http\Controllers\SuperAdmin\SubUserController as SuperAdminSubUserController;
use App\Http\Controllers\SuperAdmin\BannerController as SuperAdminBannerController;

Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('users', SuperAdminUserController::class)->only(['index', 'edit', 'update']);
        Route::resource('plans', SuperAdminPlanController::class)->only([
            'index',
            'create',
            'store',
            'edit',
            'update',
            'destroy',
        ]);
        Route::get('settings', [SuperAdminSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/referral', [SuperAdminSettingsController::class, 'updateReferralProgram'])
            ->name('settings.referral');
        Route::post('settings/report-exports', [SuperAdminSettingsController::class, 'updateReportExports'])
            ->name('settings.report-exports');
        Route::post('settings/vat-colors', [SuperAdminSettingsController::class, 'updateVatColors'])
            ->name('settings.vat-colors');
        Route::post('settings/quick-actions', [SuperAdminSettingsController::class, 'updateQuickActions'])
            ->name('settings.quick-actions');
        Route::post('settings/category-mapping', [SuperAdminSettingsController::class, 'updateCategoryMappingSettings'])
            ->name('settings.category-mapping');
        Route::post('settings/theme', [SuperAdminSettingsController::class, 'updateTheme'])
            ->name('settings.theme');
        Route::put('settings/client/{user}', [SuperAdminSettingsController::class, 'updateClientSettings'])
            ->name('settings.client.update');
        Route::get('reports', [SuperAdminReportController::class, 'index'])->name('reports.index');
        Route::get('subscriptions', [SuperAdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::get('subscriptions-export', [SuperAdminSubscriptionController::class, 'exportSubscriptions'])->name('subscriptions.export');
        Route::get('invoices', [SuperAdminSubscriptionController::class, 'invoices'])->name('invoices.index');
        Route::get('invoices/create', [SuperAdminSubscriptionController::class, 'createInvoice'])->name('invoices.create');
        Route::post('invoices', [SuperAdminSubscriptionController::class, 'storeInvoice'])->name('invoices.store');
        Route::get('invoices/subscribers', [SuperAdminSubscriptionController::class, 'searchInvoiceSubscribers'])->name('invoices.subscribers');
        Route::get('invoices-export', [SuperAdminSubscriptionController::class, 'exportInvoices'])->name('invoices.export');
        Route::get('invoices/{invoice}', [SuperAdminSubscriptionController::class, 'showInvoice'])->name('invoices.show');
        Route::resource('marketplaces', SuperAdminMarketplaceController::class);
        Route::resource('customers', SuperAdminCustomerController::class)->except(['destroy'])->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::delete('customers/{customer}', [SuperAdminCustomerController::class, 'destroy'])->name('customers.destroy');
        Route::get('referrals', [SuperAdminReferralController::class, 'index'])->name('referrals.index');
        Route::get('referrals/{referral}', [SuperAdminReferralController::class, 'show'])->name('referrals.show');
        Route::get('referrals-export', [SuperAdminReferralController::class, 'export'])->name('referrals.export');
        Route::get('sub-users', [SuperAdminSubUserController::class, 'index'])->name('sub-users.index');
        Route::resource('banners', SuperAdminBannerController::class)->except(['show']);

        Route::get('tickets', [SuperAdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/{ticket}', [SuperAdminTicketController::class, 'show'])->name('tickets.show');
        Route::post('tickets/{ticket}/reply', [SuperAdminTicketController::class, 'reply'])->name('tickets.reply');
        Route::post('tickets/{ticket}/assign', [SuperAdminTicketController::class, 'assign'])->name('tickets.assign');
        Route::post('tickets/{ticket}/status', [SuperAdminTicketController::class, 'changeStatus'])->name('tickets.status');
    });

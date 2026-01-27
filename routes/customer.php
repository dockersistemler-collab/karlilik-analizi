<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Admin\DashboardController as ClientDashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\MarketplaceProductController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SubUserController as AdminSubUserController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Customer\TicketController as CustomerTicketController;
use App\Http\Controllers\SubUser\PasswordController as SubUserPasswordController;

Route::middleware(['auth', 'verified', 'role:client'])->group(function () {
    Route::post('/subscribe/{plan}', [SubscriptionController::class, 'store'])->name('subscribe');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/renew', [SubscriptionController::class, 'renew'])->name('subscription.renew');
});

Route::middleware(['client_or_subuser', 'verified', 'subuser.permission'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('subscription', [SubscriptionController::class, 'index'])->name('subscription');
        Route::get('subscription/history', [SubscriptionController::class, 'history'])->name('subscription.history');
        Route::get('invoices', [SubscriptionController::class, 'invoices'])->name('invoices.index');
        Route::get('invoices/create', [SubscriptionController::class, 'createInvoice'])->name('invoices.create');
        Route::post('invoices', [SubscriptionController::class, 'storeInvoice'])->name('invoices.store');
        Route::get('invoices/customers', [SubscriptionController::class, 'searchInvoiceCustomers'])->name('invoices.customers');
        Route::get('invoices/{invoice}', [SubscriptionController::class, 'showInvoice'])->name('invoices.show');
        Route::get('invoices-export', [SubscriptionController::class, 'exportInvoices'])->name('invoices.export');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

Route::middleware(['client_or_subuser', 'verified', 'subscription', 'subuser.permission'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [ClientDashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', ProductController::class);
        Route::post('products/{product}/quick-update', [ProductController::class, 'quickUpdate'])
            ->name('products.quick-update');
        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::resource('brands', AdminBrandController::class)->except(['show']);
        Route::get('products-export', [ProductController::class, 'export'])->name('products.export');
        Route::get('products-template', [ProductController::class, 'exportTemplate'])->name('products.template');
        Route::post('products-import', [ProductController::class, 'import'])->name('products.import');
        Route::resource('orders', OrderController::class)->only(['index', 'show', 'update']);
        Route::post('orders/bulk-update', [OrderController::class, 'bulkUpdate'])->name('orders.bulk-update');
        Route::post('orders/bulk-ship', [OrderController::class, 'bulkShip'])->name('orders.bulk-ship');
        Route::get('orders-export', [OrderController::class, 'export'])->name('orders.export');
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [AdminReportController::class, 'index'])->name('index');
            Route::get('top-products', [AdminReportController::class, 'topProducts'])->name('top-products');
            Route::middleware('reports.export')->group(function () {
                Route::get('top-products-export', [AdminReportController::class, 'topProductsExport'])->name('top-products.export');
                Route::get('orders-revenue-export', [AdminReportController::class, 'ordersRevenueExport'])->name('orders-revenue.export');
                Route::get('orders-revenue-invoiced-export', [AdminReportController::class, 'ordersRevenueInvoicedExport'])->name('orders-revenue.invoiced-export');
            });
            Route::get('sold-products', [AdminReportController::class, 'soldProducts'])->name('sold-products');
            Route::get('sold-products-print', [AdminReportController::class, 'soldProductsPrint'])->name('sold-products.print');
            Route::get('category-sales', [AdminReportController::class, 'categorySales'])->name('category-sales');
            Route::get('brand-sales', [AdminReportController::class, 'brandSales'])->name('brand-sales');
            Route::get('vat', [AdminReportController::class, 'vat'])->name('vat');
            Route::get('commission', [AdminReportController::class, 'commission'])->name('commission');
            Route::get('stock-value', [AdminReportController::class, 'stockValue'])->name('stock-value');
        });
        Route::get('integrations', [IntegrationController::class, 'index'])->name('integrations.index');
        Route::get('integrations/{marketplace}', [IntegrationController::class, 'edit'])->name('integrations.edit');
        Route::put('integrations/{marketplace}', [IntegrationController::class, 'update'])->name('integrations.update');
        Route::post('integrations/{marketplace}/test', [IntegrationController::class, 'test'])->name('integrations.test');
        Route::view('addons', 'admin.addons')->name('addons.index');
        Route::resource('sub-users', AdminSubUserController::class)->except(['show']);

        Route::post('marketplace-products/assign', [MarketplaceProductController::class, 'assign'])
            ->name('marketplace-products.assign');
        Route::put('marketplace-products/{marketplaceProduct}', [MarketplaceProductController::class, 'update'])
            ->name('marketplace-products.update');
        Route::post('marketplace-products/bulk-update', [MarketplaceProductController::class, 'bulkUpdate'])
            ->name('marketplace-products.bulk-update');
        Route::post('marketplace-products/bulk-sync', [MarketplaceProductController::class, 'bulkSync'])
            ->name('marketplace-products.bulk-sync');
        Route::delete('marketplace-products/{marketplaceProduct}', [MarketplaceProductController::class, 'destroy'])
            ->name('marketplace-products.destroy');
        Route::post('marketplace-products/{marketplaceProduct}/sync', [MarketplaceProductController::class, 'sync'])
            ->name('marketplace-products.sync');

        Route::get('tickets', [CustomerTicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/create', [CustomerTicketController::class, 'create'])->name('tickets.create');
        Route::post('tickets', [CustomerTicketController::class, 'store'])->name('tickets.store');
        Route::get('tickets/{ticket}', [CustomerTicketController::class, 'show'])->name('tickets.show');
        Route::post('tickets/{ticket}/reply', [CustomerTicketController::class, 'reply'])->name('tickets.reply');

        Route::view('help/training', 'admin.help.training')->name('help.training');
        Route::view('help/support', 'admin.help.support')->name('help.support');
        Route::view('help/refer', 'admin.help.refer')->name('help.refer');
    });

Route::middleware(['client_or_subuser', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('subuser/password', [SubUserPasswordController::class, 'edit'])->name('subuser.password.edit');
        Route::put('subuser/password', [SubUserPasswordController::class, 'update'])->name('subuser.password.update');
    });

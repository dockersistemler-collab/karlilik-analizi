<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\Admin\DashboardController as ClientDashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\MarketplaceProductController;
use App\Http\Controllers\Admin\IntegrationController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ModuleUpsellController;
use App\Http\Controllers\Admin\ModuleCheckoutController;
use App\Http\Controllers\Admin\MyModulesController;
use App\Http\Controllers\Admin\EInvoiceController;
use App\Http\Controllers\Admin\EInvoiceSettingsController;
use App\Http\Controllers\Admin\ApiAccessController;
use App\Http\Controllers\Admin\WebhookEndpointController as AdminWebhookEndpointController;
use App\Http\Controllers\Admin\WebhookDeliveryController as AdminWebhookDeliveryController;
use App\Http\Controllers\Admin\CargoIntegrationController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\EInvoiceApiDocsController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ProfitabilityController as AdminProfitabilityController;
use App\Http\Controllers\Admin\ProfitabilityAccountController as AdminProfitabilityAccountController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SubUserController as AdminSubUserController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryMappingController as AdminCategoryMappingController;
use App\Http\Controllers\Admin\MarketplaceCategoryController as AdminMarketplaceCategoryController;
use App\Http\Controllers\Admin\Notifications\MailTemplateController as AdminMailTemplateController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\NotificationPreferenceController as AdminNotificationPreferenceController;
use App\Http\Controllers\Admin\NotificationSuppressionController as AdminNotificationSuppressionController;
use App\Http\Controllers\Admin\IntegrationHealthController as AdminIntegrationHealthController;
use App\Http\Controllers\Admin\IncidentController as AdminIncidentController;
use App\Http\Controllers\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\Admin\InventoryMappingController;
use App\Http\Controllers\Admin\InventoryMovementController;
use App\Http\Controllers\Admin\InventoryProductController;
use App\Http\Controllers\Admin\InventoryUserController;
use App\Http\Controllers\Admin\CommissionTariffController;
use App\Http\Controllers\Admin\CommissionTariffApiController;
use App\Http\Controllers\Admin\TrendyolOfferController;
use App\Http\Controllers\Admin\TrendyolOfferApiController;
use App\Http\Controllers\Admin\HepsiburadaOfferController;
use App\Http\Controllers\Admin\HepsiburadaOfferApiController;
use App\Http\Controllers\Customer\TicketController as CustomerTicketController;
use App\Http\Controllers\SubUser\PasswordController as SubUserPasswordController;
use App\Http\Controllers\Admin\System\MailLogController as AdminMailLogController;
use App\Http\Controllers\Customer\InvoiceController as CustomerInvoiceController;

Route::middleware(['auth', 'verified', 'role:client'])->group(function () {
    Route::post('/subscribe/{plan}', [SubscriptionController::class, 'store'])->name('subscribe');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/renew', [SubscriptionController::class, 'renew'])->name('subscription.renew');
});

Route::middleware(['client_or_subuser', 'verified', 'subuser.permission', 'support.readonly'])
    ->name('portal.')
    ->group(function () {
        Route::get('subscription', [SubscriptionController::class, 'index'])->name('subscription');
        Route::get('subscription/history', [SubscriptionController::class, 'history'])->name('subscription.history');
        Route::get('dashboard-metrics', [ClientDashboardController::class, 'metrics'])->name('dashboard.metrics');
        Route::get('dashboard-map', [ClientDashboardController::class, 'mapData'])->name('dashboard.map');
        Route::get('billing/plans', [AdminBillingController::class, 'plans'])->name('billing.plans');
        Route::post('billing/checkout', [AdminBillingController::class, 'checkout'])->name('billing.checkout');
        Route::get('billing/iyzico/{checkout}', [AdminBillingController::class, 'showIyzico'])->name('billing.iyzico.show');
        Route::get('billing/success', [AdminBillingController::class, 'success'])->name('billing.success');
        Route::get('billing/cancel', [AdminBillingController::class, 'cancel'])->name('billing.cancel');
        Route::post('billing/subscribe', [AdminBillingController::class, 'subscribe'])->name('billing.subscribe');
        Route::get('billing/subscription/{subscription}', [AdminBillingController::class, 'showSubscription'])->name('billing.subscription.show');
        Route::post('billing/subscription/upgrade', [AdminBillingController::class, 'upgradeSubscription'])->name('billing.subscription.upgrade');
        Route::post('billing/subscription/cancel', [AdminBillingController::class, 'cancelSubscription'])->name('billing.subscription.cancel');
        Route::get('my-modules', [MyModulesController::class, 'index'])->name('modules.mine');
        Route::post('my-modules/{module}/renew', [MyModulesController::class, 'renew'])->name('my-modules.renew');
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
        Route::get('customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');

        Route::get('billing', [\App\Http\Controllers\Customer\BillingController::class, 'index'])->name('billing');
        Route::get('billing/card-update', [\App\Http\Controllers\Customer\BillingController::class, 'cardUpdateForm'])
            ->name('billing.card-update');
        Route::post('billing/card-update', [\App\Http\Controllers\Customer\BillingController::class, 'cardUpdateInitialize'])
            ->name('billing.card-update.initialize');
        Route::get('billing/card-update/result', [\App\Http\Controllers\Customer\BillingController::class, 'cardUpdateResult'])
            ->name('billing.card-update.result');
        Route::view('support', 'customer.support')->name('support');
        Route::get('invoices', [CustomerInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/create', [SubscriptionController::class, 'createInvoice'])->name('invoices.create');
        Route::post('invoices', [SubscriptionController::class, 'storeInvoice'])->name('invoices.store');
        Route::get('invoices/customers', [SubscriptionController::class, 'searchInvoiceCustomers'])->name('invoices.customers');
        Route::get('invoices/{invoice}', [CustomerInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/download', [CustomerInvoiceController::class, 'download'])
            ->middleware(['signed', 'throttle:6,1'])
            ->name('invoices.download');
        Route::get('invoices-export', [SubscriptionController::class, 'exportInvoices'])
            ->middleware('module:feature.exports')
            ->name('invoices.export');

    });

Route::middleware(['client_or_subuser', 'verified', 'subscription', 'subuser.permission', 'support.readonly'])
    ->name('portal.')
    ->group(function () {
        Route::get('/', [ClientDashboardController::class, 'index'])->name('dashboard');
        Route::get('modules/upsell/{code}', [ModuleUpsellController::class, 'show'])->name('modules.upsell');
        Route::post('modules/{module}/buy', [ModuleCheckoutController::class, 'buy'])->name('modules.buy');

        Route::middleware('module:feature.einvoice')->group(function () {
            Route::get('einvoices', [EInvoiceController::class, 'index'])->name('einvoices.index');
            Route::get('einvoices/{einvoice}', [EInvoiceController::class, 'show'])->name('einvoices.show');
            Route::post('orders/{order}/einvoice', [EInvoiceController::class, 'createFromOrder'])->name('orders.einvoice');
            Route::post('einvoices/{einvoice}/issue', [EInvoiceController::class, 'issue'])->name('einvoices.issue');
            Route::post('einvoices/{einvoice}/return', [EInvoiceController::class, 'createReturn'])->name('einvoices.return');
            Route::post('einvoices/{einvoice}/credit-note', [EInvoiceController::class, 'createCreditNote'])->name('einvoices.credit-note');
            Route::post('einvoices/{einvoice}/cancel', [EInvoiceController::class, 'cancel'])->name('einvoices.cancel');
            Route::get('einvoices/{einvoice}/pdf', [EInvoiceController::class, 'pdf'])->name('einvoices.pdf');
        });

        Route::resource('products', ProductController::class);
        Route::prefix('admin/inventory')
            ->name('inventory.admin.')
            ->middleware('module:feature.inventory,404')
            ->group(function () {
                Route::get('products', [InventoryProductController::class, 'index'])->name('products.index');
                Route::get('products/{product}/edit', [InventoryProductController::class, 'edit'])->name('products.edit');
                Route::put('products/{product}', [InventoryProductController::class, 'update'])->name('products.update');
                Route::post('sync-marketplace', [InventoryProductController::class, 'syncMarketplace'])->name('sync-marketplace');
                Route::post('assign-marketplace', [InventoryProductController::class, 'assignMarketplace'])->name('assign-marketplace');
                Route::get('movements', [InventoryMovementController::class, 'index'])->name('movements.index');
                Route::get('mappings', [InventoryMappingController::class, 'index'])->name('mappings.index');
                Route::post('mappings', [InventoryMappingController::class, 'store'])->name('mappings.store');
                Route::put('mappings/{listing}', [InventoryMappingController::class, 'update'])->name('mappings.update');
                Route::delete('mappings/{listing}', [InventoryMappingController::class, 'destroy'])->name('mappings.destroy');
            });
        Route::prefix('user/inventory')
            ->name('inventory.user.')
            ->middleware('module:feature.inventory,404')
            ->group(function () {
                Route::get('products', [InventoryUserController::class, 'index'])->name('products.index');
            });
        Route::post('products/{product}/quick-update', [ProductController::class, 'quickUpdate'])
            ->name('products.quick-update');
        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::post('categories/import', [AdminCategoryController::class, 'importFromMarketplace'])->name('categories.import');
        Route::middleware('module:feature.category_mapping')->group(function () {
            Route::get('categories/{category}/mappings-status', [AdminCategoryMappingController::class, 'status'])
                ->name('categories.mappings.status');
            Route::post('categories/{category}/mappings/{marketplace}', [AdminCategoryMappingController::class, 'upsert'])
                ->name('categories.mappings.upsert');
            Route::delete('categories/{category}/mappings/{marketplace}', [AdminCategoryMappingController::class, 'destroy'])
                ->name('categories.mappings.destroy');
            Route::post('marketplace-categories/{marketplace}/sync', [AdminMarketplaceCategoryController::class, 'sync'])
                ->name('marketplace-categories.sync');
            Route::get('marketplace-categories/{marketplace}/search', [AdminMarketplaceCategoryController::class, 'search'])
                ->name('marketplace-categories.search');
        });
        Route::resource('brands', AdminBrandController::class)->except(['show']);
        Route::get('products-export', [ProductController::class, 'export'])
            ->middleware('module:feature.exports')
            ->name('products.export');
        Route::get('products-template', [ProductController::class, 'exportTemplate'])->name('products.template');
        Route::post('products-import', [ProductController::class, 'import'])->name('products.import');
        Route::resource('orders', OrderController::class)->only(['index', 'show', 'update']);
        Route::post('orders/bulk-update', [OrderController::class, 'bulkUpdate'])->name('orders.bulk-update');
        Route::post('orders/bulk-ship', [OrderController::class, 'bulkShip'])->name('orders.bulk-ship');
        Route::get('orders-export', [OrderController::class, 'export'])
            ->middleware('module:feature.exports')
            ->name('orders.export');
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('settings/api', [ApiAccessController::class, 'index'])->name('settings.api');
        Route::middleware('module:feature.einvoice_api')->group(function () {
            Route::post('settings/api/tokens', [ApiAccessController::class, 'store'])->name('settings.api.tokens.store');
            Route::delete('settings/api/tokens/{tokenId}', [ApiAccessController::class, 'destroy'])->name('settings.api.tokens.destroy');
            Route::get('settings/api/logs', [ApiAccessController::class, 'logs'])->name('settings.api.logs');
        });

        Route::get('settings/webhooks', [AdminWebhookEndpointController::class, 'index'])->name('webhooks.index');
        Route::middleware('module:feature.einvoice_webhooks')->group(function () {
            Route::get('settings/webhooks/create', [AdminWebhookEndpointController::class, 'create'])->name('webhooks.create');
            Route::post('settings/webhooks', [AdminWebhookEndpointController::class, 'store'])->name('webhooks.store');
            Route::get('settings/webhooks/{endpoint}/edit', [AdminWebhookEndpointController::class, 'edit'])->name('webhooks.edit');
            Route::put('settings/webhooks/{endpoint}', [AdminWebhookEndpointController::class, 'update'])->name('webhooks.update');
            Route::post('settings/webhooks/{endpoint}/secret', [AdminWebhookEndpointController::class, 'rotateSecret'])->name('webhooks.secret.rotate');
            Route::post('settings/webhooks/{endpoint}/enable', [AdminWebhookEndpointController::class, 'enable'])->name('webhooks.enable');
            Route::post('settings/webhooks/{endpoint}/test', [AdminWebhookEndpointController::class, 'test'])->name('webhooks.test');
            Route::get('settings/webhooks/{endpoint}/deliveries', [AdminWebhookDeliveryController::class, 'index'])->name('webhooks.deliveries');
            Route::get('settings/webhooks/deliveries/{delivery}', [AdminWebhookDeliveryController::class, 'show'])->name('webhooks.deliveries.show');
        });

        Route::get('settings/cargo', [CargoIntegrationController::class, 'index'])->name('settings.cargo.index');
        Route::middleware('module:feature.cargo_tracking')->group(function () {
            Route::put('settings/cargo/{providerKey}', [CargoIntegrationController::class, 'update'])->name('settings.cargo.update');
            Route::post('settings/cargo/{providerKey}/test', [CargoIntegrationController::class, 'test'])->name('settings.cargo.test');
            Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
            Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name('shipments.show');
            Route::post('shipments/{shipment}/poll', [ShipmentController::class, 'poll'])
                ->middleware('throttle:'.config('cargo.manual_poll_throttle', 5).',1')
                ->name('shipments.poll');
        });

        Route::middleware('module:feature.einvoice')->group(function () {
            Route::get('settings/einvoice', [EInvoiceSettingsController::class, 'edit'])->name('settings.einvoice.edit');
            Route::put('settings/einvoice', [EInvoiceSettingsController::class, 'update'])->name('settings.einvoice.update');
        });
        Route::prefix('reports')->name('reports.')->middleware('module:feature.reports')->group(function () {
            Route::get('/', [AdminReportController::class, 'index'])->name('index');
            Route::get('top-products', [AdminReportController::class, 'topProducts'])->name('top-products');
            Route::middleware('reports.export')->group(function () {
                Route::get('top-products-export', [AdminReportController::class, 'topProductsExport'])
                    ->middleware('module:feature.exports')
                    ->name('top-products.export');
                Route::get('orders-revenue-export', [AdminReportController::class, 'ordersRevenueExport'])
                    ->middleware('module:feature.exports')
                    ->name('orders-revenue.export');
                Route::get('orders-revenue-invoiced-export', [AdminReportController::class, 'ordersRevenueInvoicedExport'])
                    ->middleware('module:feature.exports')
                    ->name('orders-revenue.invoiced-export');
            });
            Route::get('sold-products', [AdminReportController::class, 'soldProducts'])->name('sold-products');
            Route::get('sold-products-print', [AdminReportController::class, 'soldProductsPrint'])->name('sold-products.print');
            Route::get('category-sales', [AdminReportController::class, 'categorySales'])->name('category-sales');
            Route::get('brand-sales', [AdminReportController::class, 'brandSales'])->name('brand-sales');
            Route::get('vat', [AdminReportController::class, 'vat'])->name('vat');
            Route::get('commission', [AdminReportController::class, 'commission'])->name('commission');
            Route::get('stock-value', [AdminReportController::class, 'stockValue'])->name('stock-value');
            Route::get('order-profitability', [AdminReportController::class, 'orderProfitability'])->name('order-profitability');
        });
        Route::prefix('profitability')
            ->name('profitability.')
            ->middleware('module:feature.reports.profitability')
            ->group(function () {
                Route::get('/', [AdminProfitabilityController::class, 'index'])->name('index');
                Route::get('accounts', [AdminProfitabilityAccountController::class, 'index'])->name('accounts.index');
                Route::post('accounts', [AdminProfitabilityAccountController::class, 'store'])->name('accounts.store');
                Route::get('accounts/{account}/edit', [AdminProfitabilityAccountController::class, 'edit'])->name('accounts.edit');
                Route::put('accounts/{account}', [AdminProfitabilityAccountController::class, 'update'])->name('accounts.update');
                Route::delete('accounts/{account}', [AdminProfitabilityAccountController::class, 'destroy'])->name('accounts.destroy');
                Route::post('accounts/{account}/test', [AdminProfitabilityAccountController::class, 'test'])->name('accounts.test');
            });

        Route::middleware('module:feature.reports.commission_tariffs')->group(function () {
            Route::get('commission-tariffs', [CommissionTariffController::class, 'index'])
                ->name('commission-tariffs.index');

            Route::prefix('api/commission-tariffs')
                ->name('commission-tariffs.api.')
                ->group(function () {
                    Route::post('upload', [CommissionTariffApiController::class, 'upload'])->name('upload');
                    Route::post('column-map', [CommissionTariffApiController::class, 'columnMap'])->name('column-map');
                    Route::get('list', [CommissionTariffApiController::class, 'list'])->name('list');
                    Route::get('errors/{uploadId}', [CommissionTariffApiController::class, 'errors'])->name('errors');
                    Route::post('assign', [CommissionTariffApiController::class, 'assign'])->name('assign');
                    Route::post('recalc', [CommissionTariffApiController::class, 'recalc'])->name('recalc');
                    Route::post('export', [CommissionTariffApiController::class, 'export'])->name('export');
                });
        });

        Route::prefix('campaigns')
            ->name('campaigns.')
            ->middleware('module:feature.reports')
            ->group(function () {
                Route::get('trendyol-offers', [TrendyolOfferController::class, 'index'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('trendyol-offers');
                Route::post('trendyol-offers/upload', [TrendyolOfferApiController::class, 'upload'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('trendyol-offers.api.upload');
                Route::post('trendyol-offers/column-map', [TrendyolOfferApiController::class, 'columnMap'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('trendyol-offers.api.column-map');
                Route::get('trendyol-offers/list', [TrendyolOfferApiController::class, 'list'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('trendyol-offers.api.list');
                Route::get('trendyol-offers/errors/{uploadId}', [TrendyolOfferApiController::class, 'errors'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('trendyol-offers.api.errors');
                Route::post('trendyol-offers/assign', [TrendyolOfferApiController::class, 'assign'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('trendyol-offers.api.assign');
                Route::post('trendyol-offers/recalc', [TrendyolOfferApiController::class, 'recalc'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('trendyol-offers.api.recalc');
                Route::post('trendyol-offers/export', [TrendyolOfferApiController::class, 'export'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('trendyol-offers.api.export');

                Route::get('hepsiburada-offers', [HepsiburadaOfferController::class, 'index'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('hepsiburada-offers');
                Route::post('hepsiburada-offers/upload', [HepsiburadaOfferApiController::class, 'upload'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('hepsiburada-offers.api.upload');
                Route::post('hepsiburada-offers/column-map', [HepsiburadaOfferApiController::class, 'columnMap'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('hepsiburada-offers.api.column-map');
                Route::get('hepsiburada-offers/list', [HepsiburadaOfferApiController::class, 'list'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('hepsiburada-offers.api.list');
                Route::get('hepsiburada-offers/errors/{uploadId}', [HepsiburadaOfferApiController::class, 'errors'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('hepsiburada-offers.api.errors');
                Route::post('hepsiburada-offers/assign', [HepsiburadaOfferApiController::class, 'assign'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('hepsiburada-offers.api.assign');
                Route::post('hepsiburada-offers/recalc', [HepsiburadaOfferApiController::class, 'recalc'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('hepsiburada-offers.api.recalc');
                Route::post('hepsiburada-offers/export', [HepsiburadaOfferApiController::class, 'export'])
                    ->middleware('module:feature.reports.commission_tariffs')
                    ->name('hepsiburada-offers.api.export');
            });

        Route::middleware('module:feature.integrations')->group(function () {
            Route::get('integrations', [IntegrationController::class, 'index'])->name('integrations.index');
            Route::get('integrations/health', [AdminIntegrationHealthController::class, 'index'])
                ->middleware('feature:health_dashboard')
                ->name('integrations.health');
            Route::get('integrations/{marketplace}', [IntegrationController::class, 'edit'])->middleware('module:integration.marketplace.{marketplace}')->name('integrations.edit');
            Route::put('integrations/{marketplace}', [IntegrationController::class, 'update'])->middleware('module:integration.marketplace.{marketplace}')->name('integrations.update');
            Route::post('integrations/{marketplace}/test', [IntegrationController::class, 'test'])->middleware('module:integration.marketplace.{marketplace}')->name('integrations.test');
        });
        Route::view('addons', 'admin.addons')->name('addons.index');
        Route::resource('sub-users', AdminSubUserController::class)->middleware('module:feature.sub_users')->except(['show']);

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

        Route::middleware('module:feature.tickets')->group(function () {
            Route::get('tickets', [CustomerTicketController::class, 'index'])->name('tickets.index');
            Route::get('tickets/create', [CustomerTicketController::class, 'create'])->name('tickets.create');
            Route::post('tickets', [CustomerTicketController::class, 'store'])->name('tickets.store');
            Route::get('tickets/{ticket}', [CustomerTicketController::class, 'show'])->name('tickets.show');
            Route::post('tickets/{ticket}/reply', [CustomerTicketController::class, 'reply'])->name('tickets.reply');
        });

        Route::view('help/training', 'admin.help.training')->name('help.training');
        Route::view('help/support', 'admin.help.support')->name('help.support');
        Route::view('help/refer', 'admin.help.refer')->name('help.refer');

        Route::get('system/mail-logs', [AdminMailLogController::class, 'index'])->name('system.mail-logs.index');
        Route::get('system/mail-logs/{mailLog}', [AdminMailLogController::class, 'show'])->name('system.mail-logs.show');

        Route::get('notifications/mail-templates', [AdminMailTemplateController::class, 'index'])
            ->name('notifications.mail-templates.index');
        Route::get('notifications/mail-templates/{template}', [AdminMailTemplateController::class, 'show'])
            ->name('notifications.mail-templates.show');
        Route::patch('notifications/mail-templates/{template}', [AdminMailTemplateController::class, 'toggle'])
            ->name('notifications.mail-templates.toggle');

        Route::prefix('notification-hub')->name('notification-hub.')->group(function () {
            Route::get('notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
            Route::post('notifications/{notification}/read', [AdminNotificationController::class, 'markRead'])->name('notifications.read');
            Route::post('notifications/read-all', [AdminNotificationController::class, 'readAll'])->name('notifications.read-all');
            Route::get('preferences', [AdminNotificationPreferenceController::class, 'index'])->name('preferences.index');
            Route::put('preferences', [AdminNotificationPreferenceController::class, 'update'])->name('preferences.update');
            Route::get('suppressions', [AdminNotificationSuppressionController::class, 'index'])->name('suppressions.index');
            Route::post('suppressions', [AdminNotificationSuppressionController::class, 'store'])->name('suppressions.store');
            Route::delete('suppressions/{suppression}', [AdminNotificationSuppressionController::class, 'destroy'])->name('suppressions.destroy');
        });

        Route::get('incidents', [AdminIncidentController::class, 'index'])
            ->middleware('feature:incidents')
            ->name('incidents.index');
        Route::get('incidents/inbox', [AdminIncidentController::class, 'inbox'])
            ->middleware('feature:incidents')
            ->name('incidents.inbox');
        Route::get('incidents/{incident}', [AdminIncidentController::class, 'show'])
            ->middleware('feature:incidents')
            ->name('incidents.show');
        Route::post('incidents/{incident}/assign', [AdminIncidentController::class, 'assign'])
            ->middleware('feature:incidents')
            ->name('incidents.assign');
        Route::post('incidents/{incident}/assign-to-me', [AdminIncidentController::class, 'assignToMe'])
            ->middleware('feature:incidents')
            ->name('incidents.assign_to_me');
        Route::post('incidents/{incident}/ack', [AdminIncidentController::class, 'acknowledge'])
            ->middleware('feature:incidents')
            ->name('incidents.ack');
        Route::post('incidents/{incident}/quick-ack', [AdminIncidentController::class, 'quickAck'])
            ->middleware('feature:incidents')
            ->name('incidents.quick_ack');
        Route::post('incidents/{incident}/resolve', [AdminIncidentController::class, 'resolve'])
            ->middleware('feature:incidents')
            ->name('incidents.resolve');

        Route::get('upgrade', [\App\Http\Controllers\Admin\UpgradeController::class, 'index'])
            ->name('upgrade');

    });

Route::middleware(['client_or_subuser', 'verified', 'support.readonly'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('docs/einvoice-api', [EInvoiceApiDocsController::class, 'show'])->name('docs.einvoice');
        Route::get('docs/einvoice-api/openapi', [EInvoiceApiDocsController::class, 'downloadOpenApi'])->name('docs.einvoice.openapi');
        Route::get('docs/einvoice-api/postman', [EInvoiceApiDocsController::class, 'downloadPostman'])->name('docs.einvoice.postman');
        Route::get('subuser/password', [SubUserPasswordController::class, 'edit'])->name('subuser.password.edit');
        Route::put('subuser/password', [SubUserPasswordController::class, 'update'])->name('subuser.password.update');
    });

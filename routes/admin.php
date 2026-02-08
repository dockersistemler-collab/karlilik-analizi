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
use App\Http\Controllers\SuperAdmin\ModuleController as SuperAdminModuleController;
use App\Http\Controllers\SuperAdmin\ModulePurchaseController as SuperAdminModulePurchaseController;
use App\Http\Controllers\SuperAdmin\CargoProviderController as SuperAdminCargoProviderController;
use App\Http\Controllers\SuperAdmin\MarketplaceCarrierMappingController as SuperAdminMarketplaceCarrierMappingController;
use App\Http\Controllers\SuperAdmin\CargoHealthController as SuperAdminCargoHealthController;
use App\Http\Controllers\SuperAdmin\SupportViewController as SuperAdminSupportViewController;
use App\Http\Controllers\SuperAdmin\SupportViewSessionController as SuperAdminSupportViewSessionController;
use App\Http\Controllers\SuperAdmin\MailLogController as SuperAdminMailLogController;
use App\Http\Controllers\SuperAdmin\SystemController as SuperAdminSystemController;
use App\Http\Controllers\SuperAdmin\Plans\PlanMailRulesController as SuperAdminPlanMailRulesController;
use App\Http\Controllers\Admin\Notifications\MailTemplateController as SuperAdminMailTemplateController;
use App\Http\Controllers\SuperAdmin\NotificationController as SuperAdminNotificationController;
use App\Http\Controllers\Admin\BillingEventController as AdminBillingEventController;

Route::middleware(['auth', 'verified', 'role:super_admin'])
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
        Route::get('plans/{plan}/mail-rules', [SuperAdminPlanMailRulesController::class, 'edit'])
            ->name('plans.mail-rules.edit');
        Route::put('plans/{plan}/mail-rules', [SuperAdminPlanMailRulesController::class, 'update'])
            ->name('plans.mail-rules.update');
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
        Route::post('settings/mail', [SuperAdminSettingsController::class, 'updateMailSettings'])
            ->name('settings.mail.update');
        Route::post('settings/mail/test', [SuperAdminSettingsController::class, 'sendTestMail'])
            ->name('settings.mail.test');
        Route::post('settings/incident-sla', [SuperAdminSettingsController::class, 'updateIncidentSlaSettings'])
            ->name('settings.incident-sla.update');
        Route::post('settings/health', [SuperAdminSettingsController::class, 'updateIntegrationHealthSettings'])
            ->name('settings.health.update');
        Route::post('settings/features', [SuperAdminSettingsController::class, 'updateFeatureMatrix'])
            ->name('settings.features.update');
        Route::post('settings/billing', [SuperAdminSettingsController::class, 'updateBillingPlansCatalog'])
            ->name('settings.billing.update');
        Route::post('system-settings/billing/iyzico/product-create', [SuperAdminSettingsController::class, 'createIyzicoProduct'])
            ->name('system-settings.billing.iyzico.product-create');
        Route::post('system-settings/billing/iyzico/pricing-plan-create', [SuperAdminSettingsController::class, 'createIyzicoPricingPlan'])
            ->name('system-settings.billing.iyzico.pricing-plan-create');
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

        Route::resource('modules', SuperAdminModuleController::class)->except(['show']);
        Route::post('modules/{module}/assign', [SuperAdminModuleController::class, 'assignToUser'])
            ->name('modules.assign');
        Route::post('user-modules/{userModule}/toggle', [SuperAdminModuleController::class, 'toggleUserModule'])
            ->name('user-modules.toggle');
        Route::delete('user-modules/{userModule}', [SuperAdminModuleController::class, 'removeUserModule'])
            ->name('user-modules.destroy');

        Route::resource('module-purchases', SuperAdminModulePurchaseController::class)->only(['index', 'show', 'create', 'store']);
        Route::post('module-purchases/{modulePurchase}/mark-paid', [SuperAdminModulePurchaseController::class, 'markPaid'])
            ->name('module-purchases.mark-paid');
        Route::post('module-purchases/{modulePurchase}/mark-cancelled', [SuperAdminModulePurchaseController::class, 'markCancelled'])
            ->name('module-purchases.mark-cancelled');
        Route::post('module-purchases/{modulePurchase}/mark-refunded', [SuperAdminModulePurchaseController::class, 'markRefunded'])
            ->name('module-purchases.mark-refunded');

        Route::prefix('cargo')->name('cargo.')->group(function () {
            Route::get('providers', [SuperAdminCargoProviderController::class, 'index'])->name('providers.index');
            Route::post('providers/{providerKey}/toggle', [SuperAdminCargoProviderController::class, 'toggle'])->name('providers.toggle');
            Route::get('mappings', [SuperAdminMarketplaceCarrierMappingController::class, 'index'])->name('mappings.index');
            Route::get('mappings/create', [SuperAdminMarketplaceCarrierMappingController::class, 'create'])->name('mappings.create');
            Route::post('mappings', [SuperAdminMarketplaceCarrierMappingController::class, 'store'])->name('mappings.store');
            Route::get('mappings/{mapping}/edit', [SuperAdminMarketplaceCarrierMappingController::class, 'edit'])->name('mappings.edit');
            Route::put('mappings/{mapping}', [SuperAdminMarketplaceCarrierMappingController::class, 'update'])->name('mappings.update');
            Route::delete('mappings/{mapping}', [SuperAdminMarketplaceCarrierMappingController::class, 'destroy'])->name('mappings.destroy');
            Route::get('health', [SuperAdminCargoHealthController::class, 'index'])->name('health.index');
        });

        Route::get('tickets', [SuperAdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('tickets/{ticket}', [SuperAdminTicketController::class, 'show'])->name('tickets.show');
        Route::post('tickets/{ticket}/reply', [SuperAdminTicketController::class, 'reply'])->name('tickets.reply');
        Route::post('tickets/{ticket}/assign', [SuperAdminTicketController::class, 'assign'])->name('tickets.assign');
        Route::post('tickets/{ticket}/status', [SuperAdminTicketController::class, 'changeStatus'])->name('tickets.status');

        Route::get('support-view-sessions', [SuperAdminSupportViewSessionController::class, 'index'])
            ->name('support-view-sessions.index');
        Route::post('support-view-sessions/{supportAccessLog}/end', [SuperAdminSupportViewSessionController::class, 'end'])
            ->name('support-view-sessions.end');
        Route::get('system', [SuperAdminSystemController::class, 'index'])
            ->name('system.index');
        Route::get('system/mail-logs', [SuperAdminMailLogController::class, 'index'])
            ->name('mail-logs.index');
        Route::get('system/mail-logs/export', [SuperAdminMailLogController::class, 'export'])
            ->name('mail-logs.export');
        Route::get('system/mail-logs/{mailLog}', [SuperAdminMailLogController::class, 'show'])
            ->name('mail-logs.show');
        Route::get('notifications/mail-templates', [SuperAdminMailTemplateController::class, 'index'])
            ->name('notifications.mail-templates.index');
        Route::get('notifications/mail-templates/{template}', [SuperAdminMailTemplateController::class, 'show'])
            ->name('notifications.mail-templates.show');
        Route::patch('notifications/mail-templates/{template}', [SuperAdminMailTemplateController::class, 'toggle'])
            ->name('notifications.mail-templates.toggle');
        Route::post('notifications/mail-templates/{template}/test', [SuperAdminMailTemplateController::class, 'testSend'])
            ->name('notifications.mail-templates.test');

        Route::prefix('notification-hub')->name('notification-hub.')->group(function () {
            Route::get('notifications', [SuperAdminNotificationController::class, 'index'])
                ->name('notifications.index');
        });

        Route::prefix('observability')->name('observability.')->group(function () {
            Route::get('billing-events', [AdminBillingEventController::class, 'index'])
                ->name('billing-events.index');
            Route::get('billing-events/{billingEvent}', [AdminBillingEventController::class, 'show'])
                ->name('billing-events.show');
            Route::post('billing-events/{billingEvent}/reprocess-webhook', [\App\Http\Controllers\Admin\BillingEventActionController::class, 'reprocessWebhook'])
                ->middleware('throttle:10,1')
                ->name('billing-events.reprocess-webhook');
            Route::post('billing-events/{billingEvent}/retry-job', [\App\Http\Controllers\Admin\BillingEventActionController::class, 'retryJob'])
                ->middleware('throttle:10,1')
                ->name('billing-events.retry-job');
        });
    });

Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->name('super-admin.')
    ->group(function () {
        Route::post('users/{user}/support-view/start', [SuperAdminSupportViewController::class, 'start'])
            ->middleware('throttle:10,1')
            ->name('support-view.start');
    });

Route::middleware(['auth', 'verified', 'role:super_admin,support_agent'])
    ->name('super-admin.')
    ->group(function () {
        Route::post('tickets/{ticket}/support-view/start', [SuperAdminSupportViewController::class, 'startTicket'])
            ->middleware('throttle:10,1')
            ->name('support-view.start-ticket');
        Route::post('support-view/stop', [SuperAdminSupportViewController::class, 'stop'])
            ->name('support-view.stop');
    });

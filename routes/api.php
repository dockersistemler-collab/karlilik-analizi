<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DisputesController;
use App\Http\Controllers\Api\V1\MarketplaceAccountsController;
use App\Http\Controllers\Api\V1\PayoutsController;
use App\Http\Controllers\Api\V1\RolesController;
use App\Http\Controllers\Api\V1\SettlementRulesController;
use App\Http\Controllers\Api\V1\SettlementsDashboardController;
use App\Http\Controllers\Api\V1\SettlementLossFinderController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\TenantFeaturesController;
use App\Http\Controllers\Api\V1\TenantsController;
use App\Http\Controllers\Api\V1\TenantUsersController;
use App\Http\Controllers\Api\V1\EInvoiceApiController;
use App\Http\Middleware\ApiAuditLogger;
use App\Http\Middleware\EnsureApiTokenValid;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
});

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([EnsureApiTokenValid::class, 'auth:sanctum', ApiAuditLogger::class, 'module:feature.einvoice_api', 'throttle:api-token'])
    ->group(function () {
        Route::get('einvoices', [EInvoiceApiController::class, 'index'])->name('einvoices.index');
        Route::get('einvoices/{einvoice}', [EInvoiceApiController::class, 'show'])->name('einvoices.show');
        Route::get('einvoices/{einvoice}/pdf', [EInvoiceApiController::class, 'pdf'])->name('einvoices.pdf');
        Route::post('einvoices/{einvoice}/provider-status', [EInvoiceApiController::class, 'providerStatus'])
            ->middleware('throttle:provider-status')
            ->name('einvoices.provider-status');
    });

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'tenant.resolve', 'throttle:api-token'])
    ->group(function () {
        Route::middleware('can:tenants.manage')->group(function () {
            Route::apiResource('tenants', TenantsController::class);
            Route::patch('tenants/{tenant}/features', [TenantFeaturesController::class, 'update'])
                ->middleware('permission:features.manage')
                ->name('tenants.features.update');
        });

        Route::middleware('tenant.scope')->group(function () {
            Route::apiResource('marketplace-accounts', MarketplaceAccountsController::class)
                ->middleware('permission:marketplace_accounts.manage');
            Route::post('marketplace-accounts/{id}/sync', [SyncController::class, 'sync'])
                ->middleware('permission:sync.run')
                ->name('marketplace-accounts.sync');
            Route::post('marketplace-accounts/{id}/amazon/ping', [MarketplaceAccountsController::class, 'amazonPing'])
                ->middleware(['permission:marketplace_accounts.manage', 'tenant.feature:amazon_connector'])
                ->name('marketplace-accounts.amazon.ping');

            Route::apiResource('settlement-rules', SettlementRulesController::class)
                ->middleware('permission:settlement_rules.manage');

            Route::middleware('tenant.feature:hakedis_module')->group(function () {
                Route::post('payouts/import', [SettlementLossFinderController::class, 'import'])
                    ->middleware('permission:payouts.reconcile')
                    ->name('payouts.import');
                Route::get('payouts', [PayoutsController::class, 'index'])
                    ->middleware('permission:payouts.view')
                    ->name('payouts.index');
                Route::get('payouts/{id}', [PayoutsController::class, 'show'])
                    ->middleware('permission:payouts.view')
                    ->name('payouts.show');
                Route::get('payouts/{id}/transactions', [PayoutsController::class, 'transactions'])
                    ->middleware('permission:payouts.view')
                    ->name('payouts.transactions');
                Route::post('payouts/{payout}/reconcile', [SettlementLossFinderController::class, 'reconcilePayout'])
                    ->middleware('permission:payouts.reconcile')
                    ->name('payouts.reconcile');
                Route::post('accounts/{account}/reconcile', [SettlementLossFinderController::class, 'reconcileAccount'])
                    ->middleware('permission:payouts.reconcile')
                    ->name('accounts.reconcile');
                Route::get('payouts/{payout}/summary', [SettlementLossFinderController::class, 'summary'])
                    ->middleware('permission:payouts.view')
                    ->name('payouts.summary');
                Route::get('payouts/{payout}/reconciliations', [SettlementLossFinderController::class, 'reconciliations'])
                    ->middleware('permission:payouts.view')
                    ->name('payouts.reconciliations');
                Route::get('reconciliations/{id}', [SettlementLossFinderController::class, 'reconciliationDetail'])
                    ->middleware('permission:payouts.view')
                    ->name('reconciliations.show');
                Route::post('payouts/{payout}/export', [SettlementLossFinderController::class, 'export'])
                    ->middleware('permission:exports.create')
                    ->name('payouts.export');
                Route::post('disputes/from-findings', [SettlementLossFinderController::class, 'disputesFromFindings'])
                    ->middleware('permission:disputes.manage')
                    ->name('disputes.from-findings');

                Route::get('disputes', [DisputesController::class, 'index'])
                    ->middleware('permission:disputes.view')
                    ->name('disputes.index');
                Route::get('disputes/{id}', [DisputesController::class, 'show'])
                    ->middleware('permission:disputes.view')
                    ->name('disputes.show');
                Route::patch('disputes/{id}', [DisputesController::class, 'update'])
                    ->middleware('permission:disputes.manage')
                    ->name('disputes.update');

                Route::get('dashboard/settlements', SettlementsDashboardController::class)
                    ->middleware('permission:dashboard.view')
                    ->name('dashboard.settlements');
            });

            Route::get('roles', [RolesController::class, 'index'])
                ->middleware('permission:roles.manage')
                ->name('roles.index');
            Route::apiResource('users', TenantUsersController::class)
                ->middleware('permission:users.manage');
        });
    });

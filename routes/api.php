<?php

use App\Http\Controllers\Api\V1\EInvoiceApiController;
use App\Http\Middleware\ApiAuditLogger;
use App\Http\Middleware\EnsureApiTokenValid;
use Illuminate\Support\Facades\Route;

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

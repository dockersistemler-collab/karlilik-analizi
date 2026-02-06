<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Webhooks\IyzicoPaymentWebhookController;
use App\Http\Controllers\Payments\IyzicoCheckoutCallbackController;
use App\Http\Controllers\Billing\IyzicoController as BillingIyzicoController;

Route::middleware('correlation')->group(function () {
    Route::get('/', [PublicController::class, 'home'])->name('public.home');
    Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');

    Route::get('/dashboard', function () {
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            return redirect()->route('super-admin.dashboard');
        }
        return redirect()->route('portal.dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::get('/admin', fn () => redirect('/portal', 302));
    Route::any('/admin/{any}', fn (string $any) => redirect('/portal/' . $any, 302))
        ->where('any', '.*');

    require __DIR__.'/customer.php';
    require __DIR__.'/admin.php';

    Route::post('/payments/iyzico/callback', IyzicoCheckoutCallbackController::class)->name('iyzico.callback');
    Route::post('/webhooks/iyzico/payment', IyzicoPaymentWebhookController::class);

    Route::match(['get', 'post'], '/billing/iyzico/callback', [BillingIyzicoController::class, 'callback'])
        ->name('billing.iyzico.callback');
    Route::match(['get', 'post'], '/billing/iyzico/subscription/callback', [BillingIyzicoController::class, 'subscriptionCallback'])
        ->name('billing.iyzico.subscription.callback');
    Route::post('/billing/iyzico/subscription/card-update/callback', [BillingIyzicoController::class, 'cardUpdateCallback'])
        ->name('billing.iyzico.subscription.card-update.callback');
    Route::post('/billing/iyzico/webhook', [BillingIyzicoController::class, 'webhook'])
        ->name('billing.iyzico.webhook');

    require __DIR__.'/auth.php';
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\NeKazanirimController;
use App\Http\Controllers\Webhooks\IyzicoPaymentWebhookController;
use App\Http\Controllers\Payments\IyzicoCheckoutCallbackController;
use App\Http\Controllers\Billing\IyzicoController as BillingIyzicoController;

$rootDomain = config('app.root_domain');
$appDomain = config('app.app_domain');
$saDomain = config('app.sa_domain');

$buildSubdomainUrl = function (?string $domain, ?string $path = null): string {
    $domain = $domain ?: config('app.root_domain');
    $scheme = request()->getScheme();
    $url = $scheme.'://'.$domain;
    if ($path) {
        $url .= '/'.ltrim($path, '/');
    }
    $query = request()->getQueryString();
    if ($query) {
        $url .= '?'.$query;
    }

    return $url;
};

if ($rootDomain) {
    Route::domain($rootDomain)
        ->middleware('correlation')
        ->group(function () use ($appDomain, $saDomain, $buildSubdomainUrl) {
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

            Route::get('/admin', fn () => redirect()->away($buildSubdomainUrl($appDomain), 302));
            Route::any('/admin/{any}', fn (string $any) => redirect()->away($buildSubdomainUrl($appDomain, $any), 302))
                ->where('any', '.*');
            Route::get('/portal', fn () => redirect()->away($buildSubdomainUrl($appDomain), 302));
            Route::any('/portal/{any}', fn (string $any) => redirect()->away($buildSubdomainUrl($appDomain, $any), 302))
                ->where('any', '.*');
            Route::get('/super-admin', fn () => redirect()->away($buildSubdomainUrl($saDomain), 302));
            Route::any('/super-admin/{any}', fn (string $any) => redirect()->away($buildSubdomainUrl($saDomain, $any), 302))
                ->where('any', '.*');

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
}

if ($appDomain) {
    Route::domain($appDomain)
        ->middleware('correlation')
        ->group(function () {
            require __DIR__.'/customer.php';

            Route::middleware(['client_or_subuser', 'verified', 'subscription', 'subuser.permission', 'support.readonly'])
                ->get('/ne-kazanirim', [NeKazanirimController::class, 'index'])
                ->name('ne-kazanirim.index');
        });
}

if ($saDomain) {
    Route::domain($saDomain)
        ->middleware('correlation')
        ->group(function () {
            require __DIR__.'/admin.php';
        });
}

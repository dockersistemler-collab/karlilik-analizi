<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Webhooks\IyzicoPaymentWebhookController;

Route::get('/', [PublicController::class, 'home'])->name('public.home');
Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');

Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user && $user->isSuperAdmin()) {
        return redirect()->route('super-admin.dashboard');
    }
    return redirect()->route('admin.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/customer.php';
require __DIR__.'/admin.php';

Route::post('/webhooks/iyzico/payment', IyzicoPaymentWebhookController::class);

require __DIR__.'/auth.php';

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;
use App\Integrations\Marketplaces\Support\DateRangeFactory;
use App\Services\Marketplaces\MarketplaceSyncDispatcher;
use App\Services\Profitability\MartBuilder;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:maintain')->hourly();
Schedule::command('modules:send-renewal-reminders')
    ->dailyAt('09:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping();
Schedule::command('marketplace:check-token-expirations')
    ->dailyAt('09:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping();

Schedule::command('cargo:poll-tracking')
    ->everyFifteenMinutes()
    ->withoutOverlapping();
Schedule::command('integrations:health-notify')
    ->everyTenMinutes()
    ->withoutOverlapping();
Schedule::command('billing:dunning-run')
    ->hourly()
    ->withoutOverlapping();

Schedule::call(function () {
    $rangeFactory = app(DateRangeFactory::class);
    $dispatcher = app(MarketplaceSyncDispatcher::class);
    $range = $rangeFactory->fromString((string) config('marketplace_profitability.sync.orders_returns_range', 'last1day'));
    $dispatcher->dispatchOrdersAndReturns(null, null, $range);
})
    ->name('marketplace_profitability_orders_returns')
    ->hourly()
    ->withoutOverlapping();

Schedule::call(function () {
    $rangeFactory = app(DateRangeFactory::class);
    $dispatcher = app(MarketplaceSyncDispatcher::class);
    $range = $rangeFactory->fromString((string) config('marketplace_profitability.sync.fees_range', 'last30days'));
    $dispatcher->dispatchFees(null, null, $range);
})
    ->name('marketplace_profitability_fees')
    ->dailyAt('02:00')
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping();

Schedule::call(function () {
    $builder = app(MartBuilder::class);

    User::query()
        ->where('role', 'client')
        ->where('is_active', true)
        ->select('id')
        ->orderBy('id')
        ->chunk(100, function ($users) use ($builder) {
            foreach ($users as $user) {
                $builder->buildForTenant((int) $user->id);
            }
        });
})
    ->name('marketplace_profitability_mart_daily')
    ->dailyAt((string) config('marketplace_profitability.mart.daily_time', '03:00'))
    ->timezone('Europe/Istanbul')
    ->withoutOverlapping();

Artisan::command('user:promote {email}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (!$user) {
        $this->error('User not found.');
        return;
    }

    $user->update(['role' => 'super_admin']);
    $this->info("User promoted to super_admin: {$user->email}");
})->purpose('Promote a user to super_admin');

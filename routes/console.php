<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;

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

Artisan::command('user:promote {email}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (!$user) {
        $this->error('User not found.');
        return;
    }

    $user->update(['role' => 'super_admin']);
    $this->info("User promoted to super_admin: {$user->email}");
})->purpose('Promote a user to super_admin');

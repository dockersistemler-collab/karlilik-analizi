<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:maintain')->hourly();

Artisan::command('user:promote {email}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (!$user) {
        $this->error('User not found.');
        return;
    }

    $user->update(['role' => 'super_admin']);
    $this->info("User promoted to super_admin: {$user->email}");
})->purpose('Promote a user to super_admin');

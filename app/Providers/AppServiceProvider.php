<?php

namespace App\Providers;

use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Policies\TicketPolicy;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production') && config('queue.default') === 'sync') {
            throw new \RuntimeException('QUEUE_CONNECTION=sync production ortamında kullanılamaz. Redis/Database queue + worker zorunlu.');
        }

        Gate::policy(Ticket::class, TicketPolicy::class);

        Gate::before(function (User $user): ?bool {
            return $user->isSuperAdmin() ? true : null;
        });
    }
}

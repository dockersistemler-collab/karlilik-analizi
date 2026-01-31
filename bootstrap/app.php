<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'subscription' => \App\Http\Middleware\EnsureActiveSubscription::class,
            'client_or_subuser' => \App\Http\Middleware\EnsureClientOrSubUser::class,
            'subuser.permission' => \App\Http\Middleware\EnsureSubUserPermission::class,
            'reports.export' => \App\Http\Middleware\EnsureReportExportsEnabled::class,
            'plan.module' => \App\Http\Middleware\EnsurePlanModule::class,
            'plan.marketplace' => \App\Http\Middleware\EnsurePlanMarketplace::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

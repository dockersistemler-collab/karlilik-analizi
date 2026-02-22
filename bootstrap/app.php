<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'subscription' => \App\Http\Middleware\EnsureActiveSubscription::class,
            'client_or_subuser' => \App\Http\Middleware\EnsureClientOrSubUser::class,
            'subuser.permission' => \App\Http\Middleware\EnsureSubUserPermission::class,
            'reports.export' => \App\Http\Middleware\EnsureReportExportsEnabled::class,
            'module' => \App\Http\Middleware\EnsureModuleEnabled::class,
            'support.readonly' => \App\Http\Middleware\EnsureSupportViewReadOnly::class,
            'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
            'correlation' => \App\Http\Middleware\CorrelationIdMiddleware::class,
            'tenant.resolve' => \App\Http\Middleware\ResolveTenantContext::class,
            'tenant.scope' => \App\Http\Middleware\EnsureTenantScope::class,
            'tenant.feature' => \App\Http\Middleware\EnsureFeatureFlagEnabled::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        $middleware->prependToGroup('web', \App\Http\Middleware\SetSessionCookieForSubdomain::class);

        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            \App\Http\Middleware\EnsureApiTokenValid::class
        );

        $middleware->validateCsrfTokens(except: [
            'payments/iyzico/callback',
            'webhooks/iyzico/payment',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

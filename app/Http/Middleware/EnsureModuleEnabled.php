<?php

namespace App\Http\Middleware;

use App\Services\Entitlements\EntitlementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $code): Response
    {
        $user = $request->user();

        if (!$user && auth()->guard('subuser')->check()) {
            $subUser = auth()->guard('subuser')->user();
            $user = $subUser?->owner;
        }

        if (!$user) {
            abort(401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->getActivePlan()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bu işlemi yapmak için aktif bir paketiniz olmalı.',
                    'error' => 'PLAN_REQUIRED',
                ], 403);
            }

            $target = Route::has('admin.billing.plans')
                ? route('admin.billing.plans')
                : route('pricing');

            return redirect()
                ->to($target)
                ->with('warning', 'Bu işlemi yapmak için aktif bir paketiniz olmalı.');
        }

        $allowed = app(EntitlementService::class)->hasModule($user, $code);
        if ($allowed) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bu modül hesabınız için aktif değil.',
                'error' => 'MODULE_NOT_ENABLED',
                'module' => $code,
            ], 403);
        }

        return redirect()
            ->route('admin.modules.upsell', ['code' => $code])
            ->with('info', 'Bu modül hesabınız için aktif değil.');
    }
}

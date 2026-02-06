<?php

namespace App\Http\Middleware;

use App\Services\Entitlements\EntitlementService;
use App\Support\SupportUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $code): Response
    {
        $user = SupportUser::currentUser();
        $supportView = SupportUser::isEnabled();

        if (!$user) {
            abort(401);
        }
$code = $this->resolveDynamicCode($request, $code);

        if ($user->isSuperAdmin() && !$supportView) {
            return $next($request);
        }

        if (!$user->getActivePlan()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Bu iÅŸlemi yapmak için aktif bir paketiniz olmalı.',
                    'error' => 'PLAN_REQUIRED',
                ], 403);
            }
$target = Route::has('portal.billing.plans')
                ? route('portal.billing.plans')
                : route('pricing');

            return redirect()
                ->to($target)
                ->with('warning', 'Bu iÅŸlemi yapmak için aktif bir paketiniz olmalı.');
        }
$allowed = app(EntitlementService::class)->hasModule($user, $code);
        if ($allowed) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Bu modül hesabınız için aktif deÄŸil.',
                'error' => 'MODULE_NOT_ENABLED',
                'module' => $code,
            ], 403);
        }

        return redirect()
            ->route('portal.modules.upsell', ['code' => $code])
            ->with('info', 'Bu modül hesabınız için aktif deÄŸil.');
    }

    private function resolveDynamicCode(Request $request, string $code): string
    {
        if (!str_contains($code, '{')) {
            return $code;
        }

        if (!preg_match_all('/\{([^}]+)\}/', $code, $matches)) {
            return $code;
        }
$allowed = [
            'marketplace',
            'provider',
        ];

        foreach ($matches[1] as $key) {
            if (!in_array($key, $allowed, true)) {
                abort(400, 'Invalid module placeholder.');
            }
        }
$resolved = $code;
        foreach ($matches[1] as $key) {
            $raw = $request->route($key);
            $value = data_get($raw, 'code');
            if ($value === null) {
                $value = $raw;
            }
            if (!is_scalar($value)) {
                abort(400, 'Invalid module placeholder value.');
            }
$value = (string) $value;
            if (!preg_match('/^[a-z0-9_-]+$/i', $value)) {
                abort(400, 'Invalid module placeholder value.');
            }
$resolved = str_replace('{' . $key . '}', $value, $resolved);
        }

        return trim($resolved);
    }
}







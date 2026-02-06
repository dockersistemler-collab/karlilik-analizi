<?php

namespace App\Http\Middleware;

use App\Support\SupportUser;
use App\Services\Admin\SupportViewEventLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupportViewReadOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!SupportUser::isEnabled()) {
            return $next($request);
        }

        if ($request->routeIs('super-admin.support-view.stop')) {
            return $next($request);
        }

        if ($request->method() === 'OPTIONS') {
            return $next($request);
        }
$allowedRoutes = (array) config('support.allowed_routes', []);
        $routeName = $request->route()?->getName();

        if (in_array($request->method(), ['GET', 'HEAD'], true)) {
            if (!$routeName || !in_array($routeName, $allowedRoutes, true)) {
                app(SupportViewEventLogger::class)->logBlocked($request, 'BLOCKED_GET');
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Bu sayfaya Support View modunda erisilemez.',
                        'error' => 'SUPPORT_VIEW_READ_ONLY',
                    ], 403);
                }
$previousUrl = url()->previous();
                $fallbackUrl = route('portal.dashboard');
                $redirectUrl = ($previousUrl && $previousUrl !== $request->fullUrl())
                    ? $previousUrl
                    : $fallbackUrl;

                return redirect()
                    ->to($redirectUrl)
                    ->with('error', 'Bu sayfaya Support View modunda erisilemez.');
            }

            return $next($request);
        }

        if ($request->expectsJson()) {
            app(SupportViewEventLogger::class)->logBlocked($request, 'BLOCKED_WRITE');
            return response()->json([
                'message' => 'Support View modunda islem yapilamaz.',
                'error' => 'SUPPORT_VIEW_READ_ONLY',
            ], 403);
        }

        app(SupportViewEventLogger::class)->logBlocked($request, 'BLOCKED_WRITE');
        $previousUrl = url()->previous();
        $fallbackUrl = route('portal.dashboard');
        $redirectUrl = ($previousUrl && $previousUrl !== $request->fullUrl())
            ? $previousUrl
            : $fallbackUrl;

        return redirect()
            ->to($redirectUrl)
            ->with('error', 'Support View modunda islem yapilamaz.');

    }
}



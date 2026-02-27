<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientOrSubUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('subuser')->check()) {
            $subUser = Auth::guard('subuser')->user();
            if (!$subUser || !$subUser->is_active) {
                abort(403);
            }
            $owner = $subUser->owner;
            if (!$owner || !$owner->isClient()) {
                abort(403);
            }

            Auth::guard('web')->setUser($owner);
            $request->attributes->set('sub_user', $subUser);

            return $next($request);
        }

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($user && session('support_view_enabled')) {
                if ($request->routeIs('super-admin.support-view.stop')) {
                    return $next($request);
                }

                if ($user->role === 'support_agent') {
                    $allowedRoutes = (array) config('support.allowed_routes', []);
                    $routeName = $request->route()?->getName();
                    if ($routeName && in_array($routeName, $allowedRoutes, true)) {
                        return $next($request);
                    }
                    abort(403);
                }

                if ($user->role === 'super_admin') {
                    return $next($request);
                }
            }
            if ($user && $user->isClient()) {
                return $next($request);
            }
        }

        return redirect()->route('login');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user && auth('subuser')->check()) {
            $subUser = auth('subuser')->user();
            $user = $subUser?->owner;
        }

        if (!$user) {
            abort(401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if (!$user->hasActiveSubscription()) {
            return redirect()
                ->route('pricing')
                ->with('info', 'Devam etmek için aktif bir abonelik gerekiyor.');
        }

        return $next($request);
    }
}

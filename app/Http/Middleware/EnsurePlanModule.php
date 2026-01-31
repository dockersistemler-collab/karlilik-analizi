<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanModule
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $moduleKey): Response
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

        $plan = $user->getActivePlan();
        if (!$plan) {
            return redirect()
                ->route('pricing')
                ->with('info', 'Devam etmek için aktif bir abonelik gerekiyor.');
        }

        if ($plan->hasModule($moduleKey)) {
            return $next($request);
        }

        return redirect()
            ->route('admin.addons.index')
            ->with('info', 'Bu işlem paketinizde aktif değil.');
    }
}


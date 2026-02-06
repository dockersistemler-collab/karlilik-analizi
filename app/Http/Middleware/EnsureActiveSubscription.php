<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\SupportUser;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = SupportUser::currentUser();
        $supportView = SupportUser::isEnabled();

        if (!$user) {
            abort(401);
        }

        if ($user->isSuperAdmin() && !$supportView) {
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

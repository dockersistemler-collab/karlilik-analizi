<?php

namespace App\Http\Middleware;

use App\Services\Entitlements\EntitlementService;
use App\Services\Modules\ModuleGate;
use App\Support\SupportUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleEnabled
{
    public function handle(Request $request, Closure $next, string $moduleCode = 'customer_communication_center'): Response
    {
        $user = SupportUser::currentUser();
        if (!$user) {
            abort(401);
        }

        if ($user->isSuperAdmin() && !SupportUser::isEnabled()) {
            return $next($request);
        }

        $moduleGate = app(ModuleGate::class);
        if (!$moduleGate->isActive($moduleCode)) {
            abort(404);
        }

        if (!$user->getActivePlan()) {
            abort(403);
        }

        if (!app(EntitlementService::class)->hasModule($user, $moduleCode)) {
            abort(404);
        }

        return $next($request);
    }
}


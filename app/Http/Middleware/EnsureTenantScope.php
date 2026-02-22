<?php

namespace App\Http\Middleware;

use App\Domains\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $tenantId = app(TenantContext::class)->tenantId();

        if ($user->isSuperAdmin()) {
            if (!$tenantId) {
                abort(400, 'SuperAdmin requests to tenant resources require X-Tenant-Id header.');
            }

            return $next($request);
        }

        $userTenantId = $user->tenant_id ?: ($user->isClient() ? $user->id : null);
        if (!$userTenantId || (int) $tenantId !== (int) $userTenantId) {
            abort(403);
        }

        return $next($request);
    }
}


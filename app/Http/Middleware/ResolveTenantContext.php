<?php

namespace App\Http\Middleware;

use App\Domains\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = null;
        $user = $request->user();

        if ($user) {
            if ($user->isSuperAdmin()) {
                $tenantId = $request->integer('tenant_id');
                if (!$tenantId && $request->headers->has('X-Tenant-Id')) {
                    $tenantId = (int) $request->header('X-Tenant-Id');
                }
            } else {
                $tenantId = $user->tenant_id ?: ($user->isClient() ? $user->id : null);
            }
        }

        app(TenantContext::class)->setTenantId($tenantId ?: null);

        return $next($request);
    }
}

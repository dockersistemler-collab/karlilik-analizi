<?php

namespace App\Http\Middleware;

use App\Domains\Settlements\Models\FeatureFlag;
use App\Domains\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureFlagEnabled
{
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $featureKey = Str::lower(Str::ascii($featureKey));
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $tenantId = app(TenantContext::class)->tenantId();
        if (!$tenantId) {
            abort(400, 'Tenant context is missing.');
        }
        if (!Schema::hasTable('feature_flags')) {
            abort(403, 'Feature flags are not initialized.');
        }

        $enabled = FeatureFlag::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('tenant_id', $tenantId)
            ->where('key', $featureKey)
            ->value('enabled');

        if (!$enabled) {
            abort(403, "Feature flag not enabled: {$featureKey}");
        }

        return $next($request);
    }
}

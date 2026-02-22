<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Settlements\Models\FeatureFlag;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class TenantFeaturesController extends Controller
{
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'features' => ['required', 'array', 'min:1'],
            'features.*.key' => ['required', 'string', 'max:120'],
            'features.*.enabled' => ['required', 'boolean'],
        ]);

        foreach ($validated['features'] as $row) {
            FeatureFlag::query()
                ->withoutGlobalScope('tenant_scope')
                ->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'key' => $row['key']],
                    ['enabled' => (bool) $row['enabled']]
                );
        }

        return ApiResponse::success(
            FeatureFlag::query()
                ->withoutGlobalScope('tenant_scope')
                ->where('tenant_id', $tenant->id)
                ->get()
        );
    }
}


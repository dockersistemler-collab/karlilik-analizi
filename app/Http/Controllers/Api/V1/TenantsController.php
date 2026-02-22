<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class TenantsController extends Controller
{
    public function index()
    {
        return ApiResponse::success(Tenant::query()->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'status' => ['required', 'in:active,inactive'],
            'plan' => ['required', 'string', 'max:50'],
        ]);

        $tenant = Tenant::query()->create($validated);

        return ApiResponse::success($tenant, status: 201);
    }

    public function show(Tenant $tenant)
    {
        return ApiResponse::success($tenant);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'status' => ['sometimes', 'in:active,inactive'],
            'plan' => ['sometimes', 'string', 'max:50'],
        ]);
        $tenant->update($validated);

        return ApiResponse::success($tenant->fresh());
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}


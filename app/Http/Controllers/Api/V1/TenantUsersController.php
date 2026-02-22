<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesTenant;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TenantUsersController extends Controller
{
    use ResolvesTenant;

    public function index()
    {
        $tenantId = $this->currentTenantId();
        $rows = User::query()->where('tenant_id', $tenantId)->latest()->paginate(20);

        return ApiResponse::success($rows);
    }

    public function store(Request $request)
    {
        $tenantId = $this->currentTenantId();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:TenantAdmin,Finance,Viewer'],
        ]);

        $internalRole = $this->toInternalRole($validated['role']);
        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $internalRole,
            'is_active' => true,
        ]);
        $user->syncRoles([Role::findOrCreate($validated['role'], 'sanctum')]);

        return ApiResponse::success($user, status: 201);
    }

    public function show(int $id)
    {
        $tenantId = $this->currentTenantId();
        $user = User::query()->where('tenant_id', $tenantId)->findOrFail($id);

        return ApiResponse::success($user);
    }

    public function update(Request $request, int $id)
    {
        $tenantId = $this->currentTenantId();
        $user = User::query()->where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'role' => ['sometimes', 'in:TenantAdmin,Finance,Viewer'],
            'is_active' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        if (!empty($validated['role'])) {
            $user->syncRoles([Role::findOrCreate($validated['role'], 'sanctum')]);
            $validated['role'] = $this->toInternalRole($validated['role']);
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return ApiResponse::success($user->fresh());
    }

    public function destroy(int $id)
    {
        $tenantId = $this->currentTenantId();
        $user = User::query()->where('tenant_id', $tenantId)->findOrFail($id);
        $user->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    private function toInternalRole(string $spatieRole): string
    {
        return match ($spatieRole) {
            'TenantAdmin' => 'tenant_admin',
            'Finance' => 'finance',
            'Viewer' => 'viewer',
            default => 'viewer',
        };
    }
}

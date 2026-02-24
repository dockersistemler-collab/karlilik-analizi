<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Domains\Settlements\Models\FeatureFlag;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class SettlementModuleController extends Controller
{
    public function index(): View
    {
        $clients = User::query()
            ->where('role', 'client')
            ->orderBy('name')
            ->paginate(25);

        $tenantIds = $clients->getCollection()
            ->map(fn (User $user): int => (int) ($user->tenant_id ?: $user->id))
            ->filter(fn (int $tenantId): bool => $tenantId > 0)
            ->values()
            ->all();

        $enabledTenants = [];
        if (Schema::hasTable('feature_flags') && !empty($tenantIds)) {
            $enabledTenants = FeatureFlag::query()
                ->withoutGlobalScope('tenant_scope')
                ->where('key', 'hakedis_module')
                ->where('enabled', true)
                ->whereIn('tenant_id', $tenantIds)
                ->pluck('tenant_id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        return view('super-admin.settlements.index', [
            'clients' => $clients,
            'enabledTenants' => array_flip($enabledTenants),
            'moduleDefined' => Schema::hasTable('modules')
                ? Module::query()->where('code', 'feature.hakedis')->exists()
                : false,
        ]);
    }

    public function setVisibility(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role !== 'client', 404);

        $validated = $request->validate([
            'visible' => ['required', 'boolean'],
        ]);

        if (!Schema::hasTable('modules') || !Schema::hasTable('feature_flags') || !Schema::hasTable('tenants')) {
            return back()->with('error', 'Gerekli tablolar bulunamadı. Lütfen migration çalıştırın.');
        }

        Module::query()->updateOrCreate(
            ['code' => 'feature.hakedis'],
            [
                'name' => 'Hakediş Kontrol Merkezi',
                'description' => 'Payout, mutabakat ve sapma merkezi.',
                'type' => 'feature',
                'billing_type' => 'recurring',
                'is_active' => true,
                'sort_order' => 0,
            ]
        );

        $tenantId = (int) ($user->tenant_id ?: $user->id);
        abort_if($tenantId <= 0, 400, 'Tenant bulunamadı.');

        $this->ensureTenantRow($tenantId, $user);

        $visible = (bool) $validated['visible'];

        FeatureFlag::query()->withoutGlobalScope('tenant_scope')->updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => 'hakedis_module'],
            ['enabled' => $visible]
        );

        return back()->with(
            'success',
            $visible
                ? "{$user->name} için Hakediş görünürlüğü açıldı."
                : "{$user->name} için Hakediş görünürlüğü kapatıldı."
        );
    }

    private function ensureTenantRow(int $tenantId, User $user): void
    {
        $exists = DB::table('tenants')->where('id', $tenantId)->exists();
        if ($exists) {
            return;
        }

        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => trim((string) ($user->name ?: $user->email ?: 'Tenant ' . $tenantId)),
            'status' => 'active',
            'plan' => 'starter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

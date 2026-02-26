<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CalculateOrderProfitJob;
use App\Models\Marketplace;
use App\Models\MarketplaceFeeRule;
use App\Models\Order;
use App\Models\OrderProfitSnapshot;
use App\Models\ProfitCostProfile;
use App\Models\User;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfitEngineController extends Controller
{
    public function index(Request $request): View
    {
        $owner = $this->resolveOwner($request);
        $tenantId = $this->resolveTenantId($owner);

        $query = OrderProfitSnapshot::query()
            ->with(['order.marketplace'])
            ->where('tenant_id', $tenantId)
            ->where('user_id', $owner->id)
            ->latest('calculated_at');

        if ($request->filled('marketplace')) {
            $query->where('marketplace', strtolower((string) $request->input('marketplace')));
        }
        if ($request->filled('rule_missing')) {
            $ruleMissing = $request->boolean('rule_missing');
            $query->where('meta->rule_missing', $ruleMissing);
        }

        $snapshots = $query->paginate(20)->withQueryString();
        $profiles = ProfitCostProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $owner->id)
            ->orderByDesc('is_default')
            ->latest('id')
            ->get();
        $feeRules = MarketplaceFeeRule::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $owner->id)
            ->orderByDesc('priority')
            ->latest('id')
            ->get();
        $marketplaces = Marketplace::query()->where('is_active', true)->get();

        return view('admin.profit-engine.index', compact('snapshots', 'profiles', 'feeRules', 'marketplaces', 'owner'));
    }

    public function show(OrderProfitSnapshot $snapshot): View
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        if (!$owner->isSuperAdmin() && (int) $snapshot->user_id !== (int) $owner->id) {
            abort(403);
        }

        $snapshot->load('order.orderItems', 'order.marketplace');

        return view('admin.profit-engine.show', compact('snapshot'));
    }

    public function recalculate(Order $order): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        if (!$owner->isSuperAdmin() && (int) $order->user_id !== (int) $owner->id) {
            abort(403);
        }

        CalculateOrderProfitJob::dispatch($order->id);

        return back()->with('success', 'Siparis karlilik hesaplama kuyruga alindi.');
    }

    public function storeProfile(Request $request): RedirectResponse
    {
        $owner = $this->resolveOwner($request);
        $tenantId = $this->resolveTenantId($owner);

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'packaging_cost' => 'required|numeric|min:0',
            'operational_cost' => 'required|numeric|min:0',
            'return_rate_default' => 'required|numeric|min:0|max:100',
            'ad_cost_default' => 'required|numeric|min:0',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);
        if ($isDefault) {
            ProfitCostProfile::query()
                ->where('tenant_id', $tenantId)
                ->where('user_id', $owner->id)
                ->update(['is_default' => false]);
        }

        ProfitCostProfile::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $owner->id,
            'name' => $validated['name'],
            'packaging_cost' => $validated['packaging_cost'],
            'operational_cost' => $validated['operational_cost'],
            'return_rate_default' => $validated['return_rate_default'],
            'ad_cost_default' => $validated['ad_cost_default'],
            'is_default' => $isDefault,
        ]);

        return back()->with('success', 'Masraf profili kaydedildi.');
    }

    public function updateProfile(Request $request, ProfitCostProfile $profile): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $profile->user_id !== (int) $owner->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'packaging_cost' => 'required|numeric|min:0',
            'operational_cost' => 'required|numeric|min:0',
            'return_rate_default' => 'required|numeric|min:0|max:100',
            'ad_cost_default' => 'required|numeric|min:0',
            'is_default' => 'nullable|boolean',
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);
        if ($isDefault) {
            ProfitCostProfile::query()
                ->where('tenant_id', $profile->tenant_id)
                ->where('user_id', $profile->user_id)
                ->where('id', '!=', $profile->id)
                ->update(['is_default' => false]);
        }

        $profile->update([
            'name' => $validated['name'],
            'packaging_cost' => $validated['packaging_cost'],
            'operational_cost' => $validated['operational_cost'],
            'return_rate_default' => $validated['return_rate_default'],
            'ad_cost_default' => $validated['ad_cost_default'],
            'is_default' => $isDefault,
        ]);

        return back()->with('success', 'Masraf profili guncellendi.');
    }

    public function destroyProfile(ProfitCostProfile $profile): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $profile->user_id !== (int) $owner->id) {
            abort(403);
        }

        $profile->delete();

        return back()->with('success', 'Masraf profili silindi.');
    }

    public function storeFeeRule(Request $request): RedirectResponse
    {
        $owner = $this->resolveOwner($request);
        $tenantId = $this->resolveTenantId($owner);

        $validated = $request->validate([
            'marketplace' => 'required|string|max:50',
            'sku' => 'nullable|string|max:120',
            'category_id' => 'nullable|integer|min:1',
            'brand_id' => 'nullable|integer|min:1',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'fixed_fee' => 'required|numeric|min:0',
            'shipping_fee' => 'required|numeric|min:0',
            'service_fee' => 'required|numeric|min:0',
            'campaign_contribution_rate' => 'required|numeric|min:0|max:100',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'priority' => 'required|integer|min:0|max:10000',
            'active' => 'nullable|boolean',
        ]);

        MarketplaceFeeRule::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $owner->id,
            'marketplace' => strtolower($validated['marketplace']),
            'sku' => $validated['sku'] ?: null,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'commission_rate' => $validated['commission_rate'],
            'fixed_fee' => $validated['fixed_fee'],
            'shipping_fee' => $validated['shipping_fee'],
            'service_fee' => $validated['service_fee'],
            'campaign_contribution_rate' => $validated['campaign_contribution_rate'],
            'vat_rate' => $validated['vat_rate'],
            'priority' => $validated['priority'],
            'active' => (bool) ($validated['active'] ?? false),
        ]);

        return back()->with('success', 'Fee rule kaydedildi.');
    }

    public function updateFeeRule(Request $request, MarketplaceFeeRule $rule): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $rule->user_id !== (int) $owner->id) {
            abort(403);
        }

        $validated = $request->validate([
            'marketplace' => 'required|string|max:50',
            'sku' => 'nullable|string|max:120',
            'category_id' => 'nullable|integer|min:1',
            'brand_id' => 'nullable|integer|min:1',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'fixed_fee' => 'required|numeric|min:0',
            'shipping_fee' => 'required|numeric|min:0',
            'service_fee' => 'required|numeric|min:0',
            'campaign_contribution_rate' => 'required|numeric|min:0|max:100',
            'vat_rate' => 'required|numeric|min:0|max:100',
            'priority' => 'required|integer|min:0|max:10000',
            'active' => 'nullable|boolean',
        ]);

        $rule->update([
            'marketplace' => strtolower($validated['marketplace']),
            'sku' => $validated['sku'] ?: null,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
            'commission_rate' => $validated['commission_rate'],
            'fixed_fee' => $validated['fixed_fee'],
            'shipping_fee' => $validated['shipping_fee'],
            'service_fee' => $validated['service_fee'],
            'campaign_contribution_rate' => $validated['campaign_contribution_rate'],
            'vat_rate' => $validated['vat_rate'],
            'priority' => $validated['priority'],
            'active' => (bool) ($validated['active'] ?? false),
        ]);

        return back()->with('success', 'Fee rule guncellendi.');
    }

    public function destroyFeeRule(MarketplaceFeeRule $rule): RedirectResponse
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);
        if (!$owner->isSuperAdmin() && (int) $rule->user_id !== (int) $owner->id) {
            abort(403);
        }

        $rule->delete();

        return back()->with('success', 'Fee rule silindi.');
    }

    private function resolveOwner(Request $request): User
    {
        $owner = SupportUser::currentUser();
        abort_if(!$owner, 401);

        if (!$owner->isSuperAdmin()) {
            return $owner;
        }

        $targetUserId = (int) $request->query('user_id', 0);
        if ($targetUserId <= 0) {
            return $owner;
        }

        return User::query()->findOrFail($targetUserId);
    }

    private function resolveTenantId(User $owner): int
    {
        return (int) ($owner->tenant_id ?: $owner->id);
    }
}

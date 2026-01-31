<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncMarketplaceCategoriesJob;
use App\Models\AppSetting;
use App\Models\Marketplace;
use App\Models\MarketplaceCredential;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $marketplaces = Marketplace::where('is_active', true)
            ->with(['credentials' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            }])
            ->get();

        $plan = $user?->getActivePlan();
        if ($plan) {
            $marketplaces = $marketplaces
                ->filter(fn ($marketplace) => $plan->hasModule('integrations.marketplace.' . ($marketplace->code ?? '')))
                ->values();
        }

        return view('admin.integrations.index', compact('marketplaces'));
    }

    public function edit(Marketplace $marketplace): View
    {
        $user = auth()->user();
        $credential = MarketplaceCredential::where('user_id', $user->id)
            ->where('marketplace_id', $marketplace->id)
            ->first();

        return view('admin.integrations.edit', compact('marketplace', 'credential'));
    }

    public function update(Request $request, Marketplace $marketplace)
    {
        $user = $request->user();
        $rules = [
            'api_key' => 'nullable|string|max:255',
            'api_secret' => 'nullable|string|max:255',
            'supplier_id' => 'nullable|string|max:255',
            'store_id' => 'nullable|string|max:255',
            'store_name' => 'nullable|string|max:255',
            'fixed_description' => 'nullable|string|max:5000',
            'is_active' => 'boolean',
        ];

        if ($marketplace->code === 'hepsiburada') {
            $rules['store_name'] = 'required|string|max:255';
            $rules['supplier_id'] = 'required|string|max:255';
            $rules['api_key'] = 'required|string|max:255';
        }

        if ($marketplace->code === 'ciceksepeti') {
            $rules['api_key'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules, [
            'api_key.required' => 'API anahtarı zorunludur.',
            'supplier_id.required' => 'Mağaza ID zorunludur.',
            'store_name.required' => 'Mağaza adı zorunludur.',
        ]);

        $credential = MarketplaceCredential::where('user_id', $user->id)
            ->where('marketplace_id', $marketplace->id)
            ->first();

        $wasActive = (bool) ($credential?->is_active ?? false);
        $wantsActive = (bool) $request->boolean('is_active');
        $hadCredential = (bool) $credential;
        $original = $credential ? $credential->only(['api_key', 'api_secret', 'supplier_id', 'store_id', 'is_active']) : null;

        if ($wantsActive && !$wasActive && $user && !$user->isSuperAdmin()) {
            $subscription = $user->subscription;
            if (!$subscription || !$subscription->isActive() || !$subscription->canAddMarketplace()) {
                return back()->with('info', 'Abonelik limitiniz doldu. Daha fazla pazaryeri ekleyemezsiniz.');
            }
        }

        $extra = $credential?->extra_credentials ?? [];
        foreach (['store_name', 'fixed_description'] as $field) {
            if ($request->has($field)) {
                $extra[$field] = $validated[$field] ?? null;
            }
        }
        $extra = array_filter($extra ?? [], static fn ($value) => $value !== null && $value !== '');
        if (empty($extra)) {
            $extra = null;
        }

        $data = [
            'user_id' => $user->id,
            'marketplace_id' => $marketplace->id,
            'api_key' => $validated['api_key'] ?? null,
            'api_secret' => $validated['api_secret'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'store_id' => $validated['store_id'] ?? null,
            'is_active' => $wantsActive,
            'extra_credentials' => $extra,
        ];

        $credential = MarketplaceCredential::updateOrCreate(
            [
                'user_id' => $user->id,
                'marketplace_id' => $marketplace->id,
            ],
            $data
        );

        if ($user && !$user->isSuperAdmin()) {
            if ($wantsActive && !$wasActive) {
                $user->subscription?->incrementMarketplaces();
            }
            if (!$wantsActive && $wasActive) {
                $user->subscription?->decrementMarketplaces();
            }
        }

        if ($wantsActive) {
            $categoryMappingEnabled = (bool) AppSetting::getValue('category_mapping_enabled', true);
            $autoSyncEnabled = (bool) AppSetting::getValue('category_mapping_auto_sync_enabled', true);

            $changedCredentials = false;
            if (!$hadCredential) {
                $changedCredentials = true;
            } elseif ($original) {
                $changedCredentials =
                    ($original['api_key'] ?? null) !== ($credential->api_key ?? null) ||
                    ($original['api_secret'] ?? null) !== ($credential->api_secret ?? null) ||
                    ($original['supplier_id'] ?? null) !== ($credential->supplier_id ?? null) ||
                    ($original['store_id'] ?? null) !== ($credential->store_id ?? null) ||
                    (bool) ($original['is_active'] ?? false) !== (bool) ($credential->is_active ?? false);
            }

            if ($categoryMappingEnabled && $autoSyncEnabled && $changedCredentials) {
                SyncMarketplaceCategoriesJob::dispatch($credential->id);
            }
        }

        return redirect()->route('admin.integrations.index')
            ->with('success', 'Pazaryeri bağlantısı güncellendi.');
    }

    public function test(Marketplace $marketplace)
    {
        return back()->with('info', 'Bağlantı testi yakında eklenecek.');
    }
}

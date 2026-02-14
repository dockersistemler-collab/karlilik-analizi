<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceListing;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryMappingController extends Controller
{
    public function index(Request $request): View
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        $accounts = MarketplaceAccount::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('store_name')
            ->get();
        $products = Product::query()
            ->where('user_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'barcode']);
        $listings = MarketplaceListing::query()
            ->with(['account', 'product'])
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->paginate(30);

        return view('admin.inventory.mappings.index', [
            'accounts' => $accounts,
            'products' => $products,
            'listings' => $listings,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        $validated = $this->validatePayload($request, $tenantId);

        MarketplaceListing::query()->create([
            'tenant_id' => $tenantId,
            ...$validated,
        ]);

        return back()->with('success', 'Listing eslestirmesi olusturuldu.');
    }

    public function update(Request $request, MarketplaceListing $listing): RedirectResponse
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        abort_unless((int) $listing->tenant_id === $tenantId, 404);
        $validated = $this->validatePayload($request, $tenantId);

        $listing->update($validated);

        return back()->with('success', 'Listing eslestirmesi guncellendi.');
    }

    public function destroy(Request $request, MarketplaceListing $listing): RedirectResponse
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        abort_unless((int) $listing->tenant_id === $tenantId, 404);
        $listing->delete();

        return back()->with('success', 'Listing eslestirmesi silindi.');
    }

    private function validatePayload(Request $request, int $tenantId): array
    {
        $validated = $request->validate([
            'marketplace_account_id' => 'required|integer|exists:marketplace_accounts,id',
            'product_id' => 'required|integer|exists:products,id',
            'external_listing_id' => 'nullable|string|max:255',
            'external_sku' => 'nullable|string|max:255',
            'external_barcode' => 'nullable|string|max:255',
            'sync_enabled' => 'nullable|boolean',
            'last_known_market_stock' => 'nullable|integer|min:0',
        ]);

        $accountBelongs = MarketplaceAccount::query()
            ->where('id', (int) $validated['marketplace_account_id'])
            ->where('tenant_id', $tenantId)
            ->exists();
        $productBelongs = Product::query()
            ->where('id', (int) $validated['product_id'])
            ->where('user_id', $tenantId)
            ->exists();

        abort_unless($accountBelongs && $productBelongs, 404);

        $validated['sync_enabled'] = $request->boolean('sync_enabled', true);

        return $validated;
    }
}

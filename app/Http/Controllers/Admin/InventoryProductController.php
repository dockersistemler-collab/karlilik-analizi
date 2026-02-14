<?php

namespace App\Http\Controllers\Admin;

use App\Events\ProductStockUpdated;
use App\Http\Controllers\Controller;
use App\Jobs\PushStockToMarketplacesJob;
use App\Jobs\PushStocksToMarketplaceJob;
use App\Models\Marketplace;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceCredential;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\StockAlert;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryProductController extends Controller
{
    public function index(Request $request): View
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $user = $request->user();
        $query = Product::withCount('marketplaceProducts')
            ->with(['marketplaceProducts.marketplace' => function ($builder) {
                $builder->select('id', 'name', 'code', 'settings');
            }]);

        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $term = $request->query('search');
            $query->where(function ($builder) use ($term) {
                $builder->where('name', 'like', '%'.$term.'%')
                    ->orWhere('sku', 'like', '%'.$term.'%')
                    ->orWhere('barcode', 'like', '%'.$term.'%')
                    ->orWhere('brand', 'like', '%'.$term.'%');
            });
        }

        $selectedMarketplaceId = (int) $request->query('marketplace_id', 0);
        if ($selectedMarketplaceId > 0) {
            $query->whereHas('marketplaceProducts', function ($builder) use ($selectedMarketplaceId) {
                $builder->where('marketplace_id', $selectedMarketplaceId);
            });
        }

        $sortKey = $request->query('sort');
        $sortDir = $request->query('dir', 'asc');
        $allowedSorts = [
            'sku' => 'sku',
            'name' => 'name',
            'brand' => 'brand',
            'price' => 'price',
            'stock' => 'stock_quantity',
            'marketplace' => 'marketplace_products_count',
            'status' => 'is_active',
        ];
        if ($sortKey && isset($allowedSorts[$sortKey])) {
            $direction = $sortDir === 'desc' ? 'desc' : 'asc';
            $query->orderBy($allowedSorts[$sortKey], $direction);
        } else {
            $query->orderBy('name', 'asc');
        }

        $perPageParam = $request->query('per_page', 25);
        $allowed = [25, 50, 100];
        $perPage = (int) $perPageParam;
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 25;
        }
        $products = $query->paginate($perPage)->withQueryString();

        $inventoryMarketplaces = Marketplace::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('admin.products.index', [
            'products' => $products,
            'isInventoryView' => true,
            'inventoryMarketplaces' => $inventoryMarketplaces,
            'selectedMarketplaceId' => $selectedMarketplaceId,
        ]);
    }

    public function edit(Request $request, Product $product): View
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        abort_unless((int) $product->user_id === $tenantId, 404);

        return view('admin.inventory.products.edit', [
            'product' => $product,
        ]);
    }

    public function syncMarketplace(Request $request): RedirectResponse
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        $validated = $request->validate([
            'sync_scope' => 'required|in:all,single,selected',
            'marketplace_id' => 'nullable|integer|exists:marketplaces,id',
            'selected_product_ids_csv' => 'nullable|string',
        ]);

        if (($validated['sync_scope'] ?? null) === 'selected') {
            $selectedProductIds = collect(explode(',', (string) ($validated['selected_product_ids_csv'] ?? '')))
                ->map(fn ($id) => (int) trim($id))
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            if ($selectedProductIds->isEmpty()) {
                return back()->with('error', 'Lutfen en az bir urun secin.');
            }

            $ownedProductIds = Product::query()
                ->where('user_id', $tenantId)
                ->whereIn('id', $selectedProductIds->all())
                ->pluck('id')
                ->all();

            if (empty($ownedProductIds)) {
                return back()->with('error', 'Secilen urunler icin yetkiniz yok.');
            }

            $marketplaceId = (int) ($validated['marketplace_id'] ?? 0);
            if ($marketplaceId > 0) {
                $marketplace = Marketplace::query()
                    ->where('id', $marketplaceId)
                    ->where('is_active', true)
                    ->firstOrFail();

                $marketplaceCode = strtolower(trim((string) ($marketplace->code ?? '')));
                if (!$this->hasApiConnection($tenantId, $marketplaceId, $marketplaceCode)) {
                    return back()->with('error', 'Bu pazaryeri icin API baglantisi gerekli.');
                }
            } elseif (!$this->hasApiConnection($tenantId)) {
                return back()->with('error', 'Stok gonderimi icin en az bir aktif pazaryeri API baglantisi gerekli.');
            }

            foreach ($ownedProductIds as $productId) {
                PushStockToMarketplacesJob::dispatch($tenantId, (int) $productId);
            }

            return back()->with('success', 'Secili urunler icin stok gonderimi kuyruga alindi.');
        }

        if (($validated['sync_scope'] ?? null) === 'all') {
            $hasAnyApi = $this->hasApiConnection($tenantId);
            if (!$hasAnyApi) {
                return back()->with('error', 'Stok gonderimi icin en az bir aktif pazaryeri API baglantisi gerekli.');
            }

            $marketplaceCodes = Marketplace::query()
                ->where('is_active', true)
                ->whereNotNull('code')
                ->pluck('code')
                ->map(fn ($code) => strtolower(trim((string) $code)))
                ->filter(fn ($code) => $code !== '')
                ->unique()
                ->values()
                ->all();

            foreach ($marketplaceCodes as $marketplaceCode) {
                PushStocksToMarketplaceJob::dispatch($tenantId, $marketplaceCode);
            }

            return back()->with('success', 'Tum pazaryerleri icin stok gonderimi kuyruga alindi.');
        }

        $marketplaceId = (int) ($validated['marketplace_id'] ?? 0);
        if ($marketplaceId <= 0) {
            return back()->with('error', 'Pazaryeri secimi zorunlu.');
        }

        $marketplace = Marketplace::query()
            ->where('id', $marketplaceId)
            ->where('is_active', true)
            ->firstOrFail();

        $marketplaceCode = strtolower(trim((string) ($marketplace->code ?? '')));
        if (!$this->hasApiConnection($tenantId, $marketplaceId, $marketplaceCode)) {
            return back()->with('error', 'Bu pazaryeri icin API baglantisi gerekli.');
        }

        PushStocksToMarketplaceJob::dispatch($tenantId, (string) $marketplace->code);

        return back()->with('success', 'Secili pazaryeri icin stok gonderimi kuyruga alindi.');
    }

    private function hasApiConnection(int $tenantId, ?int $marketplaceId = null, ?string $marketplaceCode = null): bool
    {
        $credentials = MarketplaceCredential::query()
            ->where('user_id', $tenantId)
            ->where('is_active', true)
            ->when($marketplaceId, function ($query) use ($marketplaceId) {
                $query->where('marketplace_id', $marketplaceId);
            })
            ->get();

        foreach ($credentials as $credential) {
            if ($this->credentialHasAuthData($credential)) {
                return true;
            }
        }

        $accounts = MarketplaceAccount::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($marketplaceCode, function ($query) use ($marketplaceCode) {
                $query->whereRaw('LOWER(COALESCE(connector_key, marketplace)) = ?', [strtolower($marketplaceCode)]);
            })
            ->get();

        foreach ($accounts as $account) {
            if ($this->accountHasAuthData($account)) {
                return true;
            }
        }

        return false;
    }

    private function credentialHasAuthData(MarketplaceCredential $credential): bool
    {
        $extra = is_array($credential->extra_credentials) ? $credential->extra_credentials : [];

        return $this->hasAnyFilledValue([
            $credential->api_key,
            $credential->api_secret,
            $credential->access_token,
            $credential->refresh_token,
            $credential->supplier_id,
            $credential->store_id,
            data_get($extra, 'token'),
            data_get($extra, 'seller_id'),
            data_get($extra, 'merchant_id'),
            data_get($extra, 'client_id'),
        ]);
    }

    private function accountHasAuthData(MarketplaceAccount $account): bool
    {
        $creds = $account->credentials_json;
        if (!is_array($creds)) {
            $creds = $account->credentials;
        }
        if (!is_array($creds)) {
            $creds = [];
        }

        return $this->hasAnyFilledValue([
            data_get($creds, 'api_key'),
            data_get($creds, 'api_secret'),
            data_get($creds, 'access_token'),
            data_get($creds, 'token'),
            data_get($creds, 'refresh_token'),
            data_get($creds, 'supplier_id'),
            data_get($creds, 'seller_id'),
            data_get($creds, 'merchant_id'),
            data_get($creds, 'store_id'),
            data_get($creds, 'client_id'),
            data_get($creds, 'app_key'),
            data_get($creds, 'app_secret'),
        ]);
    }

    private function hasAnyFilledValue(array $values): bool
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return true;
            }

            if (is_numeric($value) && (string) $value !== '') {
                return true;
            }
        }

        return false;
    }

    public function assignMarketplace(Request $request): RedirectResponse
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        $validated = $request->validate([
            'marketplace_id' => 'required|integer|exists:marketplaces,id',
            'selected_product_ids_csv' => 'required|string',
        ]);

        $marketplaceId = (int) $validated['marketplace_id'];
        $marketplace = Marketplace::query()
            ->where('id', $marketplaceId)
            ->where('is_active', true)
            ->firstOrFail();

        $marketplaceCode = strtolower(trim((string) ($marketplace->code ?? '')));
        if (!$this->hasApiConnection($tenantId, $marketplaceId, $marketplaceCode)) {
            return back()->with('error', 'Bu pazaryeri icin API baglantisi gerekli.');
        }

        $selectedProductIds = collect(explode(',', (string) $validated['selected_product_ids_csv']))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($selectedProductIds->isEmpty()) {
            return back()->with('error', 'Lutfen en az bir urun secin.');
        }

        $products = Product::query()
            ->where('user_id', $tenantId)
            ->whereIn('id', $selectedProductIds->all())
            ->get(['id', 'price', 'stock_quantity']);

        if ($products->isEmpty()) {
            return back()->with('error', 'Secilen urunler icin yetkiniz yok.');
        }

        $created = 0;
        foreach ($products as $product) {
            $record = MarketplaceProduct::query()->firstOrCreate(
                [
                    'product_id' => (int) $product->id,
                    'marketplace_id' => $marketplaceId,
                ],
                [
                    'price' => (float) $product->price,
                    'stock_quantity' => (int) $product->stock_quantity,
                    'status' => 'draft',
                ]
            );

            if ($record->wasRecentlyCreated) {
                $created++;
            }
        }

        return back()->with('success', 'Pazaryerinde acildi. Yeni eklenen: '.$created);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        if (auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        abort_unless((int) $product->user_id === $tenantId, 404);

        $validated = $request->validate([
            'direction' => 'required|in:increase,decrease',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:1000',
            'critical_stock_level' => 'nullable|integer|min:0|max:1000000',
        ]);

        $oldQuantity = (int) $product->stock_quantity;
        $delta = (int) $validated['quantity'];
        if ($validated['direction'] === 'decrease') {
            $delta *= -1;
        }
        $newQuantity = max(0, $oldQuantity + $delta);

        if ($request->filled('critical_stock_level')) {
            $product->critical_stock_level = (int) $validated['critical_stock_level'];
        }
        $product->stock_quantity = $newQuantity;
        $product->save();

        StockMovement::query()->create([
            'tenant_id' => $tenantId,
            'product_id' => $product->id,
            'type' => 'manual_adjust',
            'quantity_change' => $delta,
            'meta_json' => [
                'note' => $validated['note'] ?? null,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
            ],
            'created_by' => (int) $request->user()->id,
        ]);

        $this->syncCriticalAlert($product, $tenantId);

        event(new ProductStockUpdated(
            product: $product->fresh(),
            oldQuantity: $oldQuantity,
            newQuantity: $newQuantity,
            actorId: (int) $request->user()->id
        ));

        return redirect()
            ->route('portal.inventory.admin.products.index')
            ->with('success', 'Stok guncellendi.');
    }

    private function syncCriticalAlert(Product $product, int $tenantId): void
    {
        $isCritical = (int) $product->stock_quantity <= (int) $product->critical_stock_level;

        if ($isCritical) {
            StockAlert::query()->updateOrCreate(
                [
                    'product_id' => $product->id,
                    'alert_type' => 'critical_stock',
                ],
                [
                    'tenant_id' => $tenantId,
                    'threshold' => (int) $product->critical_stock_level,
                    'is_active' => true,
                    'last_notified_at' => now(),
                ]
            );

            return;
        }

        StockAlert::query()
            ->where('product_id', $product->id)
            ->where('alert_type', 'critical_stock')
            ->update([
                'is_active' => false,
            ]);
    }
}

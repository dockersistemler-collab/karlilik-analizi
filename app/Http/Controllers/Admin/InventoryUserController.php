<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryUserController extends Controller
{
    public function index(Request $request): View
    {
        if (!auth('subuser')->check()) {
            abort(404);
        }

        $tenantId = (int) $request->user()->id;
        $query = Product::query()
            ->withCount('marketplaceProducts')
            ->with(['marketplaceProducts.marketplace' => function ($builder) {
                $builder->select('id', 'name', 'code', 'settings');
            }])
            ->where('user_id', $tenantId)
            ->orderBy('name');

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
            'cost' => 'cost_price',
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
        $subUser = auth('subuser')->user();
        $canManageInventory = $subUser && $subUser->hasPermission('products');

        return view('admin.products.index', [
            'products' => $products,
            'isInventoryView' => true,
            'isReadOnlyInventory' => !$canManageInventory,
            'inventoryMarketplaces' => $inventoryMarketplaces,
            'selectedMarketplaceId' => $selectedMarketplaceId,
        ]);
    }
}

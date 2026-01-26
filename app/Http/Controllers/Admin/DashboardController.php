<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Product;
use App\Models\Order;
use App\Models\MarketplaceProduct;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $productQuery = Product::query();
        $orderQuery = Order::query();
        $marketplaceProductQuery = MarketplaceProduct::query();

        if ($user && !$user->isSuperAdmin()) {
            $productQuery->where('user_id', $user->id);
            $orderQuery->where('user_id', $user->id);
            $marketplaceProductQuery->whereHas('product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }

        $stats = [
            'total_products' => $productQuery->count(),
            'active_products' => (clone $productQuery)->where('is_active', true)->count(),
            'total_marketplaces' => Marketplace::where('is_active', true)->count(),
            'total_orders' => $orderQuery->count(),
            'pending_orders' => (clone $orderQuery)->where('status', 'pending')->count(),
            'total_marketplace_products' => $marketplaceProductQuery->count(),
        ];

        $recent_orders = $orderQuery->with('marketplace')
            ->latest()
            ->take(10)
            ->get();

        $marketplaces = Marketplace::withCount(['products', 'orders'])
            ->where('is_active', true)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_orders', 'marketplaces'));
    }
}

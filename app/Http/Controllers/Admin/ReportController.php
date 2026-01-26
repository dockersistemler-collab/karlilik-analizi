<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Order::query();

        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue_total')
            ->selectRaw('COALESCE(SUM(commission_amount), 0) as commission_total')
            ->selectRaw('COALESCE(SUM(net_amount), 0) as net_total')
            ->first();

        $ordersByStatus = (clone $query)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $ordersByMarketplace = (clone $query)
            ->select('marketplace_id', DB::raw('COUNT(*) as total'))
            ->groupBy('marketplace_id')
            ->orderByDesc('total')
            ->get();

        $marketplaceMap = Marketplace::whereIn('id', $ordersByMarketplace->pluck('marketplace_id'))
            ->pluck('name', 'id');

        $topProducts = (clone $query)
            ->whereNotNull('items')
            ->select('items')
            ->get()
            ->flatMap(function ($order) {
                return is_array($order->items) ? $order->items : [];
            })
            ->groupBy(function ($item) {
                return $item['sku'] ?? $item['name'] ?? 'Bilinmeyen';
            })
            ->map(function ($items) {
                $first = $items->first();
                $name = $first['name'] ?? 'Ürün';
                $qty = $items->sum(function ($item) {
                    return (int) ($item['quantity'] ?? 0);
                });
                $total = $items->sum(function ($item) {
                    return (float) ($item['price'] ?? 0) * (int) ($item['quantity'] ?? 0);
                });
                return [
                    'name' => $name,
                    'quantity' => $qty,
                    'total' => $total,
                ];
            })
            ->sortByDesc('quantity')
            ->values()
            ->take(10);

        $statusOptions = [
            'pending' => 'Beklemede',
            'approved' => 'Onaylandı',
            'shipped' => 'Kargoda',
            'delivered' => 'Teslim',
            'cancelled' => 'İptal',
            'returned' => 'İade',
        ];

        $marketplaces = Marketplace::where('is_active', true)->get();

        $productsByStock = Product::query()
            ->when($user && !$user->isSuperAdmin(), function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orderByDesc('stock_quantity')
            ->take(10)
            ->get(['name', 'stock_quantity', 'price']);

        return view('admin.reports', [
            'summary' => $summary,
            'ordersByStatus' => $ordersByStatus,
            'ordersByMarketplace' => $ordersByMarketplace,
            'marketplaceMap' => $marketplaceMap,
            'topProducts' => $topProducts,
            'statusOptions' => $statusOptions,
            'marketplaces' => $marketplaces,
            'productsByStock' => $productsByStock,
        ]);
    }
}

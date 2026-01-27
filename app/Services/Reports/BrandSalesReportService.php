<?php

namespace App\Services\Reports;

use App\Models\Marketplace;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

class BrandSalesReportService
{
    public function get(User $user, array $filters, Collection $marketplaces): array
    {
        $query = Order::query()->where('user_id', $user->id);

        if (!empty($filters['marketplace_id'])) {
            $query->where('marketplace_id', $filters['marketplace_id']);
        }

        ReportFilters::applyDateRange($query, 'order_date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $orders = $query->whereNotNull('items')->get(['items', 'marketplace_id']);

        $grouped = collect();

        foreach ($orders as $order) {
            $items = is_array($order->items) ? $order->items : [];
            foreach ($items as $item) {
                $brand = $item['brand'] ?? $item['marka'] ?? 'Bilinmeyen';
                $qty = (int) ($item['quantity'] ?? $item['qty'] ?? $item['adet'] ?? 0);
                $price = (float) ($item['price'] ?? $item['unit_price'] ?? $item['fiyat'] ?? 0);
                $revenue = $price * $qty;

                if (!$grouped->has($brand)) {
                    $row = [
                        'brand' => $brand,
                        'revenue' => 0,
                        'orders' => 0,
                    ];
                    foreach ($marketplaces as $marketplace) {
                        $row['mp_' . $marketplace->id] = 0;
                    }
                    $grouped->put($brand, $row);
                }

                $row = $grouped->get($brand);
                $row['revenue'] += $revenue;
                $row['orders'] += $qty;
                if (!empty($order->marketplace_id)) {
                    $row['mp_' . $order->marketplace_id] += $revenue;
                }
                $grouped->put($brand, $row);
            }
        }

        $table = $grouped->values()->sortByDesc('revenue')->values()->all();
        $chart = [
            'labels' => array_map(fn ($row) => $row['brand'], $table),
            'revenue' => array_map(fn ($row) => (float) $row['revenue'], $table),
            'orders' => array_map(fn ($row) => (int) $row['orders'], $table),
        ];

        return [
            'table' => $table,
            'chart' => $chart,
        ];
    }
}

<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

class TopProductsReportService
{
    public function get(User $user, array $filters, int $limit = 100): Collection
    {
        $query = Order::query()->where('user_id', $user->id);

        if (!empty($filters['marketplace_id'])) {
            $query->where('marketplace_id', $filters['marketplace_id']);
        }

        ReportFilters::applyDateRange($query, 'order_date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $orders = $query->whereNotNull('items')->get(['items']);

        $grouped = collect();

        foreach ($orders as $order) {
            $items = is_array($order->items) ? $order->items : [];
            foreach ($items as $item) {
                $sku = $item['sku'] ?? $item['stock_code'] ?? $item['stok_kodu'] ?? null;
                $name = $item['name'] ?? $item['product_name'] ?? $item['urun_adi'] ?? 'Ürün';
                $key = $sku ?: $name;
                $qty = (int) ($item['quantity'] ?? $item['qty'] ?? $item['adet'] ?? 0);
                $price = (float) ($item['price'] ?? $item['unit_price'] ?? $item['fiyat'] ?? 0);

                if (!$grouped->has($key)) {
                    $grouped->put($key, [
                        'stock_code' => $sku,
                        'name' => $name,
                        'quantity' => 0,
                        'total' => 0,
                    ]);
                }
$row = $grouped->get($key);
                $row['quantity'] += $qty;
                $row['total'] += $price * $qty;
                $grouped->put($key, $row);
            }
        }

        return $grouped->values()
            ->sortByDesc('quantity')
            ->take($limit)
            ->values();
    }
}

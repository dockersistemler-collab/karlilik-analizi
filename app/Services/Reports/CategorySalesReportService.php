<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;

class CategorySalesReportService
{
    public function get(User $user, array $filters): Collection
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
                $category = $item['category'] ?? $item['kategori'] ?? 'Bilinmeyen';
                $qty = (int) ($item['quantity'] ?? $item['qty'] ?? $item['adet'] ?? 0);
                $price = (float) ($item['price'] ?? $item['unit_price'] ?? $item['fiyat'] ?? 0);
                $revenue = $price * $qty;

                if (!$grouped->has($category)) {
                    $grouped->put($category, [
                        'label' => $category,
                        'revenue' => 0,
                        'orders' => 0,
                    ]);
                }

                $row = $grouped->get($category);
                $row['revenue'] += $revenue;
                $row['orders'] += $qty;
                $grouped->put($category, $row);
            }
        }

        return $grouped->values()
            ->sortByDesc('revenue')
            ->values();
    }
}

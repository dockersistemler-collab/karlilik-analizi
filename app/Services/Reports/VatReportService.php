<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class VatReportService
{
    public function get(User $user, array $filters): array
    {
        $query = Order::query()->where('user_id', $user->id);

        if (!empty($filters['marketplace_id'])) {
            $query->where('marketplace_id', $filters['marketplace_id']);
        }

        ReportFilters::applyDateRange($query, 'order_date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        $orders = $query->whereNotNull('items')->get(['items', 'order_date', 'marketplace_id']);

        $grouped = collect();
        $totalsByMarketplace = [];

        foreach ($orders as $order) {
            $period = Carbon::parse($order->order_date)->format('Y-m');
            $items = $this->normalizeItems($order->items);
            $vatTotal = 0.0;

            foreach ($items as $item) {
                $vatTotal += $this->calculateItemVat($item);
            }

            if (!$grouped->has($period)) {
                $grouped->put($period, 0.0);
            }
$grouped->put($period, $grouped->get($period) + $vatTotal);

            $marketplaceId = $order->marketplace_id;
            if (!isset($totalsByMarketplace[$marketplaceId])) {
                $totalsByMarketplace[$marketplaceId] = 0.0;
            }
$totalsByMarketplace[$marketplaceId] += $vatTotal;
        }
$labels = $grouped->keys()->values()->all();
        $values = $grouped->values()->map(fn ($value) => round($value, 2))->all();

        return [
            'labels' => $labels,
            'values' => $values,
            'totals_by_marketplace' => $totalsByMarketplace,
        ];
    }

    /**
     * @param mixed $rawItems
     * @return array<int, array<string, mixed>>
     */
    private function normalizeItems(mixed $rawItems): array
    {
        if (is_string($rawItems)) {
            $decoded = json_decode($rawItems, true);
            $rawItems = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($rawItems)) {
            return [];
        }

        $items = [];
        foreach ($rawItems as $rawItem) {
            if (is_string($rawItem)) {
                $decodedItem = json_decode($rawItem, true);
                if (is_array($decodedItem)) {
                    $items[] = $decodedItem;
                }
                continue;
            }

            if (is_array($rawItem)) {
                $items[] = $rawItem;
            }
        }

        return $items;
    }

    private function calculateItemVat(mixed $item): float
    {
        if (is_string($item)) {
            $item = json_decode($item, true);
        }

        if (!is_array($item)) {
            return 0.0;
        }

        $qty = (int) ($item['quantity'] ?? $item['qty'] ?? $item['adet'] ?? 0);

        if (isset($item['vat_amount'])) {
            return (float) $item['vat_amount'] * $qty;
        }
$rate = $item['vat_rate'] ?? $item['kdv_orani'] ?? null;
        $price = (float) ($item['price'] ?? $item['unit_price'] ?? $item['fiyat'] ?? 0);

        if (!$rate || $price <= 0 || $qty <= 0) {
            return 0.0;
        }
$rate = (float) $rate;
        $gross = $price * $qty;

        return $gross * ($rate / (100 + $rate));
    }
}

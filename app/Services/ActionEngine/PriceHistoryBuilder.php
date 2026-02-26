<?php

namespace App\Services\ActionEngine;

use App\Domains\Settlements\Models\OrderItem;
use App\Models\MarketplacePriceHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PriceHistoryBuilder
{
    public function buildRange(int $tenantId, int $userId, CarbonImmutable $from, CarbonImmutable $to): int
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('marketplaces', 'marketplaces.id', '=', 'orders.marketplace_id')
            ->where('orders.tenant_id', $tenantId)
            ->where('orders.user_id', $userId)
            ->whereDate('orders.order_date', '>=', $from->toDateString())
            ->whereDate('orders.order_date', '<=', $to->toDateString())
            ->groupByRaw('DATE(orders.order_date), COALESCE(marketplaces.code, "unknown"), order_items.sku')
            ->selectRaw('DATE(orders.order_date) as d')
            ->selectRaw('COALESCE(marketplaces.code, "unknown") as marketplace')
            ->selectRaw('order_items.sku as sku')
            ->selectRaw('SUM(order_items.qty) as units_sold')
            ->selectRaw('SUM(order_items.sale_price * order_items.qty) as revenue')
            ->get();

        foreach ($rows as $row) {
            $units = max(0, (int) ($row->units_sold ?? 0));
            $revenue = (float) ($row->revenue ?? 0);
            $unitPrice = $units > 0 ? $revenue / $units : 0.0;

            MarketplacePriceHistory::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'marketplace' => strtolower((string) $row->marketplace),
                    'sku' => (string) $row->sku,
                    'date' => (string) $row->d,
                ],
                [
                    'user_id' => $userId,
                    'unit_price' => $unitPrice,
                    'units_sold' => $units,
                    'revenue' => $revenue,
                ]
            );
        }

        return $rows->count();
    }
}


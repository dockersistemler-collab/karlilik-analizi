<?php

namespace App\Services\Reports;

use App\Domain\Profitability\DTO\ProfitabilityInput;
use App\Domain\Profitability\ProfitabilityCalculator;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds order profitability report rows.
 */
class OrderProfitabilityReportService
{
    public function __construct(private readonly ProfitabilityCalculator $calculator)
    {
    }

    public function query(User $user, array $filters): Builder
    {
        $query = Order::query()->with('marketplace')->where('user_id', $user->id);

        if (!empty($filters['marketplace_id'])) {
            $query->where('marketplace_id', $filters['marketplace_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        ReportFilters::applyDateRange($query, 'order_date', $filters['date_from'] ?? null, $filters['date_to'] ?? null);

        return $query;
    }

    /**
     * @param Collection<int, Order>|array<int, Order> $orders
     */
    public function rows(Collection|array $orders): Collection
    {
        $collection = $orders instanceof Collection ? $orders : collect($orders);

        return $collection->map(function (Order $order) {
            $items = is_array($order->items)
                ? $order->items
                : (is_string($order->items) ? (json_decode($order->items, true) ?: []) : []);

            $input = new ProfitabilityInput(
                $order->id,
                $order->order_number ?? $order->marketplace_order_id,
                optional($order->order_date)->toDateTimeString() ?? now()->toDateTimeString(),
                (string) ($order->total_amount ?? '0'),
                (string) ($order->commission_amount ?? '0'),
                $items,
                is_array($order->marketplace_data) ? $order->marketplace_data : [],
                $order->user_id
            );

            $breakdown = $this->calculator->calculate($input);

            return [
                'order_number' => $order->order_number ?? $order->marketplace_order_id,
                'marketplace_name' => $order->marketplace?->name,
                'order_date' => $order->order_date,
                'image_url' => $this->resolveImageUrlFromItems($items),
                'sale_price' => $breakdown->sale_price,
                'profit_amount' => $breakdown->profit_amount,
                'profit_margin_percent' => $breakdown->profit_margin_percent,
                'profit_markup_percent' => $breakdown->profit_markup_percent,
                'sales_vat_amount' => $breakdown->sales_vat_amount,
                'withholding_tax_amount' => $breakdown->withholding_tax_amount,
                'breakdown' => json_encode($breakdown, JSON_UNESCAPED_UNICODE),
            ];
        });
    }

    /**
     * @param array<int, mixed> $items
     */
    private function resolveImageUrlFromItems(array $items): ?string
    {
        $firstItem = $items;
        if (array_is_list($items)) {
            $firstItem = $items[0] ?? [];
        }

        if (!is_array($firstItem)) {
            return null;
        }

        return $firstItem['image_url']
            ?? $firstItem['image']
            ?? $firstItem['product_image']
            ?? $firstItem['thumbnail']
            ?? null;
    }
}

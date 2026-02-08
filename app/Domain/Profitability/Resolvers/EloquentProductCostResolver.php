<?php

namespace App\Domain\Profitability\Resolvers;

use App\Domain\Profitability\Contracts\ProductCostResolver;
use App\Models\Product;
use App\Support\Decimal;
use Illuminate\Support\Arr;

/**
 * Resolves product cost using Eloquent Product records.
 */
class EloquentProductCostResolver implements ProductCostResolver
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function resolveProductCostFromOrderItems(array $items, ?int $userId): string
    {
        $items = Arr::isList($items) ? $items : array_values($items);
        $total = '0';

        foreach ($items as $raw) {
            if (!is_array($raw)) {
                continue;
            }

            $codes = $this->extractCodes($raw);
            if (count($codes) === 0) {
                continue;
            }

            $product = $this->findProduct($codes, $userId);
            if (!$product) {
                continue;
            }

            $qty = $this->extractQty($raw);
            $lineCost = Decimal::mul($qty, (string) ($product->cost_price ?? '0'), Decimal::CALC_SCALE);
            $total = Decimal::add($total, $lineCost, Decimal::CALC_SCALE);
        }

        return Decimal::round($total, Decimal::MONEY_SCALE);
    }

    /**
     * @return array<int, string>
     */
    private function extractCodes(array $raw): array
    {
        $codes = [];
        foreach (['sku', 'barcode', 'merchantSku'] as $key) {
            $value = data_get($raw, $key);
            if (is_string($value)) {
                $value = trim($value);
                if ($value !== '') {
                    $codes[] = $value;
                }
            }
        }

        return array_values(array_unique($codes));
    }

    private function findProduct(array $codes, ?int $userId): ?Product
    {
        $query = Product::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $query->where(function ($q) use ($codes) {
            $q->whereIn('sku', $codes)->orWhereIn('barcode', $codes);
        });

        return $query->first();
    }

    private function extractQty(array $raw): string
    {
        $qty = data_get($raw, 'quantity', data_get($raw, 'qty', data_get($raw, 'amount', 1)));
        $qty = is_numeric($qty) ? (float) $qty : 1.0;
        $qty = $qty > 0 ? $qty : 1.0;

        return Decimal::round((string) $qty, Decimal::CALC_SCALE);
    }
}

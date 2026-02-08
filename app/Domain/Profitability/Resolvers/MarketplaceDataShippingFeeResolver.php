<?php

namespace App\Domain\Profitability\Resolvers;

use App\Domain\Profitability\Contracts\ShippingFeeResolver;
use App\Domain\Profitability\DTO\ProfitabilityInput;
use App\Models\Product;
use App\Support\Decimal;
use Illuminate\Support\Arr;

/**
 * Resolves shipping fee from marketplace data or desi mapping.
 */
class MarketplaceDataShippingFeeResolver implements ShippingFeeResolver
{
    public function resolveShippingFee(ProfitabilityInput $input, ?int $userId): string
    {
        $data = $input->marketplace_data;
        $invoiced = data_get($data, 'shipping_fee_invoiced');
        $fee = data_get($data, 'shipping_fee');

        if ($invoiced === true && is_numeric($fee)) {
            return Decimal::round((string) $fee, Decimal::MONEY_SCALE);
        }

        $totalDesi = $this->resolveTotalDesi($input->items, $userId);
        $pricing = config('marketplace.shipping_desi_pricing', []);
        if (!is_array($pricing) || count($pricing) === 0) {
            return Decimal::round('0', Decimal::MONEY_SCALE);
        }

        $limits = array_keys($pricing);
        $limits = array_map('floatval', $limits);
        sort($limits, SORT_NUMERIC);

        foreach ($limits as $limit) {
            if (Decimal::cmp($totalDesi, (string) $limit, Decimal::CALC_SCALE) <= 0) {
                $mapped = $pricing[$limit] ?? 0;
                return Decimal::round((string) $mapped, Decimal::MONEY_SCALE);
            }
        }

        $lastLimit = end($limits);
        $mapped = $pricing[$lastLimit] ?? 0;
        return Decimal::round((string) $mapped, Decimal::MONEY_SCALE);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function resolveTotalDesi(array $items, ?int $userId): string
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
            if (!$product || $product->desi === null) {
                continue;
            }

            $qty = $this->extractQty($raw);
            $lineDesi = Decimal::mul($qty, (string) $product->desi, Decimal::CALC_SCALE);
            $total = Decimal::add($total, $lineDesi, Decimal::CALC_SCALE);
        }

        return Decimal::round($total, Decimal::CALC_SCALE);
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

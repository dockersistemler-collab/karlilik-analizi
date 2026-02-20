<?php

namespace App\Services\CommissionTariffs;

use App\Models\Product;

class ShippingFeeResolver
{
    public function resolve(?Product $product = null): float
    {
        $mode = config('commission_tariffs.fallback_shipping_fee_mode', 'desi_based');
        if ($mode !== 'desi_based') {
            return 0.0;
        }

        $desi = $product?->desi;
        if ($desi === null) {
            return 0.0;
        }

        $table = config('commission_tariffs.desi_fee_table', []);
        if (!is_array($table) || count($table) === 0) {
            return 0.0;
        }

        $limits = array_keys($table);
        $limits = array_map('floatval', $limits);
        sort($limits, SORT_NUMERIC);

        foreach ($limits as $limit) {
            if ($desi <= $limit) {
                return (float) ($table[$limit] ?? 0);
            }
        }

        $last = end($limits);
        return (float) ($table[$last] ?? 0);
    }
}

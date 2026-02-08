<?php

namespace App\Domain\Profitability\Resolvers;

use App\Domain\Profitability\Contracts\VatRateResolver;
use App\Domain\Profitability\DTO\ProfitabilityInput;
use App\Models\Product;
use App\Support\Decimal;
use Illuminate\Support\Arr;

/**
 * Resolves VAT rate from items or product configuration.
 */
class OrderVatRateResolver implements VatRateResolver
{
    public function resolveVatRatePercent(ProfitabilityInput $input): string
    {
        return Decimal::round('20', Decimal::MONEY_SCALE);
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
}

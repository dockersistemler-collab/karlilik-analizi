<?php

namespace App\Services\CommissionTariffs;

use App\Models\CommissionTariffAssignment;
use App\Models\Product;
use App\Models\ProductVariant;

class CommissionTariffAssignmentService
{
    public function upsertAssignments(?string $marketplace, Product $product, array $variantIds, array $ranges, bool $allVariants = false): void
    {
        $payload = $this->normalizeRanges($ranges);

        $variantsQuery = ProductVariant::query()->where('product_id', $product->id);
        if (!$allVariants) {
            $variantsQuery->whereIn('id', array_map('intval', $variantIds));
        }

        $variantIdsToAssign = $variantsQuery->pluck('id')->all();

        foreach ($variantIdsToAssign as $variantId) {
            CommissionTariffAssignment::updateOrCreate([
                'marketplace' => $marketplace,
                'variant_id' => $variantId,
            ], array_merge($payload, [
                'product_id' => $product->id,
            ]));
        }
    }

    private function normalizeRanges(array $ranges): array
    {
        $out = [];
        for ($i = 1; $i <= 4; $i++) {
            $range = $ranges[$i] ?? $ranges[(string) $i] ?? [];
            $out["range{$i}_min"] = $range['min'] ?? null;
            $out["range{$i}_max"] = $range['max'] ?? null;
            $out["c{$i}_percent"] = $range['percent'] ?? null;
        }

        return $out;
    }
}

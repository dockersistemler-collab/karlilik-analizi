<?php

namespace App\Services\ProfitEngine;

use App\Models\MarketplaceFeeRule;
use Illuminate\Support\Collection;

class FeeRuleResolver
{
    public function resolve(
        int $tenantId,
        int $userId,
        string $marketplace,
        ?string $sku = null,
        ?int $categoryId = null,
        ?int $brandId = null
    ): ?MarketplaceFeeRule {
        $rules = MarketplaceFeeRule::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('marketplace', strtolower($marketplace))
            ->where('active', true)
            ->get();

        return $this->pickBestRule($rules, $sku, $categoryId, $brandId);
    }

    private function pickBestRule(
        Collection $rules,
        ?string $sku,
        ?int $categoryId,
        ?int $brandId
    ): ?MarketplaceFeeRule {
        $ranked = $rules
            ->map(function (MarketplaceFeeRule $rule) use ($sku, $categoryId, $brandId): ?array {
                if ($rule->sku !== null && $rule->sku !== '' && $sku !== null && $rule->sku !== $sku) {
                    return null;
                }
                if ($rule->category_id !== null && $categoryId !== null && (int) $rule->category_id !== $categoryId) {
                    return null;
                }
                if ($rule->brand_id !== null && $brandId !== null && (int) $rule->brand_id !== $brandId) {
                    return null;
                }
                if ($rule->sku !== null && $sku === null) {
                    return null;
                }
                if ($rule->category_id !== null && $categoryId === null) {
                    return null;
                }
                if ($rule->brand_id !== null && $brandId === null) {
                    return null;
                }

                $specificity = 0;
                if ($rule->sku !== null && $rule->sku !== '') {
                    $specificity += 300;
                }
                if ($rule->category_id !== null) {
                    $specificity += 200;
                }
                if ($rule->brand_id !== null) {
                    $specificity += 100;
                }

                return [
                    'rule' => $rule,
                    'specificity' => $specificity,
                    'priority' => (int) $rule->priority,
                ];
            })
            ->filter()
            ->sortByDesc(fn (array $row) => ($row['specificity'] * 100000) + $row['priority'])
            ->values();

        return $ranked->isNotEmpty() ? $ranked->first()['rule'] : null;
    }
}


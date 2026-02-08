<?php

namespace App\Domain\Profitability\Contracts;

/**
 * Resolves product cost from order items.
 */
interface ProductCostResolver
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function resolveProductCostFromOrderItems(array $items, ?int $userId): string;
}

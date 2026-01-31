<?php

namespace App\Services\Marketplace\Category;

use App\Models\MarketplaceCredential;

interface MarketplaceCategoryProviderInterface
{
    /**
     * @return array<int, array<string, mixed>> Normalized nodes:
     *  - external_id (string)
     *  - parent_external_id (?string)
     *  - name (string)
     *  - children (array) [optional, same shape]
     *  - is_leaf (bool) [optional]
     *  - raw (array) [optional]
     */
    public function fetchCategoryTree(MarketplaceCredential $credential): array;
}


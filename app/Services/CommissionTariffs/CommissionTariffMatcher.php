<?php

namespace App\Services\CommissionTariffs;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Schema;

class CommissionTariffMatcher
{
    public function match(?string $sku, ?string $barcode, mixed $productId, ?int $tenantUserId = null): array
    {
        $variant = null;

        if ($sku) {
            $variant = ProductVariant::query()
                ->where('sku', trim($sku))
                ->when($tenantUserId, function ($query) use ($tenantUserId) {
                    $query->whereHas('product', function ($sub) use ($tenantUserId) {
                        $sub->where('user_id', $tenantUserId);
                    });
                })
                ->first();
        }

        if (!$variant && $barcode) {
            $variant = ProductVariant::query()
                ->where('barcode', trim($barcode))
                ->when($tenantUserId, function ($query) use ($tenantUserId) {
                    $query->whereHas('product', function ($sub) use ($tenantUserId) {
                        $sub->where('user_id', $tenantUserId);
                    });
                })
                ->first();
        }

        if (!$variant && $productId !== null && Schema::hasColumn('products', 'marketplace_product_id')) {
            $product = Product::query()
                ->where('marketplace_product_id', $productId)
                ->when($tenantUserId, function ($query) use ($tenantUserId) {
                    $query->where('user_id', $tenantUserId);
                })
                ->first();
            if ($product) {
                $variant = $product->variants()->first();
            }
        }

        if ($variant) {
            return [
                'status' => 'matched',
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'error' => null,
            ];
        }

        return [
            'status' => 'unmatched',
            'product_id' => null,
            'variant_id' => null,
            'error' => 'Urun/varyant eslesmesi bulunamadi.',
        ];
    }
}

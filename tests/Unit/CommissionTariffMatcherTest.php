<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CommissionTariffs\CommissionTariffMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommissionTariffMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_variant_by_sku_then_barcode(): void
    {
        $product = Product::create([
            'sku' => 'PROD-1',
            'name' => 'Test Product',
            'price' => 100,
            'cost_price' => 50,
            'stock_quantity' => 10,
            'currency' => 'TRY',
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-123',
            'barcode' => 'BC-123',
            'stock' => 5,
        ]);

        $matcher = new CommissionTariffMatcher();
        $matchSku = $matcher->match('SKU-123', null, null);
        $matchBarcode = $matcher->match(null, 'BC-123', null);

        $this->assertSame('matched', $matchSku['status']);
        $this->assertSame($variant->id, $matchSku['variant_id']);
        $this->assertSame('matched', $matchBarcode['status']);
        $this->assertSame($variant->id, $matchBarcode['variant_id']);
    }
}

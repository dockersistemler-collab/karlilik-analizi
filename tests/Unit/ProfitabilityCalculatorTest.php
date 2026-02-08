<?php

namespace Tests\Unit;

use App\Domain\Profitability\Calculators\CommissionCalculator;
use App\Domain\Profitability\Calculators\PlatformServiceFeeCalculator;
use App\Domain\Profitability\Calculators\ProductCostCalculator;
use App\Domain\Profitability\Calculators\RefundShippingAdjustmentCalculator;
use App\Domain\Profitability\Calculators\SalesVatCalculator;
use App\Domain\Profitability\Calculators\ShippingFeeCalculator;
use App\Domain\Profitability\DTO\ProfitabilityInput;
use App\Domain\Profitability\ProfitabilityCalculator;
use App\Domain\Profitability\Resolvers\EloquentProductCostResolver;
use App\Domain\Profitability\Resolvers\MarketplaceDataRefundShippingResolver;
use App\Domain\Profitability\Resolvers\MarketplaceDataShippingFeeResolver;
use App\Domain\Profitability\Resolvers\OrderVatRateResolver;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfitabilityCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private function calculator(): ProfitabilityCalculator
    {
        return new ProfitabilityCalculator([
            new ProductCostCalculator(new EloquentProductCostResolver()),
            new CommissionCalculator(),
            new ShippingFeeCalculator(new MarketplaceDataShippingFeeResolver()),
            new PlatformServiceFeeCalculator(),
            new RefundShippingAdjustmentCalculator(new MarketplaceDataRefundShippingResolver()),
            new SalesVatCalculator(new OrderVatRateResolver()),
        ]);
    }

    private function createProduct(User $user, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $user->id,
            'sku' => 'SKU-1',
            'barcode' => null,
            'name' => 'Test Product',
            'price' => 100,
            'cost_price' => 50,
            'stock_quantity' => 10,
            'currency' => 'TRY',
            'desi' => 2,
            'vat_rate' => 20,
            'is_active' => true,
        ], $overrides));
    }

    public function test_shipping_invoiced_fee_is_used(): void
    {
        config(['marketplace.platform_service_fee' => 5]);
        $user = User::factory()->create();
        $this->createProduct($user, ['sku' => 'SKU-INV', 'cost_price' => 50, 'desi' => 2, 'vat_rate' => 20]);

        $input = new ProfitabilityInput(
            null,
            'ORD-1',
            now()->toDateTimeString(),
            '200',
            '10',
            [['sku' => 'SKU-INV', 'quantity' => 1, 'price' => 200, 'vat_rate' => 20]],
            ['shipping_fee_invoiced' => true, 'shipping_fee' => 30],
            $user->id
        );

        $breakdown = $this->calculator()->calculate($input);

        $this->assertSame('30.00', $breakdown->shipping_fee);
        $this->assertSame('105.00', $breakdown->profit_amount);
        $this->assertSame('52.50', $breakdown->profit_margin_percent);
        $this->assertSame('110.53', $breakdown->profit_markup_percent);
    }

    public function test_shipping_fee_uses_desi_pricing_when_not_invoiced(): void
    {
        config(['marketplace.shipping_desi_pricing' => [0 => 0, 1 => 39.90, 2 => 49.90, 5 => 69.90]]);
        $user = User::factory()->create();
        $this->createProduct($user, ['sku' => 'SKU-DESI', 'desi' => 2]);

        $input = new ProfitabilityInput(
            null,
            'ORD-2',
            now()->toDateTimeString(),
            '100',
            '0',
            [['sku' => 'SKU-DESI', 'qty' => 1, 'price' => 100]],
            ['shipping_fee_invoiced' => false],
            $user->id
        );

        $breakdown = $this->calculator()->calculate($input);

        $this->assertSame('49.90', $breakdown->shipping_fee);
    }

    public function test_refund_shipping_adjustment_is_negative(): void
    {
        $user = User::factory()->create();
        $this->createProduct($user, ['sku' => 'SKU-REF', 'cost_price' => 50]);

        $input = new ProfitabilityInput(
            null,
            'ORD-3',
            now()->toDateTimeString(),
            '200',
            '10',
            [['sku' => 'SKU-REF', 'amount' => 1, 'price' => 200]],
            ['refund_out_shipping_fee' => 40, 'refund_return_shipping_fee' => 40],
            $user->id
        );

        $breakdown = $this->calculator()->calculate($input);

        $this->assertSame('-80.00', $breakdown->refunds_shipping_adjustment);
        $this->assertSame('60.00', $breakdown->profit_amount);
    }

    public function test_sales_vat_amount_is_calculated_from_included_vat(): void
    {
        $user = User::factory()->create();
        $this->createProduct($user, ['sku' => 'SKU-VAT', 'cost_price' => 0]);

        $input = new ProfitabilityInput(
            null,
            'ORD-4',
            now()->toDateTimeString(),
            '120',
            '0',
            [['sku' => 'SKU-VAT', 'quantity' => 1, 'price' => 120, 'vat_rate' => 20]],
            [],
            $user->id
        );

        $breakdown = $this->calculator()->calculate($input);

        $this->assertSame('20.00', $breakdown->sales_vat_amount);
        $this->assertSame('20.00', $breakdown->vat_rate_percent);
    }

    public function test_margin_and_markup_percentages(): void
    {
        $user = User::factory()->create();
        $this->createProduct($user, ['sku' => 'SKU-PCT', 'cost_price' => 80, 'desi' => 0]);

        $input = new ProfitabilityInput(
            null,
            'ORD-5',
            now()->toDateTimeString(),
            '100',
            '5',
            [['sku' => 'SKU-PCT', 'quantity' => 1, 'price' => 100]],
            ['shipping_fee_invoiced' => true, 'shipping_fee' => 5],
            $user->id
        );

        $breakdown = $this->calculator()->calculate($input);

        $this->assertSame('10.00', $breakdown->profit_amount);
        $this->assertSame('10.00', $breakdown->profit_margin_percent);
        $this->assertSame('11.11', $breakdown->profit_markup_percent);
    }
}

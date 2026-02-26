<?php

namespace Tests\Unit\BuyBox;

use App\Models\MarketplaceOfferSnapshot;
use App\Services\BuyBox\BuyBoxScoreCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuyBoxScoreCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_gap_increase_reduces_score(): void
    {
        $date = Carbon::parse('2026-02-26');

        $atBestPrice = MarketplaceOfferSnapshot::query()->create([
            'tenant_id' => 1,
            'marketplace' => 'trendyol',
            'date' => $date->toDateString(),
            'sku' => 'SKU-A',
            'our_price' => 100,
            'competitor_best_price' => 100,
            'store_score' => 90,
            'shipping_speed_score' => 95,
            'stock_available' => 10,
            'promo_flag' => false,
            'source' => 'manual',
        ]);

        $overpriced = MarketplaceOfferSnapshot::query()->create([
            'tenant_id' => 1,
            'marketplace' => 'trendyol',
            'date' => $date->toDateString(),
            'sku' => 'SKU-B',
            'our_price' => 106,
            'competitor_best_price' => 100,
            'store_score' => 90,
            'shipping_speed_score' => 95,
            'stock_available' => 10,
            'promo_flag' => false,
            'source' => 'manual',
        ]);

        $calculator = app(BuyBoxScoreCalculator::class);
        $scoreA = $calculator->calculate($atBestPrice);
        $scoreB = $calculator->calculate($overpriced);

        $this->assertGreaterThan($scoreB['buybox_score'], $scoreA['buybox_score']);
    }

    public function test_store_score_downtrend_applies_penalty(): void
    {
        $date = Carbon::parse('2026-02-26');

        for ($i = 29; $i >= 7; $i--) {
            MarketplaceOfferSnapshot::query()->create([
                'tenant_id' => 1,
                'marketplace' => 'trendyol',
                'date' => $date->copy()->subDays($i)->toDateString(),
                'sku' => 'SKU-TREND',
                'store_score' => 90,
                'our_price' => 100,
                'competitor_best_price' => 100,
                'shipping_speed_score' => 90,
                'stock_available' => 8,
                'promo_flag' => false,
                'source' => 'manual',
            ]);
        }
        for ($i = 6; $i >= 0; $i--) {
            MarketplaceOfferSnapshot::query()->create([
                'tenant_id' => 1,
                'marketplace' => 'trendyol',
                'date' => $date->copy()->subDays($i)->toDateString(),
                'sku' => 'SKU-TREND',
                'store_score' => 50,
                'our_price' => 100,
                'competitor_best_price' => 100,
                'shipping_speed_score' => 90,
                'stock_available' => 8,
                'promo_flag' => false,
                'source' => 'manual',
            ]);
        }

        $reference = MarketplaceOfferSnapshot::query()->create([
            'tenant_id' => 1,
            'marketplace' => 'trendyol',
            'date' => $date->toDateString(),
            'sku' => 'SKU-REF',
            'store_score' => 50,
            'our_price' => 100,
            'competitor_best_price' => 100,
            'shipping_speed_score' => 90,
            'stock_available' => 8,
            'promo_flag' => false,
            'source' => 'manual',
        ]);

        $trendSnapshot = MarketplaceOfferSnapshot::query()
            ->where('sku', 'SKU-TREND')
            ->whereDate('date', $date->toDateString())
            ->firstOrFail();

        $calculator = app(BuyBoxScoreCalculator::class);
        $trendScore = $calculator->calculate($trendSnapshot);
        $referenceScore = $calculator->calculate($reference);

        $this->assertTrue($trendScore['buybox_score'] < $referenceScore['buybox_score']);
    }

    public function test_drivers_sorted_by_penalty_contribution(): void
    {
        $snapshot = MarketplaceOfferSnapshot::query()->create([
            'tenant_id' => 1,
            'marketplace' => 'trendyol',
            'date' => '2026-02-26',
            'sku' => 'SKU-DRIVER',
            'our_price' => 110,
            'competitor_best_price' => 100,
            'store_score' => 90,
            'shipping_speed_score' => 90,
            'stock_available' => 0,
            'promo_flag' => false,
            'source' => 'manual',
        ]);

        $calculator = app(BuyBoxScoreCalculator::class);
        $result = $calculator->calculate($snapshot);

        $this->assertCount(3, $result['drivers']);
        $this->assertSame('price_competitiveness', $result['drivers'][0]['metric']);
        $this->assertSame('stock', $result['drivers'][1]['metric']);
    }
}

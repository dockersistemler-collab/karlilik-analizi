<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockAlert;
use App\Services\Reports\OrderProfitabilityReportService;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NeKazanirimController extends Controller
{
    public function index(Request $request, OrderProfitabilityReportService $profitabilityReportService): View
    {
        abort_unless(module_enabled('ne_kazanirim'), 404);

        $tenantId = (int) $request->user()->id;
        $orderRows = Order::query()
            ->with('marketplace:id,name')
            ->where('user_id', $tenantId)
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get([
                'id',
                'marketplace_id',
                'marketplace_order_id',
                'order_number',
                'order_date',
                'total_amount',
                'commission_amount',
                'net_amount',
                'currency',
                'items',
                'marketplace_data',
                'user_id',
            ]);

        $profitabilityRows = $profitabilityReportService->rows($orderRows)->values();
        $orderRows->values()->each(function (Order $order, int $index) use ($profitabilityRows): void {
            $order->setAttribute('_profitability_report', $profitabilityRows->get($index));
        });

        $stockProducts = Product::query()
            ->with(['marketplaceProducts.marketplace:id,name'])
            ->where('user_id', $tenantId)
            ->orderBy('name')
            ->limit(100)
            ->get([
                'id',
                'name',
                'sku',
                'barcode',
                'brand',
                'cost_price',
                'price',
                'currency',
                'image_url',
                'images',
                'stock_quantity',
                'critical_stock_level',
            ]);

        $activeAlertProductIds = StockAlert::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('product_id', $stockProducts->pluck('id'))
            ->pluck('product_id')
            ->flip()
            ->all();

        $settings = app(SettingsRepository::class);
        $rawServiceFeeBrackets = $settings->get('ne_kazanirim', 'service_fee_brackets', null);
        $serviceFeeBrackets = null;
        if (is_string($rawServiceFeeBrackets)) {
            $serviceFeeBrackets = json_decode($rawServiceFeeBrackets, true);
        } elseif (is_array($rawServiceFeeBrackets)) {
            $serviceFeeBrackets = $rawServiceFeeBrackets;
        }
        if (!is_array($serviceFeeBrackets) || $serviceFeeBrackets === []) {
            $serviceFeeBrackets = config('ne_kazanirim.service_fee_brackets', []);
        }
        $withholdingRatePercent = (float) $settings->get(
            'ne_kazanirim',
            'withholding_rate_percent',
            config('ne_kazanirim.withholding_rate_percent', 1.0)
        );
        $extraServiceFeeAmount = (float) $settings->get(
            'ne_kazanirim',
            'extra_service_fee_amount',
            config('ne_kazanirim.extra_service_fee_amount', 0.0)
        );
        $platformServiceAmountTrendyol = (float) $settings->get(
            'ne_kazanirim',
            'platform_service_amount_trendyol',
            $settings->get(
                'ne_kazanirim',
                'platform_service_amount',
                config('ne_kazanirim.platform_service_amount_trendyol', 0.0)
            )
        );
        $platformServiceAmountHepsiburada = (float) $settings->get(
            'ne_kazanirim',
            'platform_service_amount_hepsiburada',
            config('ne_kazanirim.platform_service_amount_hepsiburada', 0.0)
        );
        $platformServiceAmountN11 = (float) $settings->get(
            'ne_kazanirim',
            'platform_service_amount_n11',
            config('ne_kazanirim.platform_service_amount_n11', 0.0)
        );
        $platformServiceAmountAmazon = (float) $settings->get(
            'ne_kazanirim',
            'platform_service_amount_amazon',
            config('ne_kazanirim.platform_service_amount_amazon', 0.0)
        );
        $platformServiceAmountCiceksepeti = (float) $settings->get(
            'ne_kazanirim',
            'platform_service_amount_ciceksepeti',
            config('ne_kazanirim.platform_service_amount_ciceksepeti', 0.0)
        );

        return view('ne-kazanirim.index', [
            'orderRows' => $orderRows,
            'stockProducts' => $stockProducts,
            'activeAlertProductIds' => $activeAlertProductIds,
            'neKazanirimSettings' => [
                'service_fee_brackets' => $serviceFeeBrackets,
                'withholding_rate_percent' => $withholdingRatePercent,
                'extra_service_fee_amount' => $extraServiceFeeAmount,
                'platform_service_amount_trendyol' => $platformServiceAmountTrendyol,
                'platform_service_amount_hepsiburada' => $platformServiceAmountHepsiburada,
                'platform_service_amount_n11' => $platformServiceAmountN11,
                'platform_service_amount_amazon' => $platformServiceAmountAmazon,
                'platform_service_amount_ciceksepeti' => $platformServiceAmountCiceksepeti,
            ],
        ]);
    }
}

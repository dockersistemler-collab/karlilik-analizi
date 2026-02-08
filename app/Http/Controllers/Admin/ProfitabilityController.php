<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoreOrderItem;
use App\Models\Marketplace;
use App\Support\SupportUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfitabilityController extends Controller
{
    public function index(Request $request): View
    {
        $user = SupportUser::currentUser();
        $tenantId = $user?->id;

        $marketplaces = Marketplace::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['code', 'name']);

        $filters = $this->resolveFilters($request);

        $query = CoreOrderItem::query()->where('tenant_id', $tenantId);

        if (!empty($filters['marketplaces'])) {
            $query->whereIn('marketplace', $filters['marketplaces']);
        }

        if ($filters['sku']) {
            $query->where('sku', 'like', '%'.$filters['sku'].'%');
        }

        if ($filters['date_from']) {
            $query->whereDate('order_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate('order_date', '<=', $filters['date_to']);
        }

        $summary = (clone $query)
            ->selectRaw('SUM(gross_sales) as gross_sales')
            ->addSelect(DB::raw('SUM(discounts) as discounts'))
            ->addSelect(DB::raw('SUM(refunds) as refunds'))
            ->addSelect(DB::raw('SUM(net_sales) as net_sales'))
            ->addSelect(DB::raw('SUM(fees_total) as fees_total'))
            ->addSelect(DB::raw('SUM(cogs_total) as cogs_total'))
            ->addSelect(DB::raw('SUM(gross_profit) as gross_profit'))
            ->addSelect(DB::raw('SUM(contribution_margin) as contribution_margin'))
            ->first();

        $grossSales = (float) ($summary->gross_sales ?? 0);
        $refunds = (float) ($summary->refunds ?? 0);
        $refundRate = $grossSales > 0 ? ($refunds / $grossSales) * 100 : 0;

        $kpis = [
            'net_sales' => (float) ($summary->net_sales ?? 0),
            'fees_total' => (float) ($summary->fees_total ?? 0),
            'cogs_total' => (float) ($summary->cogs_total ?? 0),
            'gross_profit' => (float) ($summary->gross_profit ?? 0),
            'contribution_margin' => (float) ($summary->contribution_margin ?? 0),
            'refund_rate' => $refundRate,
        ];

        $trendRows = (clone $query)
            ->selectRaw('DATE(order_date) as date')
            ->addSelect(DB::raw('SUM(net_sales) as net_sales'))
            ->addSelect(DB::raw('SUM(contribution_margin) as contribution_margin'))
            ->groupBy(DB::raw('DATE(order_date)'))
            ->orderBy(DB::raw('DATE(order_date)'))
            ->get();

        $trend = [
            'labels' => $trendRows->map(fn ($row) => (string) $row->date)->all(),
            'net_sales' => $trendRows->map(fn ($row) => (float) $row->net_sales)->all(),
            'contribution_margin' => $trendRows->map(fn ($row) => (float) $row->contribution_margin)->all(),
        ];

        $marketplaceRows = (clone $query)
            ->select('marketplace')
            ->addSelect(DB::raw('SUM(net_sales) as net_sales'))
            ->addSelect(DB::raw('SUM(contribution_margin) as contribution_margin'))
            ->groupBy('marketplace')
            ->orderByDesc(DB::raw('SUM(net_sales)'))
            ->get();

        $marketplaceMap = $marketplaces->pluck('name', 'code');

        $byMarketplace = [
            'labels' => $marketplaceRows->map(function ($row) use ($marketplaceMap) {
                return $marketplaceMap[$row->marketplace] ?? strtoupper((string) $row->marketplace);
            })->all(),
            'net_sales' => $marketplaceRows->map(fn ($row) => (float) $row->net_sales)->all(),
            'contribution_margin' => $marketplaceRows->map(fn ($row) => (float) $row->contribution_margin)->all(),
        ];

        $skuRows = (clone $query)
            ->select('sku')
            ->addSelect(DB::raw('SUM(net_sales) as net_sales'))
            ->addSelect(DB::raw('SUM(contribution_margin) as contribution_margin'))
            ->groupBy('sku')
            ->orderByDesc(DB::raw('SUM(contribution_margin)'))
            ->limit(10)
            ->get();

        $topBottom = [
            'top' => $skuRows->take(5)->values(),
            'bottom' => $skuRows->sortBy('contribution_margin')->take(5)->values(),
        ];

        $quickRanges = [
            'today' => 'Bugün',
            'this_week' => 'Bu Hafta',
            'this_month' => 'Bu Ay',
            'last_month' => 'Geçen Ay',
            'last_3_months' => 'Son 3 Ay',
            'last_1_year' => 'Son 1 Yıl',
            'last_7_days' => 'Son 7 Gün',
            'last_30_days' => 'Son 30 Gün',
        ];

        return view('admin.profitability.index', [
            'marketplaces' => $marketplaces,
            'filters' => $filters,
            'kpis' => $kpis,
            'trend' => $trend,
            'byMarketplace' => $byMarketplace,
            'topBottom' => $topBottom,
            'quickRanges' => $quickRanges,
        ]);
    }

    private function resolveFilters(Request $request): array
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $quickRange = $request->input('quick_range');

        if ($quickRange) {
            [$dateFrom, $dateTo] = $this->resolveQuickRange($quickRange);
        }

        if (!$dateFrom && !$dateTo) {
            $dateFrom = Carbon::today()->subDays(29)->toDateString();
            $dateTo = Carbon::today()->toDateString();
        }

        $marketplaces = $request->input('marketplaces');
        if (is_string($marketplaces)) {
            $marketplaces = array_filter(explode(',', $marketplaces));
        }

        return [
            'marketplaces' => is_array($marketplaces) ? array_values(array_filter($marketplaces)) : [],
            'date_from' => $dateFrom ?: null,
            'date_to' => $dateTo ?: null,
            'quick_range' => $quickRange ?: null,
            'sku' => $request->input('sku') ?: null,
        ];
    }

    private function resolveQuickRange(string $quickRange): array
    {
        $today = Carbon::today();

        return match ($quickRange) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'this_week' => [$today->copy()->startOfWeek()->toDateString(), $today->copy()->endOfWeek()->toDateString()],
            'this_month' => [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $today->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'last_3_months' => [
                $today->copy()->subMonthsNoOverflow(2)->startOfMonth()->toDateString(),
                $today->copy()->toDateString(),
            ],
            'last_1_year' => [
                $today->copy()->subYearNoOverflow()->toDateString(),
                $today->copy()->toDateString(),
            ],
            'last_7_days' => [$today->copy()->subDays(6)->toDateString(), $today->toDateString()],
            'last_30_days' => [$today->copy()->subDays(29)->toDateString(), $today->toDateString()],
            default => [null, null],
        };
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Marketplace;
use App\Services\Reports\BrandSalesReportService;
use App\Services\Reports\CategorySalesReportService;
use App\Services\Reports\CommissionReportService;
use App\Services\Reports\OrderProfitabilityReportService;
use App\Services\Reports\OrdersRevenueReportService;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\SoldProductsReportService;
use App\Services\Reports\StockValueReportService;
use App\Services\Reports\TopProductsReportService;
use App\Services\Reports\VatReportService;
use App\Services\SystemSettings\SettingsRepository;
use App\Support\SupportUser;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, OrdersRevenueReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request, true);
        $marketplaces = Marketplace::where('is_active', true)->orderBy('name')->get();
        $report = $service->get(SupportUser::currentUser(), $filters);

        return view('admin.reports', [
            'filters' => $filters,
            'marketplaces' => $marketplaces,
            'quickRanges' => $this->quickRanges(),
            'report' => $report,
            'reportExportsEnabled' => $this->reportExportsEnabled(),
        ]);
    }

    public function topProducts(Request $request, TopProductsReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request);
        $marketplaces = Marketplace::where('is_active', true)->orderBy('name')->get();
        $rows = $service->get(SupportUser::currentUser(), $filters, 100);

        return view('admin.reports.top-products', [
            'filters' => $filters,
            'marketplaces' => $marketplaces,
            'quickRanges' => $this->quickRanges(),
            'rows' => $rows,
            'reportExportsEnabled' => $this->reportExportsEnabled(),
        ]);
    }

    public function topProductsExport(Request $request, TopProductsReportService $service): Response
    {
        $filters = ReportFilters::fromRequest($request);
        $rows = $service->get(SupportUser::currentUser(), $filters, 1000);

        $headers = ['stok_kodu', 'urun_adi', 'satis_adedi', 'toplam_tutar'];
        $data = $rows->map(function ($row) {
            return [
                $row['stock_code'],
                $row['name'],
                $row['quantity'],
                number_format((float) $row['total'], 2, '.', ''),
            ];
        })->all();

        return $this->streamCsv('cok-satan-urunler.csv', $headers, $data);
    }

    public function soldProducts(Request $request, SoldProductsReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request);
        $marketplaces = Marketplace::where('is_active', true)->orderBy('name')->get();
        $rows = $service->get(SupportUser::currentUser(), $filters);

        return view('admin.reports.sold-products', [
            'filters' => $filters,
            'marketplaces' => $marketplaces,
            'quickRanges' => $this->quickRanges(),
            'rows' => $rows,
        ]);
    }

    public function soldProductsPrint(Request $request, SoldProductsReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request);
        $rows = $service->get(SupportUser::currentUser(), $filters);

        return view('admin.reports.sold-products-print', [
            'filters' => $filters,
            'rows' => $rows,
        ]);
    }

    public function categorySales(Request $request, CategorySalesReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request, true);
        $marketplaces = Marketplace::where('is_active', true)->orderBy('name')->get();
        $rows = $service->get(SupportUser::currentUser(), $filters);
        $chartType = $request->input('chart_type', 'bar');

        return view('admin.reports.category-sales', [
            'filters' => $filters,
            'marketplaces' => $marketplaces,
            'quickRanges' => $this->quickRanges(),
            'rows' => $rows,
            'chartType' => $chartType,
        ]);
    }

    public function brandSales(Request $request, BrandSalesReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request, true);
        $marketplaces = Marketplace::where('is_active', true)->orderBy('name')->get();
        $report = $service->get(SupportUser::currentUser(), $filters, $marketplaces);
        $chartType = $request->input('chart_type', 'bar');

        return view('admin.reports.brand-sales', [
            'filters' => $filters,
            'marketplaces' => $marketplaces,
            'quickRanges' => $this->quickRanges(),
            'report' => $report,
            'chartType' => $chartType,
        ]);
    }

    public function vat(Request $request, VatReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request, true);
        $marketplaces = Marketplace::where('is_active', true)->orderBy('name')->get();
        $chart = $service->get(SupportUser::currentUser(), $filters);
        $vatColorsRaw = AppSetting::getValue('vat_report_marketplace_colors', '{}');
        $vatColors = json_decode($vatColorsRaw, true);
        if (!is_array($vatColors)) {
            $vatColors = [];
        }
$selectedMarketplaceId = $filters['marketplace_id'] ?? null;
        $marketplaceList = $selectedMarketplaceId
            ? $marketplaces->where('id', $selectedMarketplaceId)
            : $marketplaces;
        $cards = $marketplaceList->map(function ($marketplace) use ($chart, $vatColors) {
            $total = $chart['totals_by_marketplace'][$marketplace->id] ?? 0.0;
            $color = $vatColors[$marketplace->id] ?? '#ff4439';

            return [
                'id' => $marketplace->id,
                'name' => $marketplace->name,
                'total' => $total,
                'color' => $color,
            ];
        })->values();

        return view('admin.reports.vat', [
            'filters' => $filters,
            'marketplaces' => $marketplaces,
            'quickRanges' => $this->quickRanges(),
            'chart' => $chart,
            'cards' => $cards,
        ]);
    }

    public function commission(Request $request, CommissionReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request, true);
        $marketplaces = Marketplace::where('is_active', true)->orderBy('name')->get();
        $report = $service->get(SupportUser::currentUser(), $filters);

        return view('admin.reports.commission', [
            'filters' => $filters,
            'marketplaces' => $marketplaces,
            'quickRanges' => $this->quickRanges(),
            'report' => $report,
        ]);
    }

    public function stockValue(Request $request, StockValueReportService $service): View
    {
        $report = $service->get(SupportUser::currentUser());

        return view('admin.reports.stock-value', [
            'summary' => $report['summary'],
            'rows' => $report['table'],
        ]);
    }

    public function orderProfitability(Request $request, OrderProfitabilityReportService $service): View
    {
        $filters = ReportFilters::fromRequest($request, true);
        $filters['status'] = $request->input('status');
        $marketplaces = Marketplace::where('is_active', true)->orderBy('name')->get();

        $user = SupportUser::currentUser();
        $orders = $service->query($user, $filters)
            ->orderByDesc('order_date')
            ->get();

        $rows = $service->rows($orders);
        $settings = app(SettingsRepository::class);
        $withholdingRatePercent = (float) $settings->get(
            'ne_kazanirim',
            'withholding_rate_percent',
            config('ne_kazanirim.withholding_rate_percent', 1.0)
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

        return view('admin.reports.order-profitability', [
            'filters' => $filters,
            'marketplaces' => $marketplaces,
            'quickRanges' => $this->quickRanges(),
            'rows' => $rows,
            'neKazanirimSettings' => [
                'withholding_rate_percent' => $withholdingRatePercent,
                'platform_service_amount_trendyol' => $platformServiceAmountTrendyol,
                'platform_service_amount_hepsiburada' => $platformServiceAmountHepsiburada,
                'platform_service_amount_n11' => $platformServiceAmountN11,
                'platform_service_amount_amazon' => $platformServiceAmountAmazon,
                'platform_service_amount_ciceksepeti' => $platformServiceAmountCiceksepeti,
            ],
        ]);
    }

    public function ordersRevenueExport(Request $request, OrdersRevenueReportService $service): Response
    {
        $filters = ReportFilters::fromRequest($request, true);
        $report = $service->get(SupportUser::currentUser(), $filters);

        $headers = ['tarih'];
        foreach ($report['marketplaces'] as $marketplace) {
            $headers[] = $marketplace->name;
        }
$headers[] = 'toplam';

        $data = [];
        foreach ($report['table'] as $row) {
            $line = [$row['period']];
            foreach ($report['marketplaces'] as $marketplace) {
                $line[] = number_format((float) $row['mp_' . $marketplace->id], 2, '.', '');
            }
$line[] = number_format((float) $row['total'], 2, '.', '');
            $data[] = $line;
        }

        return $this->streamCsv('siparis-ciro.csv', $headers, $data);
    }

    public function ordersRevenueInvoicedExport(Request $request): Response
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Bu rapor, fatura modülü tamamlandığında bağlanacaktır.']);
            fclose($handle);
        }, 'faturali-siparisler.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function quickRanges(): array
    {
        return [
            'today' => 'Bugün',
            'this_week' => 'Bu hafta',
            'this_month' => 'Bu ay',
            'last_month' => 'Geçen ay',
            'last_3_months' => 'Son 3 ay',
            'last_1_year' => 'Son 1 yıl',
        ];
    }

    private function streamCsv(string $filename, array $headers, array $rows): Response
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'wb');
            fprintf($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function reportExportsEnabled(): bool
    {
        return (bool) AppSetting::getValue('reports_exports_enabled', true);
    }
}


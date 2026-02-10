<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Product;
use App\Models\Order;
use App\Models\MarketplaceProduct;
use App\Models\BillingEvent;
use App\Models\BillingSubscription;
use App\Services\Reports\SoldProductsReportService;
use App\Support\SupportUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = SupportUser::currentUser();
        $isPortal = $request->routeIs('portal.dashboard');

        $productQuery = Product::query();
        $orderQuery = Order::query();
        $marketplaceProductQuery = MarketplaceProduct::query();

        if ($user && !$user->isSuperAdmin()) {
            $productQuery->where('user_id', $user->id);
            $orderQuery->where('user_id', $user->id);
            $marketplaceProductQuery->whereHas('product', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }
$stats = [
            'total_products' => $productQuery->count(),
            'active_products' => (clone $productQuery)->where('is_active', true)->count(),
            'total_marketplaces' => Marketplace::where('is_active', true)->count(),
            'total_orders' => $orderQuery->count(),
            'pending_orders' => (clone $orderQuery)->where('status', 'pending')->count(),
            'total_marketplace_products' => $marketplaceProductQuery->count(),
        ];

        $recent_orders = $orderQuery->with('marketplace')
            ->latest()
            ->take(10)
            ->get();

        $marketplaces = Marketplace::withCount(['products', 'orders'])
            ->where('is_active', true)
            ->get();

        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $range = $request->input('range', 'month');
        $rangeStart = $this->resolveRangeStart($range);
        $rangeEnd = Carbon::now();

        $todayOrders = (clone $orderQuery)
            ->whereDate('order_date', $today->toDateString())
            ->get(['items', 'total_amount']);
        $monthOrders = (clone $orderQuery)
            ->whereBetween('order_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['items', 'total_amount']);

        $todayQty = $this->sumSoldQuantity($todayOrders);
        $monthQty = $this->sumSoldQuantity($monthOrders);
        $todayAmount = (float) $todayOrders->sum('total_amount');
        $monthAmount = (float) $monthOrders->sum('total_amount');

        $kpis = [
            [
                'title' => 'BUGÜN SATIŞ SAYISI',
                'value' => number_format($todayQty, 0, ',', '.'),
                'unit' => 'adet',
                'description' => 'Bugün toplam satılan ürün sayısı',
                'icon' => 'fa-bolt',
            ],
            [
                'title' => 'BUGÜN TOPLAM SATIŞ',
                'value' => $todayAmount > 0
                    ? number_format($todayAmount, 2, ',', '.')
                    : number_format($todayQty, 0, ',', '.'),
                'unit' => $todayAmount > 0 ? '₺' : 'adet',
                'description' => 'Bugün elde edilen toplam satış',
                'icon' => 'fa-coins',
            ],
            [
                'title' => 'BU AYKİ SATIŞ SAYISI',
                'value' => number_format($monthQty, 0, ',', '.'),
                'unit' => 'adet',
                'description' => 'Bu ay toplam satılan ürün sayısı',
                'icon' => 'fa-calendar-check',
            ],
            [
                'title' => 'BU AYKİ TOPLAM SATIŞ',
                'value' => $monthAmount > 0
                    ? number_format($monthAmount, 2, ',', '.')
                    : number_format($monthQty, 0, ',', '.'),
                'unit' => $monthAmount > 0 ? '₺' : 'adet',
                'description' => 'Bu ay elde edilen toplam satış',
                'icon' => 'fa-chart-line',
            ],
        ];

        $soldProductsService = app(SoldProductsReportService::class);
        $topProducts = $soldProductsService->get($user, [
            'date_from' => $rangeStart->toDateString(),
            'date_to' => $rangeEnd->toDateString(),
        ])->take(10);

        $rangeOrders = (clone $orderQuery)
            ->whereNotNull('shipping_address')
            ->whereBetween('order_date', [$rangeStart->toDateTimeString(), $rangeEnd->toDateTimeString()])
            ->get(['shipping_address', 'items']);

        $mapData = $this->buildCityOrderCounts($rangeOrders);

        $netProfitChart = $this->buildAmountChartForRange($orderQuery, (string) $request->input('range', 'week'));

        $portalBilling = null;
        if ($isPortal && $user) {
            $subscription = BillingSubscription::query()
                ->where('tenant_id', $user->id)
                ->orderByDesc('created_at')
                ->first();

            $status = strtoupper((string) ($subscription?->status ?? ''));
            $isPastDue = in_array($status, ['PAST_DUE', 'UNPAID', 'FAILURE', 'FAILED'], true);
            $isCanceled = in_array($status, ['CANCELED', 'CANCELLED'], true);

            $badge = 'unknown';
            if ($status === 'ACTIVE') {
                $badge = 'active';
            } elseif ($isPastDue) {
                $badge = 'past_due';
            } elseif ($isCanceled) {
                $badge = 'canceled';
            }
$nextRetryAt = $subscription?->next_payment_at ?? $subscription?->grace_until;
$failureEvent = BillingEvent::query()
                ->where('tenant_id', $user->id)
                ->whereIn('type', ['iyzico.webhook.failed', 'dunning.retry_failed', 'dunning.retry_attempt'])
                ->orderByDesc('created_at')
                ->first();

            $lastFailureMessage = $this->extractFailureMessage($failureEvent?->payload ?? null);

            $portalBilling = [
                'subscription' => $subscription,
                'badge' => $badge,
                'status' => $status,
                'is_past_due' => $isPastDue,
                'next_retry_at' => $nextRetryAt,
                'last_failure_message' => $lastFailureMessage,
            ];
        }
        $fallbackMarketplaces = $marketplaces->map(fn ($marketplace) => [
            'name' => $marketplace->name,
            'code' => $marketplace->code,
            'total' => 0,
        ])->values();

        $payload = compact(
            'stats',
            'recent_orders',
            'marketplaces',
            'kpis',
            'topProducts',
            'mapData',
            'netProfitChart',
            'fallbackMarketplaces',
            'range',
            'portalBilling',
            'isPortal'
        );

        if ($isPortal) {
            return view('customer.dashboard', $payload);
        }

        return view('admin.dashboard', $payload);
    }

    private function sumSoldQuantity($orders): int
    {
        $total = 0;
        foreach ($orders as $order) {
            $items = is_array($order->items) ? $order->items : [];
            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? $item['qty'] ?? $item['adet'] ?? 0);
                $total += $qty;
            }
        }
        return $total;
    }

    private function resolveRangeStart(string $range): Carbon
    {
        return match ($range) {
            'day' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
    }

    private function buildCityOrderCounts($orders): array
    {
        $provinceMap = $this->provinceMap();
        $counts = [];

        foreach ($orders as $order) {
            $city = $this->extractCity($order->shipping_address);
            if (!$city) {
                continue;
            }
$normalized = $this->normalizeCity($city);
            $province = $provinceMap[$normalized] ?? null;
            if (!$province) {
                continue;
            }
$qty = 0;
            $items = is_array($order->items) ? $order->items : [];
            foreach ($items as $item) {
                $qty += (int) ($item['quantity'] ?? $item['qty'] ?? $item['adet'] ?? 0);
            }
            if ($qty <= 0) {
                $qty = 1;
            }
$counts[$normalized] = ($counts[$normalized] ?? 0) + $qty;
        }

        return $counts;
    }

    public function mapData(Request $request): JsonResponse
    {
        $user = SupportUser::currentUser();
        $range = (string) $request->input('range', 'week');

        $orderQuery = Order::query();
        if ($user && !$user->isSuperAdmin()) {
            $orderQuery->where('user_id', $user->id);
        }
        $orderQuery = $this->applyRealOrderFilters($orderQuery);

        [$start, $end] = $this->resolveMapRange($range);

        $rangeOrders = (clone $orderQuery)
            ->whereNotNull('shipping_address')
            ->whereBetween('order_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->get(['shipping_address']);

        return response()->json([
            'range' => $range,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'map' => $this->buildCityOrderCountsByOrders($rangeOrders),
        ]);
    }

    private function buildCityOrderCountsByOrders($orders): array
    {
        $provinceMap = $this->provinceMap();
        $counts = [];

        foreach ($orders as $order) {
            $city = $this->extractCity($order->shipping_address);
            if (!$city) {
                continue;
            }
            $normalized = $this->normalizeCity($city);
            $province = $provinceMap[$normalized] ?? null;
            if (!$province) {
                continue;
            }
            $counts[$normalized] = ($counts[$normalized] ?? 0) + 1;
        }

        return $counts;
    }

    private function resolveMapRange(string $range): array
    {
        $range = strtolower(trim($range));
        Carbon::setLocale('tr');
        $end = Carbon::now();

        return match ($range) {
            'day' => [Carbon::today(), Carbon::today()->endOfDay()],
            'month' => [Carbon::now()->startOfMonth(), $end],
            'quarter' => [Carbon::now()->subMonthsNoOverflow(2)->startOfMonth(), $end],
            'half' => [Carbon::now()->subMonthsNoOverflow(5)->startOfMonth(), $end],
            'year' => [Carbon::now()->startOfYear(), $end],
            default => [Carbon::now()->startOfWeek(), $end],
        };
    }

    public function metrics(Request $request): JsonResponse
    {
        $user = SupportUser::currentUser();

        $orderQuery = Order::query();
        if ($user && !$user->isSuperAdmin()) {
            $orderQuery->where('user_id', $user->id);
        }
        $orderQuery = $this->applyRealOrderFilters($orderQuery);

        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $todayOrders = (clone $orderQuery)
            ->whereDate('order_date', $today->toDateString())
            ->get(['items', 'total_amount', 'marketplace_id']);
        $monthOrders = (clone $orderQuery)
            ->whereBetween('order_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['items']);

        $todayOrderCount = (clone $orderQuery)
            ->whereDate('order_date', $today->toDateString())
            ->count();
        $monthOrderCount = (clone $orderQuery)
            ->whereBetween('order_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->count();

        $todayQty = $this->sumSoldQuantity($todayOrders);
        $monthQty = $this->sumSoldQuantity($monthOrders);
        $todayAmount = (float) $todayOrders->sum('total_amount');

        $marketplaceTotals = (clone $orderQuery)
            ->whereDate('order_date', $today->toDateString())
            ->selectRaw('marketplace_id, SUM(total_amount) as total')
            ->groupBy('marketplace_id')
            ->orderByDesc('total')
            ->get();

        $activeMarketplaces = Marketplace::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $totalsByMarketplace = $marketplaceTotals->pluck('total', 'marketplace_id')->all();

        $marketplaceBreakdown = $activeMarketplaces->map(function ($marketplace) use ($totalsByMarketplace) {
            return [
                'name' => $marketplace->name,
                'code' => $marketplace->code,
                'total' => (float) ($totalsByMarketplace[$marketplace->id] ?? 0),
            ];
        })->values();

        $netProfitChart = $this->buildAmountChartForRange($orderQuery, (string) $request->input('range', 'week'));

        return response()->json([
            'kpis' => [
                'daily_orders' => $todayOrderCount,
                'daily_items' => $todayQty,
                'monthly_orders' => $monthOrderCount,
                'monthly_items' => $monthQty,
            ],
            'daily_sales' => [
                'total' => $todayAmount,
                'marketplaces' => $marketplaceBreakdown,
            ],
            'net_profit_chart' => $netProfitChart,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function buildAmountChartForRange($orderQuery, string $range): array
    {
        $range = strtolower(trim($range));
        $end = Carbon::now();

        $range = match ($range) {
            'day', 'week', 'month', 'quarter', 'half', 'year' => $range,
            default => 'week',
        };

        $start = match ($range) {
            'day' => $end->copy()->startOfDay(),
            'month' => $end->copy()->startOfMonth()->startOfDay(),
            'quarter' => $end->copy()->subMonthsNoOverflow(2)->startOfMonth()->startOfDay(),
            'half' => $end->copy()->subMonthsNoOverflow(5)->startOfMonth()->startOfDay(),
            'year' => $end->copy()->startOfYear()->startOfDay(),
            default => $end->copy()->subDays(6)->startOfDay(),
        };

        $labels = [];
        $values = [];
        $timestamps = [];

        if ($range === 'day') {
            $driver = $orderQuery->getModel()->getConnection()->getDriverName();
            $hourExpr = match ($driver) {
                'sqlite' => "strftime('%H', order_date)",
                'pgsql' => "to_char(order_date, 'HH24')",
                default => "DATE_FORMAT(order_date, '%H')",
            };

            $rows = (clone $orderQuery)
                ->whereBetween('order_date', [$start->toDateTimeString(), $end->toDateTimeString()])
                ->selectRaw("$hourExpr as hour, SUM(total_amount) as total")
                ->groupBy('hour')
                ->orderBy('hour')
                ->get();

            $totalsByHour = $rows->pluck('total', 'hour')->all();
            for ($hour = 0; $hour <= 23; $hour++) {
                $key = str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
                $labels[] = $key . ':00';
                $values[] = (float) ($totalsByHour[$key] ?? 0);
                $timestamps[] = $start->copy()->setTime($hour, 0)->toIso8601String();
            }
        } else {
            $rows = (clone $orderQuery)
                ->whereBetween('order_date', [$start->toDateTimeString(), $end->toDateTimeString()])
                ->selectRaw('DATE(order_date) as date, SUM(total_amount) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $totalsByDate = $rows->pluck('total', 'date')->all();
            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                $dateKey = $day->toDateString();
                $labels[] = $day->translatedFormat('d M');
                $values[] = (float) ($totalsByDate[$dateKey] ?? 0);
                $timestamps[] = $day->copy()->startOfDay()->toIso8601String();
            }
        }

        return [
            'range' => $range,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'labels' => $labels,
            'values' => $values,
            'timestamps' => $timestamps,
        ];
    }

    private function applyRealOrderFilters($orderQuery)
    {
        return $orderQuery
            ->whereNotNull('marketplace_id')
            ->whereNotNull('marketplace_order_id');
    }

    private function extractCity($shippingAddress): ?string
    {
        if (!$shippingAddress) {
            return null;
        }
$raw = is_string($shippingAddress) ? trim($shippingAddress) : $shippingAddress;

        if (is_string($raw) && str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $city = $decoded['city'] ?? $decoded['il'] ?? $decoded['province'] ?? $decoded['state'] ?? null;
                if ($city) {
                    return (string) $city;
                }
            }
        }

        if (is_array($raw)) {
            $city = $raw['city'] ?? $raw['il'] ?? $raw['province'] ?? $raw['state'] ?? null;
            if ($city) {
                return (string) $city;
            }
        }

        if (is_string($raw)) {
            $normalizedAddress = $this->normalizeCity($raw);
            foreach (array_keys($this->turkeyProvinceCodes()) as $province) {
                $needle = $this->normalizeCity($province);
                if (str_contains($normalizedAddress, $needle)) {
                    return $province;
                }
            }
        }

        return null;
    }

    private function normalizeCity(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $replacements = [
            'ç' => 'c',
            'ğ' => 'g',
            'ı' => 'i',
            'İ' => 'i',
            'ö' => 'o',
            'ş' => 's',
            'ü' => 'u',
        ];
        $value = strtr($value, $replacements);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return trim($value);
    }

    private function provinceMap(): array
    {
        $map = [];
        foreach ($this->turkeyProvinceCodes() as $name => $code) {
            $normalized = $this->normalizeCity($name);
            $map[$normalized] = [
                'name' => $name,
                'code' => $code,
                'normalized' => $normalized,
            ];
        }

        return $map;
    }

    private function turkeyProvinceCodes(): array
    {
        return [
            'Adana' => 'TR-01',
            'Adıyaman' => 'TR-02',
            'Afyonkarahisar' => 'TR-03',
            'Ağrı' => 'TR-04',
            'Amasya' => 'TR-05',
            'Ankara' => 'TR-06',
            'Antalya' => 'TR-07',
            'Artvin' => 'TR-08',
            'Aydın' => 'TR-09',
            'Balıkesir' => 'TR-10',
            'Bilecik' => 'TR-11',
            'Bingöl' => 'TR-12',
            'Bitlis' => 'TR-13',
            'Bolu' => 'TR-14',
            'Burdur' => 'TR-15',
            'Bursa' => 'TR-16',
            'Çanakkale' => 'TR-17',
            'Çankırı' => 'TR-18',
            'Çorum' => 'TR-19',
            'Denizli' => 'TR-20',
            'Diyarbakır' => 'TR-21',
            'Edirne' => 'TR-22',
            'Elazığ' => 'TR-23',
            'Erzincan' => 'TR-24',
            'Erzurum' => 'TR-25',
            'Eskişehir' => 'TR-26',
            'Gaziantep' => 'TR-27',
            'Giresun' => 'TR-28',
            'Gümüşhane' => 'TR-29',
            'Hakkâri' => 'TR-30',
            'Hatay' => 'TR-31',
            'Isparta' => 'TR-32',
            'Mersin' => 'TR-33',
            'İstanbul' => 'TR-34',
            'İzmir' => 'TR-35',
            'Kars' => 'TR-36',
            'Kastamonu' => 'TR-37',
            'Kayseri' => 'TR-38',
            'Kırklareli' => 'TR-39',
            'Kırşehir' => 'TR-40',
            'Kocaeli' => 'TR-41',
            'Konya' => 'TR-42',
            'Kütahya' => 'TR-43',
            'Malatya' => 'TR-44',
            'Manisa' => 'TR-45',
            'Kahramanmaraş' => 'TR-46',
            'Mardin' => 'TR-47',
            'Muğla' => 'TR-48',
            'Muş' => 'TR-49',
            'Nevşehir' => 'TR-50',
            'Niğde' => 'TR-51',
            'Ordu' => 'TR-52',
            'Rize' => 'TR-53',
            'Sakarya' => 'TR-54',
            'Samsun' => 'TR-55',
            'Siirt' => 'TR-56',
            'Sinop' => 'TR-57',
            'Sivas' => 'TR-58',
            'Tekirdağ' => 'TR-59',
            'Tokat' => 'TR-60',
            'Trabzon' => 'TR-61',
            'Tunceli' => 'TR-62',
            'Şanlıurfa' => 'TR-63',
            'Uşak' => 'TR-64',
            'Van' => 'TR-65',
            'Yozgat' => 'TR-66',
            'Zonguldak' => 'TR-67',
            'Aksaray' => 'TR-68',
            'Bayburt' => 'TR-69',
            'Karaman' => 'TR-70',
            'Kırıkkale' => 'TR-71',
            'Batman' => 'TR-72',
            'Şırnak' => 'TR-73',
            'Bartın' => 'TR-74',
            'Ardahan' => 'TR-75',
            'Iğdır' => 'TR-76',
            'Yalova' => 'TR-77',
            'Karabük' => 'TR-78',
            'Kilis' => 'TR-79',
            'Osmaniye' => 'TR-80',
            'Düzce' => 'TR-81',
        ];
    }

    private function extractFailureMessage(mixed $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }
$candidates = [
            $payload['error_message'] ?? null,
            $payload['errorMessage'] ?? null,
            $payload['error'] ?? null,
            $payload['message'] ?? null,
            $payload['failure_message'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }
}

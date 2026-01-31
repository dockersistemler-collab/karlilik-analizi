<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\Product;
use App\Models\Order;
use App\Models\MarketplaceProduct;
use App\Services\Reports\SoldProductsReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

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

        return view('admin.dashboard', compact(
            'stats',
            'recent_orders',
            'marketplaces',
            'kpis',
            'topProducts',
            'mapData',
            'range'
        ));
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
}

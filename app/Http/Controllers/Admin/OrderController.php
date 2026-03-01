<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Marketplace;
use App\Services\Modules\ModuleGate;
use App\Support\SupportUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OrderController extends Controller
{
    private function ensureOwner(Order $order): void
    {
        $user = SupportUser::currentUser();
        if ($user && !$user->isSuperAdmin() && $order->user_id !== $user->id) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $user = SupportUser::currentUser();
        $applySharedFilters = static function ($queryBuilder) use ($request): void {
            if ($request->filled('status')) {
                $status = (string) $request->status;
                if ($status === 'approval') {
                    $queryBuilder->whereIn('status', ['pending', 'approved']);
                } elseif ($status !== 'all') {
                    $queryBuilder->where('status', $status);
                }
            }

            if ($request->filled('date_from')) {
                $queryBuilder->whereDate('order_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $queryBuilder->whereDate('order_date', '<=', $request->date_to);
            }
        };

        $query = Order::query();
        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }
        $applySharedFilters($query);

        $countsQuery = Order::query();
        if ($user && !$user->isSuperAdmin()) {
            $countsQuery->where('user_id', $user->id);
        }
        $applySharedFilters($countsQuery);

        $allOrdersCount = (clone $countsQuery)->count();
        $marketplaceCounts = (clone $countsQuery)
            ->selectRaw('marketplace_id, COUNT(*) as aggregate')
            ->groupBy('marketplace_id')
            ->pluck('aggregate', 'marketplace_id');

        $tabStatusCountsQuery = Order::query();
        if ($user && !$user->isSuperAdmin()) {
            $tabStatusCountsQuery->where('user_id', $user->id);
        }
        if ($request->filled('date_from')) {
            $tabStatusCountsQuery->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $tabStatusCountsQuery->whereDate('order_date', '<=', $request->date_to);
        }
        if ($request->filled('marketplace_id')) {
            $tabStatusCountsQuery->where('marketplace_id', $request->marketplace_id);
        }
        $tabStatusCounts = $tabStatusCountsQuery
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        if ($request->filled('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        $orders = $query->with('marketplace')->latest()->paginate(20);
        $marketplaces = Marketplace::where('is_active', true)->get();
        $canBulkCargoLabelPrint = $user ? app(ModuleGate::class)->isEnabledForUser($user, 'feature.bulk_cargo_label_print') : false;

        return view('admin.orders.index', compact('orders', 'marketplaces', 'canBulkCargoLabelPrint', 'marketplaceCounts', 'allOrdersCount', 'tabStatusCounts'));
    }

    public function show(Order $order)
    {
        $this->ensureOwner($order);
        $order->load(['marketplace', 'statusLogs.user']);
        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order)
    {
        $this->ensureOwner($order);
        $validated = $request->validate(['status' => 'required|in:pending,approved,shipped,delivered,cancelled,returned',
            'tracking_number' => 'nullable|string',
            'cargo_company' => 'nullable|string',
            'note' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status'],
            'tracking_number' => $validated['tracking_number'] ?? null,
            'cargo_company' => $validated['cargo_company'] ?? null,
        ]);

        if ($oldStatus !== $validated['status']) {
            $timestamps = [];
            if ($validated['status'] === 'approved') {
                $timestamps['approved_at'] = now();
            }
            if ($validated['status'] === 'shipped') {
                $timestamps['shipped_at'] = now();
            }
            if ($validated['status'] === 'delivered') {
                $timestamps['delivered_at'] = now();
            }
            if (!empty($timestamps)) {
                $order->update($timestamps);
            }

            \App\Models\OrderStatusLog::create([
                'order_id' => $order->id,
                'user_id' => SupportUser::currentUser()?->id, 'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'note' => $validated['note'] ?? null,
            ]);
        }

        return back()->with('success', 'Siparis basariyla guncellendi.');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate(['order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer',
            'status' => 'required|in:pending,approved,shipped,delivered,cancelled,returned',
            'note' => 'nullable|string|max:1000',
        ]);

        $user = SupportUser::currentUser();
        $orderIds = collect($validated['order_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($orderIds->isEmpty()) {
            return back()->with('error', 'Gecerli siparis secilmedi.');
        }

        $targetStatus = (string) $validated['status'];
        $now = now();

        $ordersQuery = Order::query()->whereIn('id', $orderIds->all());
        if ($user && !$user->isSuperAdmin()) {
            $ordersQuery->where('user_id', $user->id);
        }

        $orders = $ordersQuery->get(['id', 'status']);
        $ordersToUpdate = $orders->filter(fn ($order) => (string) $order->status !== $targetStatus)->values();

        if ($ordersToUpdate->isEmpty()) {
            return back()->with('info', 'Secili siparislerin durumu zaten guncel.');
        }

        $idsToUpdate = $ordersToUpdate->pluck('id')->all();
        $updateData = [
            'status' => $targetStatus,
            'updated_at' => $now,
        ];

        if ($targetStatus === 'approved') {
            $updateData['approved_at'] = $now;
        } elseif ($targetStatus === 'shipped') {
            $updateData['shipped_at'] = $now;
        } elseif ($targetStatus === 'delivered') {
            $updateData['delivered_at'] = $now;
        }

        $statusLogs = $ordersToUpdate->map(function ($order) use ($user, $targetStatus, $validated, $now): array {
            return [
                'order_id' => (int) $order->id,
                'user_id' => $user?->id,
                'old_status' => (string) $order->status,
                'new_status' => $targetStatus,
                'note' => $validated['note'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        DB::transaction(function () use ($idsToUpdate, $updateData, $statusLogs): void {
            Order::query()->whereIn('id', $idsToUpdate)->update($updateData);
            \App\Models\OrderStatusLog::query()->insert($statusLogs);
        });

        return back()->with('success', 'Siparis basariyla guncellendi.');
    }
    public function bulkShip(Request $request)
    {
        $validated = $request->validate(['bulk_ship_ids' => 'required|array',
            'bulk_ship_ids.*' => 'exists:orders,id',
            'cargo_company' => 'required|string|max:255',
            'tracking_number' => 'required|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);

        $user = SupportUser::currentUser();
        $orders = Order::whereIn('id', $validated['bulk_ship_ids'])->get();

        foreach ($orders as $order) {
            if ($user && !$user->isSuperAdmin() && $order->user_id !== $user->id) {
                continue;
            }
$oldStatus = $order->status;
            $order->update(['cargo_company' => $validated['cargo_company'],
                'tracking_number' => $validated['tracking_number'],
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            \App\Models\OrderStatusLog::create([
                'order_id' => $order->id,
                'user_id' => $user?->id, 'old_status' => $oldStatus,
                'new_status' => 'shipped',
                'note' => $validated['note'] ?? null,
            ]);
        }

        return back()->with('success', 'Secili siparisler kargoya alindi.');
    }

    public function export(Request $request)
    {
        $user = SupportUser::currentUser();
        $query = Order::with('marketplace')->latest();

        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }
$filename = 'orders-' . now()->format('Ymd-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'marketplace_order_id',
                'status',
                'marketplace',
                'customer_name',
                'total_amount',
                'currency',
                'order_date',
                'tracking_number',
            ]);

            $query->chunk(200, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    fputcsv($handle, [
                        $order->marketplace_order_id,
                        $order->status,
                        $order->marketplace?->name, $order->customer_name,
                        $order->total_amount,
                        $order->currency,
                        optional($order->order_date)->format('Y-m-d H:i'),
                        $order->tracking_number,
                    ]);
                }
            });
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function dailyRevenue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'marketplace_id' => ['nullable', 'integer', 'exists:marketplaces,id'],
        ]);

        $user = SupportUser::currentUser();
        $query = Order::query()->whereDate('order_date', (string) $validated['date']);

        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        if (!empty($validated['marketplace_id'])) {
            $query->where('marketplace_id', (int) $validated['marketplace_id']);
        }

        $total = (float) $query->sum('total_amount');
        $orderCount = (int) $query->count();

        return response()->json([
            'date' => (string) $validated['date'],
            'order_count' => $orderCount,
            'total_amount' => round($total, 2),
            'currency' => 'TRY',
        ]);
    }

    public function printLabels(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1', 'max:200'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $user = SupportUser::currentUser();
        abort_unless($user, 401);

        $orders = Order::query()
            ->with('marketplace')
            ->whereIn('id', $validated['order_ids'])
            ->when(!$user->isSuperAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('marketplace_id')
            ->orderBy('order_date')
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('warning', 'Yazdirilabilir siparis bulunamadi.');
        }

        $platformCount = $orders->pluck('marketplace_id')->filter()->unique()->count();
        if ($orders->count() > 1 && $platformCount > 1) {
            return back()->with('warning', 'Toplu etiket yazdirma platform bazli olmalidir. Lutfen tek platform secin.');
        }

        $labels = $orders->map(fn (Order $order) => $this->buildLabelPayload($order));

        return view('admin.orders.labels-print', [
            'labels' => $labels,
            'printedAt' => now(),
            'isBulk' => $labels->count() > 1,
            'platformName' => (string) ($orders->first()?->marketplace?->name ?? '-'),
        ]);
    }

    public function printSingleLabel(Order $order): View
    {
        $this->ensureOwner($order);
        $order->loadMissing('marketplace');

        return view('admin.orders.labels-print', [
            'labels' => collect([$this->buildLabelPayload($order)]),
            'printedAt' => now(),
            'isBulk' => false,
            'platformName' => (string) ($order->marketplace?->name ?? '-'),
        ]);
    }

    private function buildLabelPayload(Order $order): array
    {
        $address = $this->normalizeAddress($order->shipping_address);

        return [
            'order_id' => $order->id,
            'order_no' => (string) ($order->marketplace_order_id ?? $order->order_number ?? '-'),
            'platform' => (string) ($order->marketplace?->name ?? '-'),
            'customer_name' => (string) ($order->customer_name ?? '-'),
            'customer_phone' => (string) ($order->customer_phone ?? '-'),
            'cargo_company' => (string) ($order->cargo_company ?? '-'),
            'tracking_number' => (string) ($order->tracking_number ?? '-'),
            'address_line' => (string) ($address['line'] ?? '-'),
            'city' => (string) ($address['city'] ?? ''),
            'district' => (string) ($address['district'] ?? ''),
            'postal_code' => (string) ($address['postal_code'] ?? ''),
            'order_date' => optional($order->order_date)?->format('d.m.Y H:i') ?? '-',
        ];
    }

    private function normalizeAddress(mixed $shippingAddress): array
    {
        if (is_array($shippingAddress)) {
            $line = trim((string) ($shippingAddress['address'] ?? $shippingAddress['line1'] ?? $shippingAddress['full'] ?? ''));
            return [
                'line' => $line !== '' ? $line : json_encode($shippingAddress, JSON_UNESCAPED_UNICODE),
                'city' => (string) ($shippingAddress['city'] ?? ''),
                'district' => (string) ($shippingAddress['district'] ?? $shippingAddress['town'] ?? ''),
                'postal_code' => (string) ($shippingAddress['postal_code'] ?? $shippingAddress['zip'] ?? ''),
            ];
        }

        if (is_string($shippingAddress) && trim($shippingAddress) !== '') {
            $decoded = json_decode($shippingAddress, true);
            if (is_array($decoded)) {
                return $this->normalizeAddress($decoded);
            }

            return ['line' => $shippingAddress, 'city' => '', 'district' => '', 'postal_code' => ''];
        }

        return ['line' => '-', 'city' => '', 'district' => '', 'postal_code' => ''];
    }
}

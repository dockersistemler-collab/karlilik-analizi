<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Marketplace;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private function ensureOwner(Order $order): void
    {
        $user = auth()->user();
        if ($user && !$user->isSuperAdmin() && $order->user_id !== $user->id) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
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

        $orders = $query->paginate(20);
        $marketplaces = Marketplace::where('is_active', true)->get();

        return view('admin.orders.index', compact('orders', 'marketplaces'));
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
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,shipped,delivered,cancelled,returned',
            'tracking_number' => 'nullable|string',
            'cargo_company' => 'nullable|string',
            'note' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $order->status;
        $order->update([
            'status' => $validated['status'],
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
                'user_id' => $request->user()?->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'note' => $validated['note'] ?? null,
            ]);
        }

        return back()->with('success', 'Sipariş başarıyla güncellendi.');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
            'status' => 'required|in:pending,approved,shipped,delivered,cancelled,returned',
            'note' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $orders = Order::whereIn('id', $validated['order_ids'])->get();

        foreach ($orders as $order) {
            if ($user && !$user->isSuperAdmin() && $order->user_id !== $user->id) {
                continue;
            }

            $oldStatus = $order->status;
            if ($oldStatus === $validated['status']) {
                continue;
            }

            $order->update([
                'status' => $validated['status'],
            ]);

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
                'user_id' => $user?->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'note' => $validated['note'] ?? null,
            ]);
        }

        return back()->with('success', 'Seçili siparişler güncellendi.');
    }

    public function bulkShip(Request $request)
    {
        $validated = $request->validate([
            'bulk_ship_ids' => 'required|array',
            'bulk_ship_ids.*' => 'exists:orders,id',
            'cargo_company' => 'required|string|max:255',
            'tracking_number' => 'required|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        $orders = Order::whereIn('id', $validated['bulk_ship_ids'])->get();

        foreach ($orders as $order) {
            if ($user && !$user->isSuperAdmin() && $order->user_id !== $user->id) {
                continue;
            }

            $oldStatus = $order->status;
            $order->update([
                'cargo_company' => $validated['cargo_company'],
                'tracking_number' => $validated['tracking_number'],
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            \App\Models\OrderStatusLog::create([
                'order_id' => $order->id,
                'user_id' => $user?->id,
                'old_status' => $oldStatus,
                'new_status' => 'shipped',
                'note' => $validated['note'] ?? null,
            ]);
        }

        return back()->with('success', 'Seçili siparişler kargoya alındı.');
    }

    public function export(Request $request)
    {
        $user = $request->user();
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
                        $order->marketplace?->name,
                        $order->customer_name,
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
}

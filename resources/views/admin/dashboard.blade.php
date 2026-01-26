@extends('layouts.admin')

@section('header')
    Genel Bakış
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="panel-card p-6">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Ürünler</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-2">{{ $stats['total_products'] }}</h3>
            <p class="text-sm text-slate-500 mt-1">Aktif ürün: {{ $stats['active_products'] }}</p>
        </div>
        <div class="panel-card p-6">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Siparişler</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-2">{{ $stats['total_orders'] }}</h3>
            <p class="text-sm text-slate-500 mt-1">Bekleyen: {{ $stats['pending_orders'] }}</p>
        </div>
        <div class="panel-card p-6">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Pazaryerleri</p>
            <h3 class="text-2xl font-semibold text-slate-900 mt-2">{{ $stats['total_marketplaces'] }}</h3>
            <p class="text-sm text-slate-500 mt-1">Bağlı ürün: {{ $stats['total_marketplace_products'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="panel-card p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700">Son Siparişler</h3>
                <a href="{{ route('admin.orders.index') }}" class="text-xs text-blue-600 font-semibold">Tümünü Gör</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-400">
                        <tr>
                            <th class="text-left py-2 pr-4">Sipariş</th>
                            <th class="text-left py-2 pr-4">Müşteri</th>
                            <th class="text-left py-2 pr-4">Tutar</th>
                            <th class="text-left py-2">Durum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recent_orders as $order)
                            <tr>
                                <td class="py-3 pr-4 text-slate-800 font-semibold">{{ $order->order_number ?? $order->marketplace_order_id }}</td>
                                <td class="py-3 pr-4 text-slate-600">{{ $order->customer_name }}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ number_format($order->total_amount, 2) }} ₺</td>
                                <td class="py-3 text-xs text-slate-500">{{ ucfirst($order->status) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-slate-500">Sipariş bulunmuyor</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-card p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Hızlı İşlemler</h3>
            <div class="space-y-3">
                <a href="{{ route('admin.products.create') }}" class="btn btn-solid-accent w-full">
                    Yeni Ürün Ekle
                </a>
                <a href="{{ route('admin.orders.index') }}" class="block border border-slate-200 text-slate-700 text-sm font-semibold px-4 py-3 rounded-xl hover:bg-slate-50">
                    Siparişleri Gör
                </a>
                <a href="{{ route('admin.subscription') }}" class="block border border-slate-200 text-slate-700 text-sm font-semibold px-4 py-3 rounded-xl hover:bg-slate-50">
                    Paketim
                </a>
            </div>
        </div>
    </div>
@endsection

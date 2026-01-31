@extends('layouts.admin')

@section('header')
    Siparişler
@endsection

@section('content')
@php
    $tabs = [
        'all' => 'Tüm Siparişler',
        'pending' => 'Onay Bekleyen',
        'approved' => 'Onaylanan',
        'shipped' => 'Kargolanan',
        'delivered' => 'Teslim',
        'cancelled' => 'İptal',
        'returned' => 'İade',
    ];
    $activeTab = request('status') ?: 'all';
    $activePlan = auth()->user()?->getActivePlan();
    $canExport = !$activePlan || $activePlan->hasModule('exports.orders');
@endphp

<div class="panel-card p-4 mb-6">
    <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 pb-3">
        @foreach($tabs as $key => $label)
            <a href="{{ route('admin.orders.index', array_filter(array_merge(request()->query(), ['status' => $key === 'all' ? null : $key]))) }}"
               class="text-sm font-semibold {{ $activeTab === $key ? 'text-blue-600 border-b-2 border-blue-600 pb-2' : 'text-slate-400' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.orders.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-4">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Pazaryeri</label>
            <select name="marketplace_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                <option value="">Tümü</option>
                @foreach($marketplaces as $marketplace)
                    <option value="{{ $marketplace->id }}" {{ request('marketplace_id') == $marketplace->id ? 'selected' : '' }}>
                        {{ $marketplace->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Durum</label>
            <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                <option value="">Tümü</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Beklemede</option>
                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Onaylandı</option>
                <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Kargoda</option>
                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Teslim</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>İptal</option>
                <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>İade</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Başlangıç</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
        </div>

        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Bitiş</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="btn btn-solid-accent w-full">
                Filtrele
            </button>
            <a href="{{ route('admin.orders.index') }}" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50">
                Temizle
            </a>
        </div>
    </form>
</div>

<form method="POST" action="{{ route('admin.orders.bulk-update') }}">
    @csrf
    <div class="panel-card p-4 mb-4 flex flex-col md:flex-row md:items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Toplu Durum</label>
            <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" required>
                <option value="">Seçiniz</option>
                <option value="pending">Beklemede</option>
                <option value="approved">Onaylandı</option>
                <option value="shipped">Kargoda</option>
                <option value="delivered">Teslim</option>
                <option value="cancelled">İptal</option>
                <option value="returned">İade</option>
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-xs font-medium text-slate-500 mb-1">Not (opsiyonel)</label>
            <input type="text" name="note" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" placeholder="Toplu güncelleme notu">
        </div>
        <button type="submit" class="btn btn-solid-accent">
            Seçili Siparişleri Güncelle
        </button>
        @if($canExport)
        <a href="{{ route('admin.orders.export', request()->query()) }}" class="ml-auto btn btn-outline-accent">
            CSV indir
        </a>
        @endif
    </div>

    <div class="panel-card overflow-hidden">
        <table class="min-w-full" id="orders-table">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">
                    <input type="checkbox" onclick="document.querySelectorAll('input[name=&quot;order_ids[]&quot;]').forEach(cb=>cb.checked=this.checked); document.querySelectorAll('input[name=&quot;bulk_ship_ids[]&quot;]').forEach(cb=>cb.checked=this.checked);">
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Sipariş No</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Pazaryeri</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Müşteri</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tutar</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Durum</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Sipariş Tarihi</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">İşlem</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-100">
            @forelse($orders as $order)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}">
                    <input type="checkbox" class="hidden" name="bulk_ship_ids[]" value="{{ $order->id }}">
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <code class="bg-slate-100 px-2 py-1 rounded text-xs">{{ $order->marketplace_order_id }}</code>
                </td>
                <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ $order->marketplace->name }}</td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-slate-900">{{ $order->customer_name }}</div>
                    @if($order->customer_phone)
                        <div class="text-xs text-slate-500">{{ $order->customer_phone }}</div>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap font-semibold">
                    {{ number_format($order->total_amount, 2) }} {{ $order->currency }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="panel-pill text-xs 
                        @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                        @elseif($order->status == 'approved') bg-blue-100 text-blue-800
                        @elseif($order->status == 'shipped') bg-indigo-100 text-indigo-800
                        @elseif($order->status == 'delivered') bg-green-100 text-green-800
                        @elseif($order->status == 'cancelled') bg-red-100 text-red-800
                        @elseif($order->status == 'returned') bg-orange-100 text-orange-800
                        @endif">
                        {{ ucfirst($order->status) }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $order->order_date->format('d.m.Y H:i') }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a href="{{ route('admin.orders.show', $order) }}" class="text-blue-600 hover:text-blue-900">
                        <i class="fas fa-eye"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-6 py-4 text-center text-slate-500">Henüz sipariş bulunmuyor</td>
            </tr>
            @endforelse
        </tbody>
        </table>
    </div>
</form>

<form method="POST" action="{{ route('admin.orders.bulk-ship') }}">
    @csrf
    <div class="panel-card p-4 mb-4 grid grid-cols-1 md:grid-cols-5 gap-3">
        <div class="md:col-span-2">
            <label class="block text-xs font-medium text-slate-500 mb-1">Kargo Firması</label>
            <input type="text" name="cargo_company" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" required>
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-medium text-slate-500 mb-1">Takip No</label>
            <input type="text" name="tracking_number" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" required>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-500 mb-1">Not</label>
            <input type="text" name="note" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
        </div>
        <div class="md:col-span-5">
            <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
                Seçili Siparişleri Kargoya Al
            </button>
        </div>
    </div>
</form>

<div class="mt-6">
    {{ $orders->links() }}
</div>
<script>
    document.querySelectorAll('input[name="order_ids[]"]').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var hidden = cb.parentElement.querySelector('input[name="bulk_ship_ids[]"]');
            if (hidden) {
                hidden.checked = cb.checked;
            }
        });
    });
</script>
@endsection

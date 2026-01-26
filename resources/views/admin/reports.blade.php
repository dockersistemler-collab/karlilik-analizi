@extends('layouts.admin')

@section('header')
    Sipariş ve Ciro
@endsection

@section('content')
    <div class="panel-card p-6 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
                    @foreach($statusOptions as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Başlangıç</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Bitiş</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-solid-accent w-full">Filtrele</button>
                <a href="{{ route('admin.reports.index') }}" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 w-full text-center">
                    Temizle
                </a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="panel-card p-5">
            <p class="text-xs text-slate-500">Toplam Sipariş</p>
            <p class="text-2xl font-semibold text-slate-800">{{ number_format($summary->orders_count ?? 0) }}</p>
        </div>
        <div class="panel-card p-5">
            <p class="text-xs text-slate-500">Ciro</p>
            <p class="text-2xl font-semibold text-slate-800">{{ number_format($summary->revenue_total ?? 0, 2) }} ₺</p>
        </div>
        <div class="panel-card p-5">
            <p class="text-xs text-slate-500">Komisyon</p>
            <p class="text-2xl font-semibold text-slate-800">{{ number_format($summary->commission_total ?? 0, 2) }} ₺</p>
        </div>
        <div class="panel-card p-5">
            <p class="text-xs text-slate-500">Net Tutar</p>
            <p class="text-2xl font-semibold text-slate-800">{{ number_format($summary->net_total ?? 0, 2) }} ₺</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="panel-card p-6 lg:col-span-2">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Durum Bazlı Siparişler</h3>
            <div class="space-y-3 text-sm text-slate-600">
                @forelse($ordersByStatus as $row)
                    <div class="flex items-center justify-between">
                        <span>{{ $statusOptions[$row->status] ?? ucfirst($row->status) }}</span>
                        <span class="font-semibold text-slate-800">{{ $row->total }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">Veri bulunamadı.</p>
                @endforelse
            </div>
        </div>

        <div class="panel-card p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Pazaryeri Dağılımı</h3>
            <div class="space-y-3 text-sm text-slate-600">
                @forelse($ordersByMarketplace as $row)
                    <div class="flex items-center justify-between">
                        <span>{{ $marketplaceMap[$row->marketplace_id] ?? 'Bilinmeyen' }}</span>
                        <span class="font-semibold text-slate-800">{{ $row->total }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">Veri bulunamadı.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        <div class="panel-card p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Çok Satan Ürünler</h3>
            <div class="space-y-3 text-sm text-slate-600">
                @forelse($topProducts as $item)
                    <div class="flex items-center justify-between">
                        <span>{{ $item['name'] }}</span>
                        <span class="font-semibold text-slate-800">{{ $item['quantity'] }} adet</span>
                    </div>
                @empty
                    <p class="text-slate-500">Veri bulunamadı.</p>
                @endforelse
            </div>
        </div>
        <div class="panel-card p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Stokta En Fazla Ürün</h3>
            <div class="space-y-3 text-sm text-slate-600">
                @forelse($productsByStock as $product)
                    <div class="flex items-center justify-between">
                        <span>{{ $product->name }}</span>
                        <span class="font-semibold text-slate-800">{{ $product->stock_quantity }} adet</span>
                    </div>
                @empty
                    <p class="text-slate-500">Veri bulunamadı.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

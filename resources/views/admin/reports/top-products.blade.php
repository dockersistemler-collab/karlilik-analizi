@extends('layouts.admin')

@section('header')
    Çok Satan Ürünler
@endsection

@section('content')
    <div class="panel-card p-6 mb-6">
        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-end gap-3">
            <div class="min-w-[180px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Satış Kanalı</label>
                <select name="marketplace_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                    <option value="">Tümü</option>
                    @foreach($marketplaces as $marketplace)
                        <option value="{{ $marketplace->id }}" @selected(($filters['marketplace_id'] ?? null) == $marketplace->id)>
                            {{ $marketplace->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Başlangıç</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Bitiş</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Hızlı Seçim</label>
                <select name="quick_range" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                    <option value="">Seç</option>
                    @foreach($quickRanges as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['quick_range'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 lg:ml-auto">
                <button type="submit" class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('admin.reports.top-products') }}" class="btn btn-outline">Temizle</a>
            </div>
            @if($reportExportsEnabled)
                <details class="relative">
                    <summary class="btn btn-outline list-none cursor-pointer">Dışa Aktar</summary>
                    <div class="absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-lg shadow-lg p-2 z-10">
                        <a href="{{ route('admin.reports.top-products.export', request()->query()) }}" class="block px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-md">CSV</a>
                        <a href="{{ route('admin.reports.top-products.export', request()->query()) }}" class="block px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-md">Excel</a>
                    </div>
                </details>
            @endif
        </form>
    </div>

    <div class="panel-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-700">En Çok Satan Ürünler</h3>
            <span class="text-xs text-slate-400">İlk 100 ürün listelenir.</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-4">Stok Kodu</th>
                        <th class="text-left py-2 pr-4">Ürün Adı</th>
                        <th class="text-right py-2 pr-4">Satış Adedi</th>
                        <th class="text-right py-2">Toplam Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="py-3 pr-4 text-slate-600">{{ $row['stock_code'] ?? '-' }}</td>
                            <td class="py-3 pr-4 text-slate-800 font-semibold">{{ $row['name'] }}</td>
                            <td class="py-3 pr-4 text-right text-slate-700">{{ number_format($row['quantity']) }}</td>
                            <td class="py-3 text-right text-slate-700">{{ number_format($row['total'], 2, ',', '.') }} ₺</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-slate-500">Kayıt bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

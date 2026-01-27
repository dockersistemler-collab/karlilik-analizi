@extends('layouts.admin')

@section('header')
    Satılan Ürünler Raporu
@endsection

@section('content')
    <div class="panel-card p-6 mb-6">
        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-end gap-3">
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Başlangıç</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>
            <div class="min-w-[160px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Bitiş</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>
            <div class="flex items-center gap-2 lg:ml-auto">
                <button type="submit" class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('admin.reports.sold-products') }}" class="btn btn-outline">Temizle</a>
                <a href="{{ route('admin.reports.sold-products.print', request()->query()) }}" target="_blank" class="btn btn-outline">Yazdır</a>
            </div>
        </form>
    </div>

    <div class="panel-card p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-4">Stok Kodu</th>
                        <th class="text-left py-2 pr-4">Ürün Adı</th>
                        <th class="text-left py-2 pr-4">Seçenek</th>
                        <th class="text-right py-2">Satış Adedi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="py-3 pr-4 text-slate-600">{{ $row['stock_code'] ?? '-' }}</td>
                            <td class="py-3 pr-4 text-slate-800 font-semibold">{{ $row['name'] }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $row['variant'] ?? '-' }}</td>
                            <td class="py-3 text-right text-slate-700">{{ number_format($row['quantity']) }}</td>
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

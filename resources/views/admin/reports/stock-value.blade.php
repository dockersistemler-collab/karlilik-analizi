@extends('layouts.admin')

@section('header')
    Stoktaki Ürün Tutarları
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
        <div class="panel-card p-5">
            <p class="text-xs text-slate-500">Toplam Ürün Sayısı</p>
            <p class="text-2xl font-semibold text-slate-800">{{ number_format($summary['total_products'] ?? 0) }}</p>
        </div>
        <div class="panel-card p-5">
            <p class="text-xs text-slate-500">Toplam Satış Tutarı</p>
            <p class="text-2xl font-semibold text-slate-800">{{ number_format($summary['total_sales_amount'] ?? 0, 2, ',', '.') }} ₺</p>
        </div>
        <div class="panel-card p-5">
            <p class="text-xs text-slate-500">Yüksek Stoklu Ürün</p>
            <p class="text-lg font-semibold text-slate-800">{{ $summary['highest_stock']->name ?? '—' }}</p>
            <p class="text-xs text-slate-500">{{ $summary['highest_stock']->stock_quantity ?? 0 }} adet</p>
        </div>
        <div class="panel-card p-5">
            <p class="text-xs text-slate-500">Düşük Stoklu Ürün</p>
            <p class="text-lg font-semibold text-slate-800">{{ $summary['lowest_stock']->name ?? '—' }}</p>
            <p class="text-xs text-slate-500">{{ $summary['lowest_stock']->stock_quantity ?? 0 }} adet</p>
        </div>
        <div class="panel-card p-5">
            <p class="text-xs text-slate-500">Stoktaki Ürünlerin Maliyet Değeri</p>
            <p class="text-2xl font-semibold text-slate-800">{{ number_format($summary['total_cost_amount'] ?? 0, 2, ',', '.') }} ₺</p>
        </div>
    </div>

    <div class="panel-card p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-4">Ürün Adı</th>
                        <th class="text-left py-2 pr-4">Stok Kodu</th>
                        <th class="text-right py-2 pr-4">Alış Maliyeti</th>
                        <th class="text-right py-2 pr-4">Satış Fiyatı</th>
                        <th class="text-right py-2 pr-4">Stok</th>
                        <th class="text-right py-2 pr-4">Stok Maliyeti</th>
                        <th class="text-right py-2">Satış Toplam Tutarı</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="py-3 pr-4 text-slate-800 font-semibold">{{ $row['name'] }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $row['sku'] ?? '-' }}</td>
                            <td class="py-3 pr-4 text-right text-slate-700">{{ number_format($row['cost_price'], 2, ',', '.') }} ₺</td>
                            <td class="py-3 pr-4 text-right text-slate-700">{{ number_format($row['price'], 2, ',', '.') }} ₺</td>
                            <td class="py-3 pr-4 text-right text-slate-700">{{ number_format($row['stock_quantity']) }}</td>
                            <td class="py-3 pr-4 text-right text-slate-700">{{ number_format($row['stock_cost'], 2, ',', '.') }} ₺</td>
                            <td class="py-3 text-right text-slate-700">{{ number_format($row['sales_total'], 2, ',', '.') }} ₺</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-slate-500">Kayıt bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

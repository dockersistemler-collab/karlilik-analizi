@extends('layouts.admin')

@section('title', 'Stok - Urun Stoklari')

@section('content')
    <div class="panel-card p-6">
        <div class="flex items-center justify-between gap-3 mb-5">
            <h1 class="text-lg font-semibold text-slate-900">Stok - Urun Stoklari</h1>
            <a href="{{ route('portal.inventory.admin.movements.index') }}" class="btn btn-outline">Hareketler</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-2 pr-4">Urun</th>
                        <th class="py-2 pr-4">SKU / Barkod</th>
                        <th class="py-2 pr-4">Stok</th>
                        <th class="py-2 pr-4">Kritik Seviye</th>
                        <th class="py-2 pr-4">Uyari</th>
                        <th class="py-2 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $critical = (int) $product->stock_quantity <= (int) $product->critical_stock_level;
                            $hasAlert = isset($activeAlertProductIds[$product->id]);
                        @endphp
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-semibold text-slate-900">{{ $product->name }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $product->sku }} / {{ $product->barcode ?: '-' }}</td>
                            <td class="py-3 pr-4">
                                <span class="{{ $critical ? 'text-rose-700 font-semibold' : 'text-slate-800' }}">{{ $product->stock_quantity }}</span>
                            </td>
                            <td class="py-3 pr-4 text-slate-700">{{ $product->critical_stock_level }}</td>
                            <td class="py-3 pr-4">
                                @if($hasAlert)
                                    <span class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-xs text-rose-700">Kritik Uyari</span>
                                @else
                                    <span class="text-slate-400 text-xs">-</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-right">
                                <a href="{{ route('portal.inventory.admin.products.edit', $product) }}" class="btn btn-outline">Duzenle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-500">Urun bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $products->links() }}
        </div>
    </div>
@endsection

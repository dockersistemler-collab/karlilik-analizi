@extends('layouts.admin')

@section('title', 'Stok - Kullanici Stok Gorunumu')

@section('content')
    <div class="panel-card p-6">
        <h1 class="text-lg font-semibold text-slate-900 mb-5">Stok - Read Only Stok Listesi</h1>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-2 pr-4">Urun</th>
                        <th class="py-2 pr-4">SKU / Barkod</th>
                        <th class="py-2 pr-4">Stok</th>
                        <th class="py-2 pr-4">Durum</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $critical = (int) $product->stock_quantity <= (int) $product->critical_stock_level;
                        @endphp
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-semibold text-slate-900">{{ $product->name }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $product->sku }} / {{ $product->barcode ?: '-' }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $product->stock_quantity }}</td>
                            <td class="py-3 pr-4">
                                @if($critical)
                                    <span class="inline-flex items-center rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-xs text-rose-700">Kritik</span>
                                @else
                                    <span class="inline-flex items-center rounded-md border border-emerald-200 bg-emerald-50 px-2 py-1 text-xs text-emerald-700">Normal</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-6 text-center text-slate-500">Urun bulunamadi.</td>
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

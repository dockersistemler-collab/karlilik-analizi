@extends('layouts.admin')

@section('title', 'Stok - Stok Duzenle')

@section('content')
    <div class="panel-card p-6 max-w-3xl">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h1 class="text-lg font-semibold text-slate-900">Stok Duzenle</h1>
            <a href="{{ route('portal.inventory.admin.products.index') }}" class="btn btn-outline">Geri</a>
        </div>

        <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
            <div><span class="font-semibold">Urun:</span> {{ $product->name }}</div>
            <div><span class="font-semibold">SKU:</span> {{ $product->sku }}</div>
            <div><span class="font-semibold">Mevcut Stok:</span> {{ $product->stock_quantity }}</div>
            <div><span class="font-semibold">Kritik Seviye:</span> {{ $product->critical_stock_level }}</div>
        </div>

        <form method="POST" action="{{ route('portal.inventory.admin.products.update', $product) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Islem</label>
                <select name="direction" class="w-full">
                    <option value="increase" @selected(old('direction') === 'increase')>Stok Arttir</option>
                    <option value="decrease" @selected(old('direction') === 'decrease')>Stok Azalt</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Miktar</label>
                <input type="number" min="1" name="quantity" value="{{ old('quantity', 1) }}" class="w-full">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Kritik Stok Seviyesi</label>
                <input type="number" min="0" name="critical_stock_level" value="{{ old('critical_stock_level', $product->critical_stock_level) }}" class="w-full">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Aciklama</label>
                <textarea name="note" rows="3" class="w-full">{{ old('note') }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary">Kaydet</button>
                <a href="{{ route('portal.inventory.admin.products.index') }}" class="btn btn-outline">Iptal</a>
            </div>
        </form>
    </div>
@endsection

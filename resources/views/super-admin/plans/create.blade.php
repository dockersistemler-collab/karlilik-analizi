@extends('layouts.super-admin')

@section('header')
    Yeni Paket
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow p-6 max-w-3xl">
        <form method="POST" action="{{ route('super-admin.plans.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Paket Adı</label>
                <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full border-slate-300 rounded-md" required>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Açıklama</label>
                <textarea name="description" class="mt-1 w-full border-slate-300 rounded-md" rows="3">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Aylık Fiyat</label>
                <input type="number" step="0.01" name="price" value="{{ old('price', 0) }}" class="mt-1 w-full border-slate-300 rounded-md" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Yıllık Fiyat</label>
                <input type="number" step="0.01" name="yearly_price" value="{{ old('yearly_price') }}" class="mt-1 w-full border-slate-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Faturalama</label>
                <select name="billing_period" class="mt-1 w-full border-slate-300 rounded-md" required>
                    <option value="monthly" @selected(old('billing_period') === 'monthly')>Aylık</option>
                    <option value="yearly" @selected(old('billing_period') === 'yearly')>Yıllık</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Sıra</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="mt-1 w-full border-slate-300 rounded-md">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Max Ürün</label>
                <input type="number" name="max_products" value="{{ old('max_products', 0) }}" class="mt-1 w-full border-slate-300 rounded-md" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Max Pazaryeri</label>
                <input type="number" name="max_marketplaces" value="{{ old('max_marketplaces', 0) }}" class="mt-1 w-full border-slate-300 rounded-md" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Max Sipariş/Ay</label>
                <input type="number" name="max_orders_per_month" value="{{ old('max_orders_per_month', 0) }}" class="mt-1 w-full border-slate-300 rounded-md" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Max Ticket/Ay</label>
                <input type="number" name="max_tickets_per_month" value="{{ old('max_tickets_per_month', 0) }}" class="mt-1 w-full border-slate-300 rounded-md" required>
            </div>

            <div class="md:col-span-2 grid grid-cols-2 gap-3">
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="api_access" value="1" class="rounded" @checked(old('api_access'))>
                    <span class="text-sm text-slate-700">API Erişimi</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="advanced_reports" value="1" class="rounded" @checked(old('advanced_reports'))>
                    <span class="text-sm text-slate-700">Gelişmiş Raporlar</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="priority_support" value="1" class="rounded" @checked(old('priority_support'))>
                    <span class="text-sm text-slate-700">Öncelikli Destek</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="custom_integrations" value="1" class="rounded" @checked(old('custom_integrations'))>
                    <span class="text-sm text-slate-700">Özel Entegrasyon</span>
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" class="rounded" @checked(old('is_active', true))>
                    <span class="text-sm text-slate-700">Aktif</span>
                </label>
            </div>

            <div class="md:col-span-2 flex items-center gap-3 mt-2">
                <button type="submit" class="btn btn-solid-accent">
                    Kaydet
                </button>
                <a href="{{ route('super-admin.plans.index') }}" class="text-slate-500 hover:text-slate-700">
                    Vazgeç
                </a>
            </div>
        </form>
    </div>
@endsection

@extends('layouts.admin')

@section('header')
    Müşteri Detayı
@endsection

@section('content')
    <div class="panel-card p-6 max-w-4xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-xs uppercase text-slate-400">Müşteri</p>
                <h3 class="text-sm font-semibold text-slate-800 mt-1">{{ $customer->name }}</h3>
                <p class="text-sm text-slate-500">{{ $customer->email }}</p>
            </div>
            <div class="text-sm text-slate-600">
                Tür: {{ $customer->customer_type === 'corporate' ? 'Tüzel Kişi' : 'Gerçek Kişi' }}
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($customer->customer_type === 'corporate')
                <div class="panel-card p-4">
                    <p class="text-xs uppercase text-slate-400">Firma Ünvanı</p>
                    <p class="text-sm font-semibold text-slate-800 mt-1">{{ $customer->company_title ?? '-' }}</p>
                </div>
            @endif
            <div class="panel-card p-4">
                <p class="text-xs uppercase text-slate-400">Telefon</p>
                <p class="text-sm font-semibold text-slate-800 mt-1">{{ $customer->phone ?? '-' }}</p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs uppercase text-slate-400">Vergi / TC Kimlik</p>
                <p class="text-sm font-semibold text-slate-800 mt-1">{{ $customer->tax_id ?? '-' }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $customer->tax_office ?? '-' }}</p>
            </div>
        </div>

        <div class="mt-6 panel-card p-4">
            <h3 class="text-sm font-semibold text-slate-800">Adres Bilgileri</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 text-sm text-slate-600">
                <div>
                    <span class="text-slate-400">İl:</span>
                    <span class="font-medium text-slate-900">{{ $customer->city ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400">İlçe:</span>
                    <span class="font-medium text-slate-900">{{ $customer->district ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400">Mahalle:</span>
                    <span class="font-medium text-slate-900">{{ $customer->neighborhood ?? '-' }}</span>
                </div>
                <div>
                    <span class="text-slate-400">Sokak:</span>
                    <span class="font-medium text-slate-900">{{ $customer->street ?? '-' }}</span>
                </div>
                <div class="md:col-span-2">
                    <span class="text-slate-400">Açık Adres:</span>
                    <div class="font-medium text-slate-900 mt-1">
                        {{ $customer->billing_address ?? '-' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.customers.edit', $customer) }}" class="text-slate-600 hover:text-slate-900">Düzenle</a>
                <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Müşteri silinsin mi?')">Sil</button>
                </form>
                <a href="{{ route('admin.customers.index') }}" class="text-slate-500 hover:text-slate-700">Listeye Dön</a>
            </div>
        </div>
    </div>
@endsection

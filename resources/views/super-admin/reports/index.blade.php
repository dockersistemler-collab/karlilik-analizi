@extends('layouts.super-admin')

@section('header')
    Gelir Raporları
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-slate-500">Toplam Gelir</p>
            <p class="text-2xl font-semibold text-slate-800">{{ number_format($totalRevenue, 2) }} ₺</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-slate-500">Aktif Abonelik</p>
            <p class="text-2xl font-semibold text-slate-800">{{ $activeSubscriptions }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-slate-500">Toplam Müşteri</p>
            <p class="text-2xl font-semibold text-slate-800">{{ $totalClients }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-slate-500">Toplam Sipariş</p>
            <p class="text-2xl font-semibold text-slate-800">{{ $totalOrders }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mt-6">
        <h3 class="text-sm font-semibold text-slate-800 mb-2">Notlar</h3>
        <p class="text-sm text-slate-600">
            Gelir raporları abonelik tutarları üzerinden hesaplanır. Daha detaylı kırılımlar (aylık, paket bazlı)
            entegrasyonlardan sonra genişletilecek.
        </p>
    </div>
@endsection

@extends('layouts.admin')

@section('header')
    Ek Modüller & Hizmetler
@endsection

@section('content')
@php
    $ecommerce = [
        ['name' => 'IdeaSoft', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
        ['name' => 'Opencart', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
        ['name' => 'WooCommerce', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
        ['name' => 'Shopify', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
        ['name' => 'Ticimax', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
        ['name' => 'Wix', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
        ['name' => 'Kolay Sipariş', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
        ['name' => 'ETicaretSoft', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
        ['name' => 'Shopier', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
        ['name' => 'ikas', 'price' => '20.120,00 ₺', 'old' => '21.300,00 ₺'],
    ];

    $cargo = [
        ['name' => 'Aras Kargo', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Yurtiçi Kargo', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Sürat Kargo', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'MNG Kargo', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'HepsiJet', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Sendeo', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
    ];

    $services = [
        ['name' => 'Set (Bundle) Ürün Yönetimi', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Sanal/Hayalet Ürün Yönetimi', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Dijital Kod Entegrasyonu', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Grup Ürün Yönetimi', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Gelişmiş İade Yönetimi', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Kritik Stok Yönetimi', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Kullanıcı ve Yetki Yönetimi', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
        ['name' => 'Ürün Farklılık Kontrolü', 'price' => '4.730,00 ₺', 'old' => '6.080,00 ₺'],
    ];

    $erp = [
        ['name' => 'Dia Yazılım', 'price' => '25.520,00 ₺', 'old' => '33.740,00 ₺'],
        ['name' => 'Sysmond', 'price' => '25.520,00 ₺', 'old' => '33.740,00 ₺'],
        ['name' => 'Logo', 'price' => '25.520,00 ₺', 'old' => '33.750,00 ₺'],
        ['name' => 'Logo İşbaşı', 'price' => '14.180,00 ₺', 'old' => '20.250,00 ₺'],
        ['name' => 'IdeaConnect Muhasebe', 'price' => '28.350,00 ₺', 'old' => '37.130,00 ₺'],
        ['name' => 'Nebim', 'price' => '25.520,00 ₺', 'old' => '33.740,00 ₺'],
    ];

    $sections = [
        ['title' => 'E-Ticaret entegrasyonları', 'subtitle' => 'Ürün, fiyat, stok ve sipariş yönetimini tek ekranda toplayın.', 'items' => $ecommerce],
        ['title' => 'Kargo entegrasyonları', 'subtitle' => 'Pazaryerlerinden gelen siparişleri anlaşmalı kargonuzla yönetin.', 'items' => $cargo],
        ['title' => 'Ek Hizmetler', 'subtitle' => 'İşlerinizi hızlandıran profesyonel modüller.', 'items' => $services],
        ['title' => 'ERP/Muhasebe entegrasyonları', 'subtitle' => 'Fiziksel mağaza ve pazaryeri verilerini tek noktada birleştirin.', 'items' => $erp],
    ];
@endphp

<div class="text-center mb-10">
    <h2 class="text-2xl md:text-3xl font-semibold text-slate-900">Ek Modüller & Hizmetler</h2>
    <p class="text-sm text-slate-500 mt-2">Tüm paketleri 7 gün boyunca ücretsiz deneyebilirsiniz.</p>
</div>

@foreach($sections as $section)
    <div class="mb-12">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-slate-900">{{ $section['title'] }}</h3>
            <p class="text-sm text-slate-500 mt-1">{{ $section['subtitle'] }}</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($section['items'] as $item)
                <div class="panel-card p-6 text-center">
                    <div class="w-14 h-14 rounded-full bg-slate-50 border border-slate-200 mx-auto flex items-center justify-center text-sm font-semibold text-slate-600">
                        {{ mb_substr($item['name'], 0, 2) }}
                    </div>
                    <h4 class="text-sm font-semibold text-slate-900 mt-4">{{ $item['name'] }}</h4>
                    <p class="text-xs text-slate-400 line-through mt-2">{{ $item['old'] }}</p>
                    <p class="text-sm font-semibold text-slate-700">{{ $item['price'] }} / Yıllık</p>
                    <button type="button" class="btn btn-solid-accent">
                        7 Gün Dene
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
@endsection

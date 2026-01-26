@extends('layouts.admin')

@section('header')
    Eğitim Merkezi
@endsection

@section('content')
    <div class="panel-card p-6 mb-6">
        <p class="text-sm text-slate-600">
            Temel kurulum ve pazaryeri entegrasyonları için kısa eğitimler.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="panel-card p-5">
            <h3 class="text-sm font-semibold text-slate-800">Hızlı Başlangıç</h3>
            <p class="text-sm text-slate-500 mt-2">Hesap kurulumu ve ilk ürün yükleme.</p>
            <a href="#" class="topbar-link mt-3 inline-flex">Eğitimi izle</a>
        </div>
        <div class="panel-card p-5">
            <h3 class="text-sm font-semibold text-slate-800">Entegrasyon Rehberi</h3>
            <p class="text-sm text-slate-500 mt-2">API anahtarları ve mağaza bağlantıları.</p>
            <a href="#" class="topbar-link mt-3 inline-flex">Eğitimi izle</a>
        </div>
        <div class="panel-card p-5">
            <h3 class="text-sm font-semibold text-slate-800">Sipariş Yönetimi</h3>
            <p class="text-sm text-slate-500 mt-2">Sipariş akışı ve otomatik işlemler.</p>
            <a href="#" class="topbar-link mt-3 inline-flex">Eğitimi izle</a>
        </div>
        <div class="panel-card p-5">
            <h3 class="text-sm font-semibold text-slate-800">Raporlama</h3>
            <p class="text-sm text-slate-500 mt-2">Performans ve satış raporları.</p>
            <a href="#" class="topbar-link mt-3 inline-flex">Eğitimi izle</a>
        </div>
    </div>
@endsection

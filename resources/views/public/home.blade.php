@extends('layouts.public')

@section('content')
    <section class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center mt-6">
        <div class="space-y-6">
            <p class="text-xs uppercase tracking-[0.3em] text-teal-700 font-semibold">Tek merkezden yönetim</p>
            <h1 class="text-4xl md:text-5xl font-semibold leading-tight text-slate-900">
                Tüm pazaryeri operasyonlarını tek panelde topla.
            </h1>
            <p class="text-base text-slate-600">
                Ürün, sipariş ve entegrasyon süreçlerini hızlandır. Abonelik paketine göre ölçeklenebilir altyapı ile
                işini büyüt.
            </p>
            <div class="flex flex-col sm:flex-row gap-3">
                @auth
                    <a href="{{ route('portal.dashboard') }}" class="btn btn-solid-brand">
                        Panelem
                    </a>
                @else
                    <a href="{{ route('register') }}" class="btn btn-solid-brand">
                        Ücretsiz Başla
                    </a>
                @endauth
                <a href="{{ route('pricing') }}" class="btn btn-outline-brand">
                    Paketleri İncele
                </a>
            </div>
            <div class="flex items-center gap-4 text-xs text-slate-500">
                <span class="bg-amber-200 text-amber-900 px-2 py-1 rounded-full">Kredi kartı gerektirmez</span>
                <span>Kurulum 5 dk</span>
            </div>
        </div>
        <div class="relative">
            <div class="glass rounded-3xl p-6 shadow-xl">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-700">Canlı Panel Önizleme</p>
                    <span class="text-xs text-teal-700 font-semibold">Güncel</span>
                </div>
                <div class="mt-6 grid grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <p class="text-xs text-slate-500">Bugünkü Sipariş</p>
                        <p class="text-xl font-semibold text-slate-900">128</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <p class="text-xs text-slate-500">Aktif Ürün</p>
                        <p class="text-xl font-semibold text-slate-900">1.240</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <p class="text-xs text-slate-500">Bağlı Mağaza</p>
                        <p class="text-xl font-semibold text-slate-900">5</p>
                    </div>
                    <div class="bg-white rounded-2xl p-4 shadow-sm">
                        <p class="text-xs text-slate-500">Aylık Gelir</p>
                        <p class="text-xl font-semibold text-slate-900">₺ 142.300</p>
                    </div>
                </div>
            </div>
            <div class="absolute -z-10 -top-6 -right-6 w-32 h-32 rounded-full bg-amber-300/60 blur-2xl"></div>
        </div>
    </section>

    <section class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass rounded-2xl p-5">
            <p class="text-sm font-semibold text-slate-800">Akıllı Ürün Yönetimi</p>
            <p class="text-sm text-slate-600 mt-2">Ürün kataloglarını tek seferde düzenle ve tüm kanallara dağıt.</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm font-semibold text-slate-800">Sipariş Senkronu</p>
            <p class="text-sm text-slate-600 mt-2">Sipariş akışını anlık takip et, operasyonlarını hızlandır.</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <p class="text-sm font-semibold text-slate-800">Ölçeklenebilir Paketler</p>
            <p class="text-sm text-slate-600 mt-2">İş hacmine uygun paketlerle gereksiz maliyeti azalt.</p>
        </div>
    </section>

    <section class="mt-16">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-semibold text-slate-900">Popüler Paketler</h2>
            <a href="{{ route('pricing') }}" class="text-sm text-teal-700 font-semibold">Tüm Paketler</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($plans as $plan)
                <div class="glass rounded-2xl p-6">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Paket</p>
                    <p class="text-lg font-semibold text-slate-800 mt-2">{{ $plan->name }}</p>
                    <p class="text-3xl font-semibold text-slate-900 mt-4">{{ number_format($plan->price, 2) }} ₺</p>
                    <p class="text-xs text-slate-500">Aylık</p>
                    <ul class="text-sm text-slate-600 mt-4 space-y-2">
                        <li>Ürün: {{ $plan->max_products === 0 ? 'Sınırsız' : $plan->max_products }}</li>
                        <li>Pazaryeri: {{ $plan->max_marketplaces === 0 ? 'Sınırsız' : $plan->max_marketplaces }}</li>
                        <li>Sipariş: {{ $plan->max_orders_per_month === 0 ? 'Sınırsız' : $plan->max_orders_per_month }}</li>
                    </ul>
                    @auth
                        <form method="POST" action="{{ route('subscribe', $plan) }}" class="mt-5">
                            @csrf
                            <button type="submit" class="btn btn-solid-brand w-full">
                                Paketi Seç
                            </button>
                        </form>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-solid-brand mt-5 w-full">
                            Paketi Seç
                        </a>
                    @endauth
                </div>
            @empty
                <div class="glass rounded-2xl p-6">
                    <p class="text-sm text-slate-600">Henüz paket tanımlanmadı.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection


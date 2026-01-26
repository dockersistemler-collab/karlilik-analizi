@extends('layouts.public')

@section('content')
    <section class="mt-4">
        <div class="text-center max-w-2xl mx-auto">
            <p class="text-xs uppercase tracking-[0.3em] text-teal-700 font-semibold">Fiyatlandırma</p>
            <h1 class="text-3xl md:text-4xl font-semibold text-slate-900 mt-3">İşine uygun paketi seç</h1>
            <p class="text-sm text-slate-600 mt-3">
                Paketler ürün, pazaryeri ve sipariş limitlerine göre şekillenir. İhtiyacına göre yükseltebilirsin.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10">
            @forelse($plans as $plan)
                <div class="glass rounded-2xl p-6 flex flex-col">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-800">{{ $plan->name }}</p>
                        <span class="text-xs text-slate-500">{{ $plan->billing_period === 'yearly' ? 'Yıllık' : 'Aylık' }}</span>
                    </div>
                    <p class="text-3xl font-semibold text-slate-900 mt-4">{{ number_format($plan->price, 2) }} ₺</p>
                    <p class="text-xs text-slate-500">Paket ücreti</p>

                    <ul class="text-sm text-slate-600 mt-4 space-y-2">
                        <li>Ürün Limiti: {{ $plan->max_products === 0 ? 'Sınırsız' : $plan->max_products }}</li>
                        <li>Pazaryeri Limiti: {{ $plan->max_marketplaces === 0 ? 'Sınırsız' : $plan->max_marketplaces }}</li>
                        <li>Sipariş Limiti: {{ $plan->max_orders_per_month === 0 ? 'Sınırsız' : $plan->max_orders_per_month }}</li>
                        <li>API Erişimi: {{ $plan->api_access ? 'Var' : 'Yok' }}</li>
                        <li>Gelişmiş Raporlar: {{ $plan->advanced_reports ? 'Var' : 'Yok' }}</li>
                        <li>Öncelikli Destek: {{ $plan->priority_support ? 'Var' : 'Yok' }}</li>
                    </ul>

                    @auth
                        <form method="POST" action="{{ route('subscribe', $plan) }}" class="mt-6">
                            @csrf
                            <button type="submit" class="btn btn-solid-brand w-full">
                                Paketi Seç
                            </button>
                        </form>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-solid-brand mt-6 w-full">
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

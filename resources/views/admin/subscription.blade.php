@extends('layouts.admin')

@section('header')
    Aboneliğim
@endsection

@section('content')
    <div class="panel-card p-6 max-w-3xl">
        @if($subscription && $subscription->isActive())
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-500">Aktif Paket</p>
                    <p class="text-lg font-semibold text-gray-800">{{ $subscription->plan?->name }}</p>
                    <p class="text-sm text-gray-500 mt-1">
                        Bitiş: {{ $subscription->ends_at?->format('d.m.Y') }}
                    </p>
                </div>
                <span class="px-3 py-1 text-xs rounded bg-green-100 text-green-700">Aktif</span>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-600">
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-xs text-slate-500">Ürün Kullanımı</p>
                    <p class="text-base font-semibold text-slate-800">
                        {{ $subscription->current_products_count }} / {{ $subscription->plan?->max_products === 0 ? 'Sınırsız' : $subscription->plan?->max_products }}
                    </p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-xs text-slate-500">Pazaryeri Kullanımı</p>
                    <p class="text-base font-semibold text-slate-800">
                        {{ $subscription->current_marketplaces_count }} / {{ $subscription->plan?->max_marketplaces === 0 ? 'Sınırsız' : $subscription->plan?->max_marketplaces }}
                    </p>
                </div>
                <div class="bg-slate-50 rounded-xl p-4">
                    <p class="text-xs text-slate-500">Sipariş Kullanımı</p>
                    <p class="text-base font-semibold text-slate-800">
                        {{ $subscription->current_month_orders_count }} / {{ $subscription->plan?->max_orders_per_month === 0 ? 'Sınırsız' : $subscription->plan?->max_orders_per_month }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('pricing') }}" class="btn btn-solid-accent">
                    Paketi Değiştir
                </a>
                <a href="{{ route('portal.subscription.history') }}" class="btn btn-outline-accent">
                    Abonelik Geçmişi
                </a>
                <form method="POST" action="{{ route('subscription.cancel') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-accent" onclick="return confirm('Aboneliği iptal etmek istediğinize emin misiniz?')">
                        Aboneliği İptal Et
                    </button>
                </form>
            </div>
        @else
            <div>
                <p class="text-sm text-gray-600">
                    Aktif aboneliğiniz yok. Paketleri görmek için fiyatlandırma sayfasını ziyaret edin.
                </p>
                <a href="{{ route('pricing') }}" class="inline-block mt-4 btn btn-solid-accent">
                    Paketleri Gör
                </a>
                @if($subscription)
                    <form method="POST" action="{{ route('subscription.renew') }}" class="inline-block mt-4">
                        @csrf
                        <button type="submit" class="btn btn-solid-accent">
                            Son Paketi Yenile
                        </button>
                    </form>
                @endif
            </div>
        @endif
    </div>
@endsection


@extends('layouts.admin')

@section('header', 'Kullanici Paneli')

@section('content')
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 panel-card p-6">
            <h3 class="text-2xl font-semibold text-slate-900">Hesabina hos geldin</h3>
            <p class="mt-2 text-sm text-slate-600">
                Satis, abonelik ve fatura bilgilerini bu panelden takip edebilirsin.
            </p>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="{{ route('portal.billing') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 hover:bg-slate-100">
                    Odeme ve Abonelik
                </a>
                <a href="{{ route('portal.invoices.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 hover:bg-slate-100">
                    Faturalar
                </a>
                <a href="{{ route('portal.help.support') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 hover:bg-slate-100">
                    Destek Talepleri
                </a>
                <a href="{{ route('portal.settings.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700 hover:bg-slate-100">
                    Hesap Ayarlari
                </a>
            </div>
        </div>

        <div class="panel-card p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Durum Ozeti</h3>
            @php
                $badgeClass = match ($portalBilling['badge'] ?? 'unknown') {
                    'active' => 'badge badge-success',
                    'past_due' => 'badge badge-warning',
                    'canceled' => 'badge badge-danger',
                    default => 'badge badge-muted',
                };
                $statusLabel = match ($portalBilling['badge'] ?? 'unknown') {
                    'active' => 'active',
                    'past_due' => 'past_due',
                    'canceled' => 'canceled',
                    default => 'unknown',
                };
            @endphp
            <div class="flex items-center justify-between">
                <span class="text-sm text-slate-600">Abonelik</span>
                <span class="{{ $badgeClass }}">{{ $statusLabel }}</span>
            </div>
            <div class="mt-4 text-xs text-slate-500 space-y-1">
                <div>
                    Sonraki deneme:
                    <span class="font-medium text-slate-700">
                        {{ !empty($portalBilling['next_retry_at']) ? optional($portalBilling['next_retry_at'])->format('d.m.Y H:i') : '-' }}
                    </span>
                </div>
                <div>
                    Son hata:
                    <span class="font-medium text-slate-700">{{ $portalBilling['last_failure_message'] ?? '-' }}</span>
                </div>
            </div>
            @if(($portalBilling['is_past_due'] ?? false))
                <a href="{{ route('portal.billing.card-update') }}" class="btn btn-solid-accent mt-5 w-full">
                    Kart Guncelle
                </a>
            @endif
        </div>
    </div>
@endsection

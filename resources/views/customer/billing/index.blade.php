@extends('layouts.admin')



@section('header', 'Ödeme & Abonelik')



@section('content')

    <div class="panel-card p-4 mb-4 text-sm text-slate-600">

        Ödeme ve abonelik detaylarınızı bu sayfadan takip edebilirsiniz.

    </div>

    @if(session('error'))

        <div class="panel-card px-4 py-3 mb-4 border-rose-200 text-rose-700 bg-rose-50/60">

            {{ session('error') }}

            <div class="text-xs text-rose-600 mt-1">

                Destek icin <a class="underline" href="{{ route('portal.support') }}">iletisim</a> sayfasini kullanabilirsiniz.

            </div>

        </div>

    @endif

    @if(session('success'))

        <div class="panel-card px-4 py-3 mb-4 border-green-200 text-green-700 bg-green-50/60">

            {{ session('success') }}

        </div>

    @endif



    <div class="panel-card p-6 max-w-4xl space-y-6">

        <div class="flex flex-wrap items-center gap-3">

            <h3 class="text-lg font-semibold text-slate-800">Abonelik Durumu</h3>

            @php

                $badgeClass = match ($badge ?? 'unknown') {

                    'active' => 'badge badge-success',

                    'past_due' => 'badge badge-warning',

                    'canceled' => 'badge badge-danger',

                    default => 'badge badge-muted',

                };

                $statusLabel = match ($badge ?? 'unknown') {

                    'active' => 'active',

                    'past_due' => 'past_due',

                    'canceled' => 'canceled',

                    default => 'unknown',

                };

            @endphp

            <span class="{{ $badgeClass }}">{{ $statusLabel }}</span>

        </div>



        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-600">

            <div class="panel-card p-4">

                <div class="text-xs uppercase tracking-wide text-slate-400">Paket Bilgisi</div>

                <div class="text-slate-800 font-semibold mt-1">

                    {{ $subscription?->plan_code ? strtoupper($subscription->plan_code) : 'Paket bilgisi yok' }}

                </div>

                <div class="text-xs text-slate-500 mt-2">Durum: {{ $statusLabel }}</div>

            </div>

            <div class="panel-card p-4">

                <div class="text-xs uppercase tracking-wide text-slate-400">Ödeme Yöntemi</div>

                <div class="text-slate-800 font-semibold mt-1">

                    {{ $maskedCard ?? 'Kart bilgisi bulunamadi' }}

                </div>

                <div class="mt-3">

                    <a href="{{ route('portal.billing.card-update') }}" class="btn btn-outline-accent">Ödeme yöntemini güncelle</a>

                </div>

            </div>

            <div class="panel-card p-4">

                <div class="text-xs uppercase tracking-wide text-slate-400">Faturalar</div>

                <div class="text-slate-800 font-semibold mt-1">Fatura geçmişi</div>

                <div class="mt-3">

                    <a href="{{ route('portal.invoices.index') }}" class="btn btn-outline">Faturaları görüntüle</a>

                </div>

            </div>

        </div>



        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-600">

            <div class="panel-card p-4">

                <div class="text-xs uppercase tracking-wide text-slate-400">Sonraki Deneme</div>

                <div class="text-slate-800 font-semibold mt-1">

                    {{ $nextRetryAt ? $nextRetryAt->format('d.m.Y H:i') : '-' }}

                </div>

                @if($isPastDue)

                    <div class="text-xs text-amber-700 mt-2">Ödeme tekrar denenecek.</div>

                @endif

            </div>

            <div class="panel-card p-4">

                <div class="text-xs uppercase tracking-wide text-slate-400">Son Hata</div>

                <div class="text-slate-700 mt-1">

                    {{ $lastFailureMessage ?: '-' }}

                </div>

                <div class="mt-3">

                    <a href="{{ route('portal.support') }}" class="text-xs text-slate-500 underline">Destek iletisim</a>

                </div>

            </div>

        </div>

    </div>

@endsection





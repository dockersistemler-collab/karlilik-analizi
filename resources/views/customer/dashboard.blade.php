@extends('layouts.admin')



@section('header', 'Genel Bakış')



@section('content')

    <div class="panel-card p-6 space-y-4">

        <div class="flex flex-wrap items-center gap-3">

            <h3 class="text-lg font-semibold text-slate-800">Abonelik Durumu</h3>

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

            <span class="{{ $badgeClass }}">{{ $statusLabel }}</span>

        </div>



        @if(($portalBilling['is_past_due'] ?? false))

            <div class="panel-card px-4 py-3 border-amber-200 text-amber-800 bg-amber-50/60">

                Odeme alinamadi. Kart guncellemesi yaparak tekrar denenmesini saglayabilirsiniz.

                <div class="mt-2">

                    <a href="{{ route('portal.billing.card-update') }}" class="btn btn-outline-accent">Kart Guncelle</a>

                </div>

            </div>

        @endif



        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-600">

            <div>

                <div class="text-xs uppercase tracking-wide text-slate-400">Sonraki Deneme</div>

                <div class="text-slate-800 font-semibold">

                    {{ !empty($portalBilling['next_retry_at']) ? optional($portalBilling['next_retry_at'])->format('d.m.Y H:i') : '-' }}

                </div>

            </div>

            <div>

                <div class="text-xs uppercase tracking-wide text-slate-400">Son Hata</div>

                <div class="text-slate-700">

                    {{ $portalBilling['last_failure_message'] ?? '-' }}

                </div>

            </div>

        </div>



        <div class="flex flex-wrap items-center gap-3">

            <a href="{{ route('portal.billing') }}" class="btn btn-outline">Ödeme & Abonelik</a>

            <a href="{{ route('portal.invoices.index') }}" class="btn btn-outline">Faturalar</a>

            <a href="{{ route('portal.support') }}" class="btn btn-outline">Destek</a>

        </div>

    </div>

@endsection





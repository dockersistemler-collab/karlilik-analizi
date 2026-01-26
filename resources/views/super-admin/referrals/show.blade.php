@extends('layouts.super-admin')

@section('header')
    Tavsiye Detayı
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow p-6 max-w-4xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-xs uppercase text-slate-400">Referans #{{ $referral->id }}</p>
                <h3 class="text-sm font-semibold text-slate-800 mt-1">{{ $referral->program?->name ?? 'Program yok' }}</h3>
                <p class="text-sm text-slate-600">Durum: {{ $referral->status }}</p>
            </div>
            <div class="text-sm text-slate-600">
                Oluşturulma: {{ optional($referral->created_at)->format('d.m.Y H:i') }}
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="panel-card p-4">
                <p class="text-xs uppercase text-slate-400">Tavsiye Eden</p>
                <p class="text-sm font-semibold text-slate-900 mt-1">{{ $referral->referrer?->name ?? '-' }}</p>
                <p class="text-xs text-slate-500">ID: {{ $referral->referrer_id }}</p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs uppercase text-slate-400">Davet Edilen</p>
                <p class="text-sm font-semibold text-slate-900 mt-1">{{ $referral->referredUser?->name ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $referral->referred_email ?? '-' }}</p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs uppercase text-slate-400">Tavsiye Eden Ödülü</p>
                <p class="text-sm font-semibold text-slate-900 mt-1">
                    {{ $referral->referrer_reward_type === 'percent' ? '%'.$referral->referrer_reward_value.' indirim' : ($referral->referrer_reward_value ? $referral->referrer_reward_value.' ay kullanım' : '-') }}
                </p>
                <p class="text-xs text-slate-500">
                    Kullanıldı: {{ $referral->referrer_discount_consumed_at ? 'Evet' : 'Hayır' }}
                </p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs uppercase text-slate-400">Tavsiye Alan Ödülü</p>
                <p class="text-sm font-semibold text-slate-900 mt-1">
                    {{ $referral->referred_reward_type === 'percent' ? '%'.$referral->referred_reward_value.' indirim' : ($referral->referred_reward_value ? $referral->referred_reward_value.' ay kullanım' : '-') }}
                </p>
                <p class="text-xs text-slate-500">
                    İndirim: {{ $referral->applied_discount_amount ? $referral->applied_discount_amount.' TRY' : '-' }}
                </p>
            </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <a href="{{ route('super-admin.referrals.index') }}" class="btn btn-outline-accent">
                Listeye Dön
            </a>
        </div>
    </div>
@endsection

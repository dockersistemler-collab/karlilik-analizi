@extends('layouts.admin')

@section('header')
    BuyBox Scoring Profiles
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-3 flex gap-2">
        <a href="{{ route('portal.buybox.index') }}" class="btn btn-outline">Snapshots</a>
        <a href="{{ route('portal.buybox.scores') }}" class="btn btn-outline">Scores</a>
        <a href="{{ route('portal.buybox.profiles') }}" class="btn btn-outline">Profiles</a>
    </div>

    <div class="panel-card p-4">
        <h3 class="text-sm font-semibold mb-3">Yeni Profil</h3>
        <form method="POST" action="{{ route('portal.buybox.profiles.store') }}" class="space-y-2">
            @csrf
            <select name="marketplace" class="border border-slate-200 rounded px-3 py-2" required>
                @foreach(['trendyol','hepsiburada','amazon','n11'] as $market)
                    <option value="{{ $market }}">{{ strtoupper($market) }}</option>
                @endforeach
            </select>
            <textarea name="weights" rows="3" class="w-full border border-slate-200 rounded px-3 py-2" required>{"price_competitiveness":35,"store_score":25,"shipping_speed":20,"stock":10,"promo":10}</textarea>
            <textarea name="thresholds" rows="2" class="w-full border border-slate-200 rounded px-3 py-2" required>{"risky":60}</textarea>
            <button class="btn btn-solid-accent">Kaydet</button>
        </form>
    </div>

    <div class="panel-card p-4">
        <h3 class="text-sm font-semibold mb-3">Mevcut Profiller</h3>
        <div class="space-y-3">
            @forelse($profiles as $profile)
                <form method="POST" action="{{ route('portal.buybox.profiles.update', $profile) }}" class="border border-slate-200 rounded p-3 space-y-2">
                    @csrf
                    @method('PUT')
                    <div class="text-xs text-slate-500 uppercase">{{ $profile->marketplace }}</div>
                    <textarea name="weights" rows="3" class="w-full border border-slate-200 rounded px-3 py-2" required>{{ json_encode($profile->weights, JSON_UNESCAPED_UNICODE) }}</textarea>
                    <textarea name="thresholds" rows="2" class="w-full border border-slate-200 rounded px-3 py-2" required>{{ json_encode($profile->thresholds, JSON_UNESCAPED_UNICODE) }}</textarea>
                    <button class="btn btn-outline-accent">Guncelle</button>
                </form>
            @empty
                <div class="text-sm text-slate-500">Profil yok.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

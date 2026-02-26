@extends('layouts.super-admin')

@section('header')
    Action Kurallari
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-lg font-semibold text-slate-900">{{ $module->name }}</div>
            <div class="text-sm text-slate-500">Kod: {{ $module->code }}</div>
        </div>
        <form method="POST" action="{{ route('super-admin.intelligence.modules.toggle', ['code' => $module->code]) }}">
            @csrf
            <button class="btn {{ $module->is_active ? 'btn-outline' : 'btn-solid-accent' }}">{{ $module->is_active ? 'Pasif Yap' : 'Aktif Et' }}</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Toplam Oneri</div><div class="text-2xl font-semibold">{{ number_format($stats['recommendations']) }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Open Oneri</div><div class="text-2xl font-semibold text-rose-600">{{ number_format($stats['open']) }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Aktif Musteri</div><div class="text-2xl font-semibold">{{ number_format($stats['active_clients']) }}</div></div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="panel-card p-5">
            <h3 class="font-semibold mb-3">Rule Set</h3>
            <ul class="text-sm text-slate-700 space-y-2">
                <li>CRITICAL risk + negatif net kar => PRICE_INCREASE veya LISTING_SUSPEND</li>
                <li>late_shipment driver => SHIPPING_SLA_FIX</li>
                <li>return_rate driver + dusuk marj => RULE_REVIEW / CUSTOMER_SUPPORT</li>
                <li>amazon odr driver => CUSTOMER_SUPPORT</li>
            </ul>
            <h4 class="font-semibold mt-4 mb-2">Action Type Dagilimi</h4>
            <div class="space-y-2">
                @forelse($byActionType as $row)
                    <div class="flex justify-between text-sm border border-slate-200 rounded px-3 py-2">
                        <span>{{ $row->action_type }}</span>
                        <strong>{{ $row->total }}</strong>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Kayit yok.</p>
                @endforelse
            </div>
        </div>

        <div class="panel-card p-5">
            <h3 class="font-semibold mb-3">Son Calisma Kayitlari</h3>
            <div class="space-y-2 max-h-[420px] overflow-auto">
                @forelse($latestRuns as $run)
                    <div class="border border-slate-200 rounded p-3 text-sm">
                        <div class="font-medium">{{ $run->run_date->format('d.m.Y') }}</div>
                        <div>Generated: {{ data_get($run->stats, 'generated', 0) }}</div>
                        <div>Updated: {{ data_get($run->stats, 'updated', 0) }}</div>
                        <div>Skipped: {{ data_get($run->stats, 'skipped', 0) }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Run kaydi yok.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

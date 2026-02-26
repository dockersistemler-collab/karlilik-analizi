@extends('layouts.admin')

@section('header')
    Decision Center
@endsection

@section('content')
<div class="space-y-6">
    @include('admin.decision-center.partials.nav', ['active' => 'center'])

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Net Kar (Donem)</div>
            <div class="text-2xl font-semibold {{ $global['net_profit_total'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ number_format($global['net_profit_total'], 2) }} TRY
            </div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Ortalama Risk</div>
            <div class="text-2xl font-semibold {{ $global['avg_risk_score'] >= 70 ? 'text-rose-600' : ($global['avg_risk_score'] >= 45 ? 'text-amber-600' : 'text-emerald-600') }}">
                {{ number_format($global['avg_risk_score'], 2) }}
            </div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Open Oneri</div>
            <div class="text-2xl font-semibold">{{ $global['open_actions'] }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Kritik Risk Kaydi</div>
            <div class="text-2xl font-semibold text-rose-600">{{ $global['critical_risk_count'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @forelse($ctaCards as $card)
            <div class="panel-card p-4 border {{ $card['tone'] === 'critical' ? 'border-rose-200' : ($card['tone'] === 'warning' ? 'border-amber-200' : 'border-sky-200') }}">
                <div class="text-sm font-semibold text-slate-900">{{ $card['title'] }}</div>
                <div class="text-sm text-slate-600 mt-1">{{ $card['text'] }}</div>
                <a href="{{ $card['href'] }}" class="btn btn-solid-accent mt-3">{{ $card['button'] }}</a>
            </div>
        @empty
            <div class="panel-card p-4 md:col-span-3 text-sm text-slate-500">Secili filtrelerde acil aksiyon bulunmuyor.</div>
        @endforelse
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="panel-card p-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold">Profit Ozeti</h3>
                @if($hasProfitModule)
                    <a href="{{ route('portal.profit-engine.index', request()->only('marketplace')) }}" class="btn btn-outline-accent">Detay</a>
                @endif
            </div>
            @if(!$hasProfitModule)
                <p class="text-sm text-slate-500 mt-2">Profit Engine aktif degil.</p>
                <a href="{{ route('portal.modules.upsell', ['code' => 'profit_engine']) }}" class="btn btn-outline mt-3">Modulu Aktif Et</a>
            @else
                <div class="mt-2 text-sm text-slate-600">Ort. Marj: <strong>%{{ number_format((float) $profit['avg_margin'], 2) }}</strong></div>
                <div class="mt-1 text-sm text-slate-600">Eksik Kural: <strong>{{ $profit['missing_rule_count'] }}</strong></div>
                <div class="mt-1 text-sm text-slate-600">Eksik Maliyet: <strong>{{ $profit['missing_cost_count'] }}</strong></div>
                <div class="mt-3 space-y-2">
                    @forelse($profit['top_negative_orders'] as $row)
                        <div class="text-xs border border-slate-200 rounded p-2">
                            <div class="font-medium">{{ $row->order?->order_number ?? ('#'.$row->order_id) }} ({{ strtoupper($row->marketplace) }})</div>
                            <div class="text-rose-600">Net: {{ number_format((float) $row->net_profit, 2) }} TRY</div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">Kayit yok.</p>
                    @endforelse
                </div>
            @endif
        </div>

        <div class="panel-card p-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold">Risk Ozeti</h3>
                @if($hasRiskModule)
                    <a href="{{ route('portal.marketplace-risk.index', request()->only('marketplace')) }}" class="btn btn-outline-accent">Detay</a>
                @endif
            </div>
            @if(!$hasRiskModule)
                <p class="text-sm text-slate-500 mt-2">Marketplace Risk aktif degil.</p>
                <a href="{{ route('portal.modules.upsell', ['code' => 'marketplace_risk']) }}" class="btn btn-outline mt-3">Modulu Aktif Et</a>
            @else
                <div class="mt-2 text-sm text-slate-600">Warning: <strong>{{ $risk['warning_count'] }}</strong></div>
                <div class="mt-1 text-sm text-slate-600">Critical: <strong class="text-rose-600">{{ $risk['critical_count'] }}</strong></div>
                <div class="mt-3 text-xs text-slate-500">Top Drivers</div>
                <div class="mt-1 flex flex-wrap gap-2">
                    @forelse($risk['top_drivers'] as $metric => $count)
                        <span class="inline-flex px-2 py-1 rounded bg-amber-50 text-amber-700 border border-amber-200 text-xs">{{ $metric }} ({{ $count }})</span>
                    @empty
                        <span class="text-xs text-slate-500">Driver bulunmuyor.</span>
                    @endforelse
                </div>
            @endif
        </div>

        <div class="panel-card p-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold">Action Ozeti</h3>
                @if($hasActionModule)
                    <a href="{{ route('portal.action-engine.index', request()->only('marketplace', 'date_from', 'date_to')) }}" class="btn btn-outline-accent">Detay</a>
                @endif
            </div>
            @if(!$hasActionModule)
                <p class="text-sm text-slate-500 mt-2">Action Engine aktif degil.</p>
                <a href="{{ route('portal.modules.upsell', ['code' => 'action_engine']) }}" class="btn btn-outline mt-3">Modulu Aktif Et</a>
            @else
                <div class="mt-2 text-sm text-slate-600">Open: <strong>{{ $action['open'] }}</strong></div>
                <div class="mt-1 text-sm text-slate-600">Applied: <strong>{{ $action['applied'] }}</strong></div>
                <div class="mt-1 text-sm text-slate-600">Dismissed: <strong>{{ $action['dismissed'] }}</strong></div>
                <div class="mt-1 text-sm text-slate-600">High Open: <strong class="text-rose-600">{{ $action['high_open'] }}</strong></div>
                <div class="mt-3 space-y-2">
                    @forelse($action['latest_open'] as $row)
                        <div class="text-xs border border-slate-200 rounded p-2">
                            <div class="font-medium">{{ $row->title }}</div>
                            <div>{{ $row->action_type }} / {{ strtoupper($row->severity) }}</div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500">Open oneriler yok.</p>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

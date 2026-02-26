@extends('layouts.admin')

@section('header')
    BuyBox SKU Detay
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-3 flex gap-2">
        <a href="{{ route('portal.buybox.index') }}" class="btn btn-outline">Snapshots</a>
        <a href="{{ route('portal.buybox.scores') }}" class="btn btn-outline">Scores</a>
        <a href="{{ route('portal.buybox.profiles') }}" class="btn btn-outline">Profiles</a>
    </div>

    <div class="panel-card p-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-xs text-slate-500 uppercase">{{ $marketplace }}</div>
            <div class="text-lg font-semibold">{{ $sku }}</div>
        </div>
        @if($latest)
            <form method="POST" action="{{ route('portal.buybox.suggest-actions') }}" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="date" value="{{ optional($latest->date)->toDateString() }}">
                <input type="hidden" name="marketplace" value="{{ $marketplace }}">
                <input type="hidden" name="sku" value="{{ $sku }}">
                <button class="btn btn-solid-accent">Aksiyon Oner (Son Gun)</button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Son BuyBox Score</div>
            <div class="text-2xl font-semibold">{{ $latest?->buybox_score ?? '-' }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Son Status</div>
            <div class="text-2xl font-semibold uppercase">{{ $latest?->status ?? '-' }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Son Store Score</div>
            <div class="text-2xl font-semibold">{{ $latest?->snapshot?->store_score ?? '-' }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Son Price Gap</div>
            @php
                $latestOur = $latest?->snapshot?->our_price !== null ? (float) $latest->snapshot->our_price : null;
                $latestBest = $latest?->snapshot?->competitor_best_price !== null ? (float) $latest->snapshot->competitor_best_price : null;
                $latestGap = ($latestOur !== null && $latestBest !== null && $latestBest > 0) ? (($latestOur - $latestBest) / $latestBest) * 100 : null;
            @endphp
            <div class="text-2xl font-semibold">{{ $latestGap !== null ? number_format($latestGap, 2).'%' : '-' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Etki Kaydi (SKU)</div>
            <div class="text-2xl font-semibold">{{ (int) ($impactSummary['recommendation_count'] ?? 0) }}</div>
            <div class="text-xs text-slate-500 mt-1">Acik: {{ (int) ($impactSummary['open_count'] ?? 0) }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Ort. Win Probability Delta</div>
            <div class="text-2xl font-semibold">
                {{ ($impactSummary['avg_win_probability_delta_pp'] ?? null) !== null ? number_format((float) $impactSummary['avg_win_probability_delta_pp'], 2).' pp' : '-' }}
            </div>
            <div class="text-xs text-slate-500 mt-1">n={{ (int) ($impactSummary['win_probability_samples'] ?? 0) }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Toplam Net Kar Delta</div>
            <div class="text-2xl font-semibold">
                {{ ($impactSummary['sum_net_profit_delta'] ?? null) !== null ? number_format((float) $impactSummary['sum_net_profit_delta'], 2) : '-' }}
            </div>
            <div class="text-xs text-slate-500 mt-1">n={{ (int) ($impactSummary['net_profit_samples'] ?? 0) }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Ort. Confidence</div>
            <div class="text-2xl font-semibold">
                {{ ($impactSummary['avg_confidence'] ?? null) !== null ? number_format((float) $impactSummary['avg_confidence'], 2) : '-' }}
            </div>
        </div>
    </div>

    <div class="panel-card p-4">
        <h3 class="text-sm font-semibold mb-3">Trend (Son 30 Gun)</h3>
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="px-3 py-2">Tarih</th>
                        <th class="px-3 py-2">BuyBox Score</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Store Score</th>
                        <th class="px-3 py-2">Price Gap</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($trend->sortByDesc('date') as $row)
                    @php
                        $our = $row->snapshot?->our_price !== null ? (float) $row->snapshot->our_price : null;
                        $best = $row->snapshot?->competitor_best_price !== null ? (float) $row->snapshot->competitor_best_price : null;
                        $gap = ($our !== null && $best !== null && $best > 0) ? (($our - $best) / $best) * 100 : null;
                    @endphp
                    <tr class="border-b border-slate-100">
                        <td class="px-3 py-2">{{ optional($row->date)->format('d.m.Y') }}</td>
                        <td class="px-3 py-2 font-semibold">{{ $row->buybox_score }}</td>
                        <td class="px-3 py-2 uppercase">{{ $row->status }}</td>
                        <td class="px-3 py-2">{{ $row->snapshot?->store_score ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $gap !== null ? number_format($gap, 2).'%' : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Trend verisi yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel-card p-4">
        <h3 class="text-sm font-semibold mb-3">Aksiyon Onerileri</h3>
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="px-3 py-2">Tarih</th>
                        <th class="px-3 py-2">Type</th>
                        <th class="px-3 py-2">Severity</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Win Delta</th>
                        <th class="px-3 py-2">Net Kar Delta</th>
                        <th class="px-3 py-2">Baslik</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($recommendations as $rec)
                    @php
                        $winDelta = data_get($rec->impact?->delta, 'win_probability');
                        $netProfitDelta = data_get($rec->impact?->delta, 'net_profit');
                    @endphp
                    <tr class="border-b border-slate-100">
                        <td class="px-3 py-2">{{ optional($rec->date)->format('d.m.Y') }}</td>
                        <td class="px-3 py-2">{{ $rec->action_type }}</td>
                        <td class="px-3 py-2 uppercase">{{ $rec->severity }}</td>
                        <td class="px-3 py-2 uppercase">{{ $rec->status }}</td>
                        <td class="px-3 py-2">{{ is_numeric($winDelta) ? number_format(((float) $winDelta) * 100, 2).' pp' : '-' }}</td>
                        <td class="px-3 py-2">{{ is_numeric($netProfitDelta) ? number_format((float) $netProfitDelta, 2) : '-' }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('portal.action-engine.show', $rec) }}" class="text-blue-700 hover:underline">{{ $rec->title }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-4 text-center text-slate-500">Oneri yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

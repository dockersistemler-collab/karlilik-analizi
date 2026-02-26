@extends('layouts.admin')

@section('header')
    BuyBox Scores
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-3 flex gap-2">
        <a href="{{ route('portal.buybox.index') }}" class="btn btn-outline">Snapshots</a>
        <a href="{{ route('portal.buybox.scores') }}" class="btn btn-outline">Scores</a>
        <a href="{{ route('portal.buybox.profiles') }}" class="btn btn-outline">Profiles</a>
    </div>

    <div class="panel-card p-4">
        <form method="GET" action="{{ route('portal.buybox.scores') }}" class="grid grid-cols-1 md:grid-cols-6 gap-2">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-slate-200 rounded px-3 py-2">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-slate-200 rounded px-3 py-2">
            <select name="marketplace" class="border border-slate-200 rounded px-3 py-2">
                <option value="">Tum pazaryerleri</option>
                @foreach(['trendyol','hepsiburada','amazon','n11'] as $market)
                    <option value="{{ $market }}" @selected(request('marketplace') === $market)>{{ strtoupper($market) }}</option>
                @endforeach
            </select>
            <select name="status" class="border border-slate-200 rounded px-3 py-2">
                <option value="">Tum durumlar</option>
                @foreach(['winning','risky','losing'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ strtoupper($st) }}</option>
                @endforeach
            </select>
            <input type="number" step="0.01" name="min_store_score" value="{{ request('min_store_score') }}" placeholder="Min store score" class="border border-slate-200 rounded px-3 py-2">
            <div class="flex gap-2">
                <button class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('portal.buybox.scores') }}" class="btn btn-outline">Temizle</a>
            </div>
        </form>
        <form method="POST" action="{{ route('portal.buybox.calculate-scores') }}" class="mt-3 flex items-center gap-2">
            @csrf
            <input type="date" name="date" value="{{ request('date_to', now()->subDay()->toDateString()) }}" class="border border-slate-200 rounded px-3 py-2">
            <button class="btn btn-solid-accent">Score Hesapla (Job)</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Etki Kaydi</div>
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
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="px-3 py-2">Date</th>
                        <th class="px-3 py-2">Marketplace</th>
                        <th class="px-3 py-2">SKU</th>
                        <th class="px-3 py-2">Score</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Store Score</th>
                        <th class="px-3 py-2">Price Gap</th>
                        <th class="px-3 py-2">Drivers</th>
                        <th class="px-3 py-2">Aksiyon</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scores as $row)
                        @php
                            $our = $row->snapshot?->our_price !== null ? (float) $row->snapshot->our_price : null;
                            $best = $row->snapshot?->competitor_best_price !== null ? (float) $row->snapshot->competitor_best_price : null;
                            $gap = ($our !== null && $best !== null && $best > 0) ? (($our - $best) / $best) * 100 : null;
                        @endphp
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-2">{{ optional($row->date)->format('d.m.Y') }}</td>
                            <td class="px-3 py-2 uppercase">{{ $row->marketplace }}</td>
                            <td class="px-3 py-2">{{ $row->sku }}</td>
                            <td class="px-3 py-2 font-semibold">{{ $row->buybox_score }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex px-2 py-1 rounded text-xs {{ $row->status === 'winning' ? 'bg-emerald-100 text-emerald-700' : ($row->status === 'risky' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                    {{ strtoupper($row->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2">{{ $row->snapshot?->store_score ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $gap !== null ? number_format($gap, 2).'%' : '-' }}</td>
                            <td class="px-3 py-2 text-xs">
                                {{ collect((array) $row->drivers)->pluck('metric')->implode(', ') ?: '-' }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-2">
                                    <a
                                        href="{{ route('portal.buybox.detail', ['marketplace' => $row->marketplace, 'sku' => $row->sku]) }}"
                                        class="btn btn-outline px-2 py-1 text-xs"
                                    >Detay</a>
                                    <form method="POST" action="{{ route('portal.buybox.suggest-actions') }}">
                                        @csrf
                                        <input type="hidden" name="date" value="{{ optional($row->date)->toDateString() }}">
                                        <input type="hidden" name="marketplace" value="{{ $row->marketplace }}">
                                        <input type="hidden" name="sku" value="{{ $row->sku }}">
                                        <button class="btn btn-solid-accent px-2 py-1 text-xs">Aksiyon Oner</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-3 py-4 text-center text-slate-500">Kayit yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $scores->links() }}</div>
    </div>
</div>
@endsection

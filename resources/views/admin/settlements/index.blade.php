@extends('layouts.admin')

@section('header')
    Hakedi&#351; Kontrol Merkezi
@endsection

@section('content')
    @php
        $statusLabels = [
            'EXPECTED' => 'Beklenen',
            'PARTIAL_PAID' => 'K&#305;smi &Ouml;dendi',
            'PAID' => '&Ouml;dendi',
            'DISCREPANCY' => 'Sapmal&#305;',
            'IN_REVIEW' => '&#304;ncelemede',
            'ok' => 'Tamam',
            'warning' => 'Uyar&#305;',
            'mismatch' => 'Uyumsuz',
        ];
        $statusPill = [
            'EXPECTED' => 'bg-slate-100 text-slate-700',
            'PARTIAL_PAID' => 'bg-amber-100 text-amber-800',
            'PAID' => 'bg-emerald-100 text-emerald-800',
            'DISCREPANCY' => 'bg-rose-100 text-rose-800',
            'IN_REVIEW' => 'bg-indigo-100 text-indigo-800',
            'ok' => 'bg-emerald-100 text-emerald-800',
            'warning' => 'bg-amber-100 text-amber-800',
            'mismatch' => 'bg-rose-100 text-rose-800',
        ];
        $totalDiff = (float) ($summary['total_diff'] ?? 0);
    @endphp

    <div class="panel-card p-5 md:p-6 mb-4 overflow-hidden relative">
        <div class="absolute -top-14 -right-10 w-56 h-56 rounded-full bg-rose-100/70 blur-3xl"></div>
        <div class="absolute -bottom-16 left-10 w-56 h-56 rounded-full bg-orange-100/70 blur-3xl"></div>
        <div class="relative z-10 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 mb-2">
                    <span class="px-2 py-1 rounded-full bg-orange-100 text-orange-700">Finans Operasyonlar&#305;</span>
                    <span class="px-2 py-1 rounded-full bg-emerald-100 text-emerald-700">Canl&#305;</span>
                </div>
                <h2 class="text-2xl md:text-3xl font-black tracking-tight text-slate-900">Hakedi&#351; Kontrol Merkezi</h2>
                <p class="text-sm text-slate-600 mt-1">Sapma, gecikme ve dispute s&uuml;re&ccedil;lerini tek ak&#305;&#351;ta y&ouml;netin.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('portal.settlements.disputes') }}" class="btn btn-outline-accent">Dispute Merkezi</a>
                <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline">T&uuml;m Kay&#305;tlar</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
        <div class="panel-card p-4 border border-slate-200/80">
            <div class="text-xs text-slate-500 uppercase tracking-wide">A&ccedil;&#305;k Sapma Adedi</div>
            <div class="mt-2 text-2xl font-black text-slate-900">{{ (int) ($summary['open_disputes'] ?? 0) }}</div>
            <div class="text-xs text-slate-500 mt-1">Aksiyon bekleyen uyu&#351;mazl&#305;k</div>
        </div>
        <div class="panel-card p-4 border border-slate-200/80">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Toplam Fark (Net)</div>
            <div class="mt-2 text-2xl font-black {{ $totalDiff < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ number_format($totalDiff, 2, ',', '.') }} TRY</div>
            <div class="text-xs text-slate-500 mt-1">Beklenen - &Ouml;denen</div>
        </div>
        <div class="panel-card p-4 border border-slate-200/80">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Geciken Payout</div>
            <div class="mt-2 text-2xl font-black text-slate-900">{{ (int) ($summary['overdue_payouts'] ?? 0) }}</div>
            <div class="text-xs text-slate-500 mt-1">SLA kontrol listesi</div>
        </div>
        <div class="panel-card p-4 border border-slate-200/80">
            <div class="text-xs text-slate-500 uppercase tracking-wide">Son Reconcile</div>
            <div class="mt-2 text-lg font-bold text-slate-900 leading-tight">
                {{ !empty($summary['last_reconciled_at']) ? \Illuminate\Support\Carbon::parse($summary['last_reconciled_at'])->format('d.m.Y H:i') : '-' }}
            </div>
            <div class="text-xs text-slate-500 mt-1">Otomatik g&uuml;ncelleme</div>
        </div>
    </div>

    <div class="panel-card p-4 md:p-5 mb-4 border border-slate-200/80">
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">H&#305;zl&#305; Durumlar</span>
            @foreach($statusLabels as $code => $label)
                @php $count = (int) ($statusCounts[$code] ?? 0); @endphp
                @if($count > 0)
                    <a href="{{ route('portal.settlements.index', array_merge(request()->query(), ['status' => $code])) }}"
                       class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold border {{ $status === $code ? 'border-slate-300 bg-slate-100 text-slate-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                        <span>{!! $label !!}</span>
                        <span class="px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-700">{{ $count }}</span>
                    </a>
                @endif
            @endforeach
        </div>

        <form method="GET" action="{{ route('portal.settlements.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-end">
            <div class="md:col-span-4">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Referans / Pazaryeri Ara</label>
                <input type="text" name="q" value="{{ $q ?? '' }}" class="mt-1 w-full" placeholder="&Ouml;rn: payout-8200 veya Trendyol">
            </div>
            <div class="md:col-span-3">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Pazaryeri</label>
                <select name="marketplace" class="mt-1 w-full">
                    <option value="">T&uuml;m&uuml;</option>
                    @foreach(($marketplaces ?? collect()) as $market)
                        <option value="{{ $market }}" @selected(($marketplace ?? '') === $market)>{{ strtoupper((string) $market) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Durum</label>
                <select name="status" class="mt-1 w-full">
                    <option value="">T&uuml;m&uuml;</option>
                    @foreach($statusLabels as $code => $label)
                        <option value="{{ $code }}" @selected($status === $code)>{!! $label !!}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="btn btn-solid-accent flex-1">Filtrele</button>
                <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline">Temizle</a>
            </div>
        </form>
    </div>

    <div class="panel-card overflow-hidden border border-slate-200/80">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50/90 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold uppercase tracking-wide text-xs">Referans</th>
                    <th class="text-left px-4 py-3 font-semibold uppercase tracking-wide text-xs">Pazaryeri</th>
                    <th class="text-left px-4 py-3 font-semibold uppercase tracking-wide text-xs">D&ouml;nem</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-xs">Beklenen</th>
                    <th class="text-right px-4 py-3 font-semibold uppercase tracking-wide text-xs">&Ouml;denen</th>
                    <th class="text-left px-4 py-3 font-semibold uppercase tracking-wide text-xs">Durum</th>
                    <th class="text-left px-4 py-3 font-semibold uppercase tracking-wide text-xs"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($payouts as $payout)
                    <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-900">{{ $payout->payout_reference ?: '-' }}</div>
                            <div class="text-xs text-slate-500">{{ optional($payout->expected_date)->format('d.m.Y') ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $payout->integration?->name ?: strtoupper((string) $payout->marketplace) }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ optional($payout->period_start)->format('d.m.Y') }} - {{ optional($payout->period_end)->format('d.m.Y') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ number_format((float) $payout->expected_amount, 2, ',', '.') }} {{ $payout->currency }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ number_format((float) $payout->paid_amount, 2, ',', '.') }} {{ $payout->currency }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusPill[$payout->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {!! $statusLabels[$payout->status] ?? e($payout->status) !!}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('portal.settlements.show', $payout) }}" class="btn btn-outline">Detay</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center">
                            <div class="text-slate-700 font-semibold mb-1">Filtreye uygun kay&#305;t bulunamad&#305;</div>
                            <div class="text-slate-500 text-sm mb-3">Arama kelimesini veya durum filtresini temizleyip tekrar deneyin.</div>
                            <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline">Filtreleri S&#305;f&#305;rla</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $payouts->links() }}
    </div>
@endsection


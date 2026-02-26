@extends('layouts.admin')

@section('header')
    Hakedi&#351; Detay&#305;
@endsection

@section('content')
    @php
        $disputeTypeLabels = [
            'MISSING_PAYMENT' => 'Eksik &Ouml;deme',
            'COMMISSION_DIFF' => 'Komisyon Fark&#305;',
            'SHIPPING_DIFF' => 'Kargo Fark&#305;',
            'VAT_DIFF' => 'KDV Fark&#305;',
            'UNKNOWN_DEDUCTION' => 'Bilinmeyen Kesinti',
        ];
        $findings = collect($payout->reconciliations ?? [])->flatMap(function ($rec) {
            $items = is_array($rec->loss_findings_json) ? $rec->loss_findings_json : [];
            return collect($items)->map(function ($f) use ($rec) {
                $f['reconciliation_id'] = $rec->id;
                $f['match_key'] = $rec->match_key;
                return $f;
            });
        });
    @endphp

    <div class="panel-card p-5 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <div class="text-xs text-slate-500 uppercase tracking-wide">Payout Referans&#305;</div>
                <div class="text-xl font-black text-slate-900">{{ $payout->payout_reference ?: '-' }}</div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline">Listeye D&ouml;n</a>
                <form method="POST" action="{{ route('portal.settlements.reconcile', $payout->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-accent">Reconcile &Ccedil;al&#305;&#351;t&#305;r</button>
                </form>
                <a href="{{ route('portal.settlements.export', ['payout' => $payout->id, 'format' => 'csv']) }}" class="btn btn-outline">CSV</a>
                <a href="{{ route('portal.settlements.export', ['payout' => $payout->id, 'format' => 'xlsx']) }}" class="btn btn-outline">XLSX</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-4">
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Beklenen</div>
            <div class="text-lg font-bold text-slate-900">{{ number_format((float) $payout->expected_amount, 2, ',', '.') }} {{ $payout->currency }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">&Ouml;denen</div>
            <div class="text-lg font-bold text-slate-900">{{ number_format((float) $payout->paid_amount, 2, ',', '.') }} {{ $payout->currency }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Dispute Say&#305;s&#305;</div>
            <div class="text-lg font-bold text-slate-900">{{ $payout->disputes->count() }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Son Reconcile</div>
            <div class="text-base font-bold text-slate-900">{{ $payout->reconciliation?->reconciled_at?->format('d.m.Y H:i') ?: '-' }}</div>
        </div>
    </div>

    <div class="panel-card p-4 mb-4">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h3 class="font-semibold text-slate-900">Loss Findings</h3>
            <div class="text-xs text-slate-500">Uygun bulgular&#305; se&ccedil;ip tek t&#305;kla dispute olu&#351;turabilirsiniz.</div>
        </div>

        @if($findings->isNotEmpty())
            <form method="POST" action="{{ route('portal.settlements.disputes.from-findings', $payout->id) }}">
                @csrf
                <div class="mb-2">
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" id="select-all-findings">
                        T&uuml;m bulgular&#305; se&ccedil;
                    </label>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr>
                                <th class="text-left px-3 py-2"></th>
                                <th class="text-left px-3 py-2">Kod</th>
                                <th class="text-left px-3 py-2">A&ccedil;&#305;klama</th>
                                <th class="text-left px-3 py-2">Seviye</th>
                                <th class="text-right px-3 py-2">Tutar</th>
                                <th class="text-left px-3 py-2">&Ouml;nerilen Dispute</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($findings as $finding)
                                <tr class="border-t border-slate-100">
                                    <td class="px-3 py-2">
                                        <input type="checkbox" class="finding-check" name="reconciliation_ids[]" value="{{ $finding['reconciliation_id'] }}">
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $finding['code'] ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ $finding['detail'] ?? '-' }}</td>
                                    <td class="px-3 py-2">{{ strtoupper((string) ($finding['severity'] ?? '-')) }}</td>
                                    <td class="px-3 py-2 text-right">{{ number_format((float) ($finding['amount'] ?? 0), 2, ',', '.') }}</td>
                                    <td class="px-3 py-2">{{ $disputeTypeLabels[$finding['suggested_dispute_type'] ?? ''] ?? ($finding['suggested_dispute_type'] ?? '-') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-outline-accent">Se&ccedil;ilenlerden Dispute Olu&#351;tur</button>
                </div>
            </form>
        @else
            <div class="text-sm text-slate-500">Bu payout i&ccedil;in bulgu bulunamad&#305;.</div>
        @endif
    </div>

    <div class="panel-card p-4">
        <h3 class="font-semibold mb-3">&#304;lgili Dispute Kay&#305;tlar&#305;</h3>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-3 py-2">Tip</th>
                    <th class="text-right px-3 py-2">Tutar</th>
                    <th class="text-left px-3 py-2">Durum</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payout->disputes as $dispute)
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2">{{ $disputeTypeLabels[$dispute->dispute_type] ?? $dispute->dispute_type }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float) ($dispute->amount ?: $dispute->diff_amount), 2, ',', '.') }}</td>
                        <td class="px-3 py-2">{{ $dispute->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-3 py-6 text-center text-slate-500">Dispute kayd&#305; yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var selectAll = document.getElementById('select-all-findings');
            if (!selectAll) return;
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.finding-check').forEach(function (el) {
                    el.checked = selectAll.checked;
                });
            });
        });
    </script>
@endsection


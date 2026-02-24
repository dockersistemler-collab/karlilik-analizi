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
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3 mb-4">
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">A&ccedil;&#305;k Sapma Adedi</div>
            <div class="text-base font-semibold">{{ (int) ($summary['open_disputes'] ?? 0) }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Toplam Fark (Net)</div>
            <div class="text-base font-semibold">{{ number_format((float) ($summary['total_diff'] ?? 0), 2, ',', '.') }} TRY</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Geciken Payout</div>
            <div class="text-base font-semibold">{{ (int) ($summary['overdue_payouts'] ?? 0) }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Son Reconcile</div>
            <div class="text-base font-semibold">
                {{ !empty($summary['last_reconciled_at']) ? \Illuminate\Support\Carbon::parse($summary['last_reconciled_at'])->format('d.m.Y H:i') : '-' }}
            </div>
        </div>
    </div>

    <div class="panel-card p-5 mb-4">
        <form method="GET" action="{{ route('portal.settlements.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-slate-500">Durum</label>
                <select name="status" class="mt-1">
                    <option value="">T&uuml;m&uuml;</option>
                    @foreach($statusLabels as $code => $label)
                        <option value="{{ $code }}" @selected($status === $code)>{!! $label !!}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-outline">Filtrele</button>
            <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline">Temizle</a>
            <a href="{{ route('portal.settlements.disputes') }}" class="btn btn-outline-accent ml-auto">Dispute Merkezi</a>
        </form>
    </div>

    <div class="panel-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Referans</th>
                    <th class="text-left px-4 py-3">Pazaryeri</th>
                    <th class="text-left px-4 py-3">D&ouml;nem</th>
                    <th class="text-right px-4 py-3">Beklenen</th>
                    <th class="text-right px-4 py-3">&Ouml;denen</th>
                    <th class="text-left px-4 py-3">Durum</th>
                    <th class="text-left px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($payouts as $payout)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-semibold">{{ $payout->payout_reference ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $payout->integration?->name ?: strtoupper((string) $payout->marketplace) }}</td>
                        <td class="px-4 py-3">{{ optional($payout->period_start)->format('d.m.Y') }} - {{ optional($payout->period_end)->format('d.m.Y') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $payout->expected_amount, 2, ',', '.') }} {{ $payout->currency }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $payout->paid_amount, 2, ',', '.') }} {{ $payout->currency }}</td>
                        <td class="px-4 py-3">{!! $statusLabels[$payout->status] ?? e($payout->status) !!}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('portal.settlements.show', $payout) }}" class="btn btn-outline">Detay</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Kay&#305;t bulunamad&#305;.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $payouts->links() }}
    </div>
@endsection


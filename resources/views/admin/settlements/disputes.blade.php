@extends('layouts.admin')

@section('header')
    Dispute Merkezi
@endsection

@section('content')
    @php
        $statusLabels = [
            'open' => 'A&ccedil;&#305;k',
            'in_review' => '&#304;ncelemede',
            'resolved' => '&Ccedil;&ouml;z&uuml;ld&uuml;',
            'rejected' => 'Reddedildi',
            'OPEN' => 'A&ccedil;&#305;k',
            'IN_REVIEW' => '&#304;ncelemede',
            'SUBMITTED_TO_MARKETPLACE' => 'Pazaryerine &#304;letildi',
            'RESOLVED' => '&Ccedil;&ouml;z&uuml;ld&uuml;',
            'REJECTED' => 'Reddedildi',
        ];

        $disputeTypeLabels = [
            'MISSING_PAYMENT' => 'Eksik &Ouml;deme',
            'COMMISSION_DIFF' => 'Komisyon Fark&#305;',
            'SHIPPING_DIFF' => 'Kargo Fark&#305;',
            'VAT_DIFF' => 'KDV Fark&#305;',
            'UNKNOWN_DEDUCTION' => 'Bilinmeyen Kesinti',
        ];
    @endphp

    <div class="panel-card p-5 mb-4">
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Durum &Ouml;zeti</span>
            @foreach(['open', 'in_review', 'resolved', 'rejected'] as $code)
                @php $count = (int) (($statusCounts ?? collect())[$code] ?? 0); @endphp
                <a href="{{ route('portal.settlements.disputes', array_merge(request()->query(), ['status' => $code])) }}"
                   class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-xs font-semibold border {{ ($status ?? '') === $code ? 'border-slate-300 bg-slate-100 text-slate-800' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                    <span>{!! $statusLabels[$code] !!}</span>
                    <span class="px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-700">{{ $count }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('portal.settlements.disputes') }}" class="grid grid-cols-1 md:grid-cols-12 gap-3 md:items-end">
            <div class="md:col-span-4">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">&Ouml;deme Ref Ara</label>
                <input type="text" name="q" value="{{ $q ?? '' }}" class="mt-1 w-full" placeholder="&Ouml;rn: payout-8200">
            </div>
            <div class="md:col-span-3">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Dispute Tipi</label>
                <select name="type" class="mt-1 w-full">
                    <option value="">T&uuml;m&uuml;</option>
                    @foreach(($types ?? collect()) as $typeCode)
                        <option value="{{ $typeCode }}" @selected(($type ?? '') === $typeCode)>{{ $disputeTypeLabels[$typeCode] ?? $typeCode }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-3">
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Durum</label>
                <select name="status" class="mt-1 w-full">
                    <option value="">T&uuml;m&uuml;</option>
                    @foreach(['open','in_review','resolved','rejected'] as $code)
                        <option value="{{ $code }}" @selected(($status ?? '') === $code)>{!! $statusLabels[$code] !!}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button type="submit" class="btn btn-solid-accent flex-1">Filtrele</button>
                <a href="{{ route('portal.settlements.disputes') }}" class="btn btn-outline">Temizle</a>
            </div>
        </form>
    </div>

    <div class="panel-card overflow-hidden border border-slate-200/80">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3"></th>
                    <th class="text-left px-4 py-3 uppercase tracking-wide text-xs font-semibold">&Ouml;deme Ref</th>
                    <th class="text-left px-4 py-3 uppercase tracking-wide text-xs font-semibold">Tip</th>
                    <th class="text-right px-4 py-3 uppercase tracking-wide text-xs font-semibold">Tutar</th>
                    <th class="text-left px-4 py-3 uppercase tracking-wide text-xs font-semibold">Durum</th>
                    <th class="text-left px-4 py-3 uppercase tracking-wide text-xs font-semibold">&#304;&#351;lem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disputes as $dispute)
                    @php $normalized = strtolower((string) $dispute->status); @endphp
                    <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition-colors">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="dispute_ids[]" value="{{ $dispute->id }}" form="bulk-dispute-form">
                        </td>
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $dispute->payout?->payout_reference ?: '-' }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $disputeTypeLabels[$dispute->dispute_type] ?? $dispute->dispute_type }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">{{ number_format((float) ($dispute->amount ?: $dispute->diff_amount), 2, ',', '.') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ in_array($normalized, ['resolved'], true) ? 'bg-emerald-100 text-emerald-800' : (in_array($normalized, ['rejected'], true) ? 'bg-rose-100 text-rose-800' : (in_array($normalized, ['in_review'], true) ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800')) }}">
                                {!! $statusLabels[$dispute->status] ?? e($dispute->status) !!}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('portal.settlements.disputes.update', $dispute->id) }}" class="flex gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="text-xs">
                                    @foreach(['open','in_review','resolved','rejected'] as $code)
                                        <option value="{{ $code }}" @selected($normalized === $code)>{!! $statusLabels[$code] !!}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline text-xs">G&uuml;ncelle</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center">
                            <div class="text-slate-700 font-semibold mb-1">Dispute kayd&#305; bulunamad&#305;</div>
                            <div class="text-slate-500 text-sm mb-3">Filtreleri temizleyip tekrar deneyin.</div>
                            <a href="{{ route('portal.settlements.disputes') }}" class="btn btn-outline">Filtreleri S&#305;f&#305;rla</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <form method="POST" id="bulk-dispute-form" action="{{ route('portal.settlements.disputes.bulk-status') }}" class="p-4 border-t border-slate-100 flex flex-wrap items-center gap-2">
            @csrf
            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 mr-2">Toplu G&uuml;ncelle</div>
            <select name="status" class="text-sm">
                <option value="in_review">&#304;ncelemede</option>
                <option value="resolved">&Ccedil;&ouml;z&uuml;ld&uuml;</option>
                <option value="rejected">Reddedildi</option>
                <option value="open">A&ccedil;&#305;k</option>
            </select>
            <button type="submit" class="btn btn-outline-accent">Se&ccedil;ilenleri Uygula</button>
            <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline ml-auto">&Ouml;deme Listesi</a>
        </form>
    </div>

    <div class="mt-4">
        {{ $disputes->links() }}
    </div>
@endsection


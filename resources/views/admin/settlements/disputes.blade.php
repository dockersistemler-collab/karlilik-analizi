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
        <form method="GET" action="{{ route('portal.settlements.disputes') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-slate-500">Durum</label>
                <select name="status" class="mt-1">
                    <option value="">T&uuml;m&uuml;</option>
                    @foreach(['open','in_review','resolved','rejected'] as $code)
                        <option value="{{ $code }}" @selected($status === $code)>{!! $statusLabels[$code] !!}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-outline">Filtrele</button>
            <a href="{{ route('portal.settlements.disputes') }}" class="btn btn-outline">Temizle</a>
            <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline-accent ml-auto">&Ouml;deme Listesi</a>
        </form>
    </div>

    <div class="panel-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3"></th>
                    <th class="text-left px-4 py-3">&Ouml;deme Ref</th>
                    <th class="text-left px-4 py-3">Tip</th>
                    <th class="text-right px-4 py-3">Tutar</th>
                    <th class="text-left px-4 py-3">Durum</th>
                    <th class="text-left px-4 py-3">&#304;&#351;lem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disputes as $dispute)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="dispute_ids[]" value="{{ $dispute->id }}" form="bulk-dispute-form">
                        </td>
                        <td class="px-4 py-3">{{ $dispute->payout?->payout_reference ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $disputeTypeLabels[$dispute->dispute_type] ?? $dispute->dispute_type }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) ($dispute->amount ?: $dispute->diff_amount), 2, ',', '.') }}</td>
                        <td class="px-4 py-3">{!! $statusLabels[$dispute->status] ?? e($dispute->status) !!}</td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('portal.settlements.disputes.update', $dispute->id) }}" class="flex gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="text-xs">
                                    @foreach(['open','in_review','resolved','rejected'] as $code)
                                        <option value="{{ $code }}" @selected(strtolower((string) $dispute->status) === $code)>{!! $statusLabels[$code] !!}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline text-xs">G&uuml;ncelle</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">Dispute kayd&#305; bulunamad&#305;.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <form method="POST" id="bulk-dispute-form" action="{{ route('portal.settlements.disputes.bulk-status') }}" class="p-4 border-t border-slate-100 flex items-center gap-2">
            @csrf
            <select name="status" class="text-sm">
                <option value="in_review">&#304;ncelemede</option>
                <option value="resolved">&Ccedil;&ouml;z&uuml;ld&uuml;</option>
                <option value="rejected">Reddedildi</option>
                <option value="open">A&ccedil;&#305;k</option>
            </select>
            <button type="submit" class="btn btn-outline-accent">Se&ccedil;ilenleri Toplu G&uuml;ncelle</button>
        </form>
    </div>

    <div class="mt-4">
        {{ $disputes->links() }}
    </div>
@endsection

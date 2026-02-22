@extends('layouts.admin')

@section('header')
    Hakediş Kontrol Merkezi
@endsection

@section('content')
    <div class="panel-card p-5 mb-4">
        <form method="GET" action="{{ route('portal.settlements.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-slate-500">Durum</label>
                <select name="status" class="mt-1">
                    <option value="">Tümü</option>
                    @foreach(['EXPECTED','PARTIAL_PAID','PAID','DISCREPANCY','IN_REVIEW'] as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-outline">Filtrele</button>
            <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline">Temizle</a>
            <a href="{{ route('portal.settlements.disputes') }}" class="btn btn-outline-accent ml-auto">Sapmalar</a>
        </form>
    </div>

    <div class="panel-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Referans</th>
                    <th class="text-left px-4 py-3">Pazaryeri</th>
                    <th class="text-left px-4 py-3">Dönem</th>
                    <th class="text-right px-4 py-3">Beklenen</th>
                    <th class="text-right px-4 py-3">Ödenen</th>
                    <th class="text-left px-4 py-3">Durum</th>
                    <th class="text-left px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($payouts as $payout)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3 font-semibold">{{ $payout->payout_reference ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $payout->integration?->name ?: strtoupper((string) $payout->integration?->code) }}</td>
                        <td class="px-4 py-3">{{ optional($payout->period_start)->format('d.m.Y') }} - {{ optional($payout->period_end)->format('d.m.Y') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $payout->expected_amount, 2, ',', '.') }} {{ $payout->currency }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $payout->paid_amount, 2, ',', '.') }} {{ $payout->currency }}</td>
                        <td class="px-4 py-3">{{ $payout->status }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('portal.settlements.show', $payout) }}" class="btn btn-outline">Detay</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">Kayıt bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $payouts->links() }}
    </div>
@endsection

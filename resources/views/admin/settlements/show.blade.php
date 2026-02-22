@extends('layouts.admin')

@section('header')
    Hakediş Detayı
@endsection

@section('content')
    <div class="mb-4">
        <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline">Listeye Dön</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Payout Ref</div>
            <div class="text-base font-semibold">{{ $payout->payout_reference ?: '—' }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Beklenen / Ödenen</div>
            <div class="text-base font-semibold">
                {{ number_format((float) $payout->expected_amount, 2, ',', '.') }} / {{ number_format((float) $payout->paid_amount, 2, ',', '.') }} {{ $payout->currency }}
            </div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Durum</div>
            <div class="text-base font-semibold">{{ $payout->status }}</div>
        </div>
    </div>

    <div class="panel-card p-4 mb-4">
        <h3 class="font-semibold mb-3">İşlem Kırılımı</h3>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-3 py-2">Tip</th>
                    <th class="text-right px-3 py-2">Tutar</th>
                    <th class="text-right px-3 py-2">KDV</th>
                    <th class="text-left px-3 py-2">Meta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payout->transactions as $tx)
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2">{{ $tx->type }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float) $tx->amount, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float) $tx->vat_amount, 2, ',', '.') }}</td>
                        <td class="px-3 py-2 text-xs text-slate-600">{{ json_encode($tx->meta, JSON_UNESCAPED_UNICODE) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-3 py-6 text-center text-slate-500">İşlem yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="panel-card p-4">
        <h3 class="font-semibold mb-3">İlgili Sapmalar</h3>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-3 py-2">Tip</th>
                    <th class="text-right px-3 py-2">Fark</th>
                    <th class="text-left px-3 py-2">Durum</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payout->disputes as $dispute)
                    <tr class="border-t border-slate-100">
                        <td class="px-3 py-2">{{ $dispute->dispute_type }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format((float) $dispute->diff_amount, 2, ',', '.') }}</td>
                        <td class="px-3 py-2">{{ $dispute->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-3 py-6 text-center text-slate-500">Sapma kaydı yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

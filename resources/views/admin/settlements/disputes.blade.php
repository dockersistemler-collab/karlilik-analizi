@extends('layouts.admin')

@section('header')
    Sapma Merkezi
@endsection

@section('content')
    <div class="panel-card p-5 mb-4">
        <form method="GET" action="{{ route('portal.settlements.disputes') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs text-slate-500">Durum</label>
                <select name="status" class="mt-1">
                    <option value="">Tümü</option>
                    @foreach(['OPEN','IN_REVIEW','SUBMITTED_TO_MARKETPLACE','RESOLVED','REJECTED'] as $s)
                        <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-outline">Filtrele</button>
            <a href="{{ route('portal.settlements.disputes') }}" class="btn btn-outline">Temizle</a>
            <a href="{{ route('portal.settlements.index') }}" class="btn btn-outline-accent ml-auto">Payout Listesi</a>
        </form>
    </div>

    <div class="panel-card overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="text-left px-4 py-3">Payout Ref</th>
                    <th class="text-left px-4 py-3">Tip</th>
                    <th class="text-right px-4 py-3">Beklenen</th>
                    <th class="text-right px-4 py-3">Gerçekleşen</th>
                    <th class="text-right px-4 py-3">Fark</th>
                    <th class="text-left px-4 py-3">Durum</th>
                </tr>
            </thead>
            <tbody>
                @forelse($disputes as $dispute)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-3">{{ $dispute->payout?->payout_reference ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $dispute->dispute_type }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $dispute->expected_amount, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) $dispute->actual_amount, 2, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-semibold">{{ number_format((float) $dispute->diff_amount, 2, ',', '.') }}</td>
                        <td class="px-4 py-3">{{ $dispute->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">Sapma kaydı bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $disputes->links() }}
    </div>
@endsection

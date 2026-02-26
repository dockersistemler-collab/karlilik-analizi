@extends('layouts.admin')

@section('header')
    Action Engine Calibration
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-4">
        <form method="POST" action="{{ route('portal.action-engine.calibration.run') }}" class="flex gap-2 items-end">
            @csrf
            <div>
                <label class="text-xs text-slate-500">Date</label>
                <input type="date" name="date" value="{{ now()->subDay()->toDateString() }}" class="block border border-slate-200 rounded px-2 py-2">
            </div>
            <button class="btn btn-solid-accent">Calibration Run</button>
        </form>
    </div>

    <div class="panel-card p-4 overflow-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="px-3 py-2">Calculated</th>
                    <th class="px-3 py-2">Marketplace</th>
                    <th class="px-3 py-2">SKU</th>
                    <th class="px-3 py-2">Elasticity</th>
                    <th class="px-3 py-2">Confidence</th>
                    <th class="px-3 py-2">Diagnostics</th>
                </tr>
            </thead>
            <tbody>
                @forelse($calibrations as $row)
                    <tr class="border-b border-slate-100">
                        <td class="px-3 py-2">{{ optional($row->calculated_at)->format('d.m.Y H:i') }}</td>
                        <td class="px-3 py-2 uppercase">{{ $row->marketplace ?? 'global' }}</td>
                        <td class="px-3 py-2">{{ $row->sku ?? '-' }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $row->elasticity, 4) }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $row->confidence, 2) }}</td>
                        <td class="px-3 py-2"><pre class="text-xs whitespace-pre-wrap">{{ json_encode($row->diagnostics, JSON_UNESCAPED_UNICODE) }}</pre></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">Calibration kaydı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $calibrations->links() }}</div>
</div>
@endsection


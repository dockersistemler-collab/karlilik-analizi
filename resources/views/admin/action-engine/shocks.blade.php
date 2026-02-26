@extends('layouts.admin')

@section('header')
    Action Engine Shocks
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-4">
        <form method="GET" action="{{ route('portal.action-engine.shocks') }}" class="grid grid-cols-1 md:grid-cols-4 gap-2">
            <select name="marketplace" class="border border-slate-200 rounded px-2 py-2">
                <option value="">Tüm Pazaryeri</option>
                @foreach($marketplaces as $marketplace)
                    <option value="{{ $marketplace->code }}" @selected(request('marketplace')===$marketplace->code)>{{ $marketplace->name }}</option>
                @endforeach
            </select>
            <select name="shock_type" class="border border-slate-200 rounded px-2 py-2">
                <option value="">Tüm Shock</option>
                <option value="CAMPAIGN" @selected(request('shock_type')==='CAMPAIGN')>CAMPAIGN</option>
                <option value="SHIPPING_CHANGE" @selected(request('shock_type')==='SHIPPING_CHANGE')>SHIPPING_CHANGE</option>
                <option value="FEE_CHANGE" @selected(request('shock_type')==='FEE_CHANGE')>FEE_CHANGE</option>
                <option value="OUTLIER_DEMAND" @selected(request('shock_type')==='OUTLIER_DEMAND')>OUTLIER_DEMAND</option>
                <option value="OUTLIER_PRICE" @selected(request('shock_type')==='OUTLIER_PRICE')>OUTLIER_PRICE</option>
            </select>
            <button class="btn btn-solid-accent">Filtrele</button>
            <a href="{{ route('portal.action-engine.shocks') }}" class="px-3 py-2 border border-slate-200 rounded">Temizle</a>
        </form>
    </div>

    <div class="panel-card p-4">
        <form method="POST" action="{{ route('portal.action-engine.shocks.run') }}" class="flex gap-2 items-end">
            @csrf
            <div>
                <label class="text-xs text-slate-500">Date</label>
                <input type="date" name="date" value="{{ now()->subDay()->toDateString() }}" class="block border border-slate-200 rounded px-2 py-2">
            </div>
            <button class="btn btn-solid-accent">Shock Detection Run</button>
        </form>
    </div>

    <div class="panel-card p-4 overflow-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Marketplace</th>
                    <th class="px-3 py-2">SKU</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2">Severity</th>
                    <th class="px-3 py-2">Detected By</th>
                    <th class="px-3 py-2">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($shocks as $row)
                    <tr class="border-b border-slate-100">
                        <td class="px-3 py-2">{{ $row->date->format('d.m.Y') }}</td>
                        <td class="px-3 py-2 uppercase">{{ $row->marketplace }}</td>
                        <td class="px-3 py-2">{{ $row->sku ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $row->shock_type }}</td>
                        <td class="px-3 py-2">{{ $row->severity }}</td>
                        <td class="px-3 py-2">{{ $row->detected_by }}</td>
                        <td class="px-3 py-2"><pre class="text-xs whitespace-pre-wrap">{{ json_encode($row->details, JSON_UNESCAPED_UNICODE) }}</pre></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-4 text-center text-slate-500">Shock kaydı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $shocks->links() }}</div>
</div>
@endsection


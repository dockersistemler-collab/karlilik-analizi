@extends('layouts.admin')

@section('header')
    Action Engine Campaigns
@endsection

@section('content')
<div class="space-y-4">
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="panel-card p-4">
            <h3 class="font-semibold mb-2">CSV Import</h3>
            <p class="text-xs text-slate-500 mb-2">Kolonlar: marketplace,campaign_id,campaign_name,start_date,end_date,sku,discount_rate</p>
            <form method="POST" action="{{ route('portal.action-engine.campaigns.import') }}" enctype="multipart/form-data" class="space-y-2">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" class="w-full border border-slate-200 rounded px-2 py-2" required>
                <button class="btn btn-solid-accent w-full">Import</button>
            </form>
        </div>
        <div class="panel-card p-4">
            <h3 class="font-semibold mb-2">Apply Campaign Calendar</h3>
            <form method="POST" action="{{ route('portal.action-engine.campaigns.apply') }}" class="space-y-2">
                @csrf
                <label class="text-xs text-slate-500">Campaign DB ID (opsiyonel)</label>
                <input type="number" name="campaign_id" min="1" class="w-full border border-slate-200 rounded px-2 py-2">
                <button class="btn btn-solid-accent w-full">Apply</button>
            </form>
        </div>
    </div>

    <div class="panel-card p-4 overflow-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b text-left text-slate-500">
                    <th class="px-3 py-2">Marketplace</th>
                    <th class="px-3 py-2">Campaign ID</th>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Start</th>
                    <th class="px-3 py-2">End</th>
                    <th class="px-3 py-2">Items</th>
                    <th class="px-3 py-2">Source</th>
                </tr>
            </thead>
            <tbody>
                @forelse($campaigns as $row)
                    <tr class="border-b border-slate-100">
                        <td class="px-3 py-2 uppercase">{{ $row->marketplace }}</td>
                        <td class="px-3 py-2">{{ $row->campaign_id }}</td>
                        <td class="px-3 py-2">{{ $row->name }}</td>
                        <td class="px-3 py-2">{{ $row->start_date->format('d.m.Y') }}</td>
                        <td class="px-3 py-2">{{ $row->end_date->format('d.m.Y') }}</td>
                        <td class="px-3 py-2">{{ $row->items_count }}</td>
                        <td class="px-3 py-2">{{ $row->source }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-4 text-center text-slate-500">Campaign kaydı yok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $campaigns->links() }}</div>
</div>
@endsection


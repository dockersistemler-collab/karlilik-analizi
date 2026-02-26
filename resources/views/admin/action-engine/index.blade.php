@extends('layouts.admin')

@section('header')
    Action Engine
@endsection

@section('content')
<div class="space-y-6">
    @include('admin.decision-center.partials.nav', ['active' => 'action'])

    <div class="flex flex-wrap gap-2">
        <a href="{{ route('portal.action-engine.calibration') }}" class="btn btn-outline-accent">Calibration</a>
        <a href="{{ route('portal.action-engine.shocks') }}" class="btn btn-outline-accent">Shocks</a>
        <a href="{{ route('portal.action-engine.campaigns') }}" class="btn btn-outline-accent">Campaigns</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Open</div><div class="text-2xl font-semibold">{{ $overview['open'] }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Applied</div><div class="text-2xl font-semibold">{{ $overview['applied'] }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Dismissed</div><div class="text-2xl font-semibold">{{ $overview['dismissed'] }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Critical Open</div><div class="text-2xl font-semibold text-rose-600">{{ $overview['critical_open'] }}</div></div>
    </div>

    <div class="panel-card p-4">
        <form method="GET" action="{{ route('portal.action-engine.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-2">
            <select name="status" class="border border-slate-200 rounded px-2 py-2">
                <option value="">Tüm Status</option>
                <option value="open" @selected(request('status')==='open')>open</option>
                <option value="applied" @selected(request('status')==='applied')>applied</option>
                <option value="dismissed" @selected(request('status')==='dismissed')>dismissed</option>
            </select>
            <select name="marketplace" class="border border-slate-200 rounded px-2 py-2">
                <option value="">Tüm Pazaryeri</option>
                @foreach($marketplaces as $marketplace)
                    <option value="{{ $marketplace->code }}" @selected(request('marketplace')===$marketplace->code)>{{ $marketplace->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-slate-200 rounded px-2 py-2">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-slate-200 rounded px-2 py-2">
            <div class="flex gap-2">
                <button class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('portal.action-engine.index') }}" class="px-3 py-2 border border-slate-200 rounded">Temizle</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="panel-card p-4 xl:col-span-2">
            <h3 class="font-semibold mb-3">Recommendations</h3>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-slate-500">
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2">Marketplace</th>
                            <th class="px-3 py-2">Action</th>
                            <th class="px-3 py-2">Severity</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recommendations as $row)
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-2">{{ $row->date->format('d.m.Y') }}</td>
                                <td class="px-3 py-2 uppercase">{{ $row->marketplace }}</td>
                                <td class="px-3 py-2">{{ $row->action_type }}</td>
                                <td class="px-3 py-2">{{ $row->severity }}</td>
                                <td class="px-3 py-2">{{ $row->status }}</td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('portal.action-engine.show', $row) }}" class="btn btn-outline-accent">Detay</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">Kayıt yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $recommendations->links() }}</div>
        </div>

        <div class="panel-card p-4">
            <h3 class="font-semibold mb-3">Manual Run</h3>
            <form method="POST" action="{{ route('portal.action-engine.run') }}" class="space-y-2">
                @csrf
                <input type="date" name="date_from" value="{{ now()->subDays(1)->toDateString() }}" class="w-full border border-slate-200 rounded px-2 py-2" required>
                <input type="date" name="date_to" value="{{ now()->subDays(1)->toDateString() }}" class="w-full border border-slate-200 rounded px-2 py-2" required>
                <button class="btn btn-solid-accent w-full">Run</button>
            </form>

            <h3 class="font-semibold mt-5 mb-2">Recent Runs</h3>
            <div class="space-y-2 max-h-72 overflow-auto">
                @forelse($latestRuns as $run)
                    <div class="border border-slate-200 rounded p-2 text-sm">
                        <div class="font-medium">{{ $run->run_date->format('d.m.Y') }}</div>
                        <div>Generated: {{ data_get($run->stats, 'generated', 0) }}</div>
                        <div>Updated: {{ data_get($run->stats, 'updated', 0) }}</div>
                        <div>Skipped: {{ data_get($run->stats, 'skipped', 0) }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Run kaydı yok.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

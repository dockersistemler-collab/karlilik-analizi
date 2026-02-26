@extends('layouts.admin')

@section('header')
    Marketplace Risk
@endsection

@section('content')
<div class="space-y-6">
    @include('admin.decision-center.partials.nav', ['active' => 'risk'])

    <div class="panel-card p-4">
        <form method="GET" action="{{ route('portal.marketplace-risk.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-slate-500">Pazaryeri</label>
                <select name="marketplace" class="mt-1 w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                    <option value="">Tümü</option>
                    @foreach($marketplaces as $marketplace)
                        <option value="{{ $marketplace->code }}" @selected(request('marketplace') === $marketplace->code)>{{ $marketplace->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-500">Tarih</label>
                <input type="date" name="date" value="{{ request('date', $date->toDateString()) }}" class="mt-1 w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
            </div>
            <div class="md:col-span-2 flex items-end gap-2">
                <button class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('portal.marketplace-risk.index') }}" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600">Temizle</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        @forelse($scores->take(3) as $score)
            <div class="panel-card p-4">
                <div class="text-sm text-slate-500 uppercase">{{ $score->marketplace }}</div>
                <div class="mt-1 text-3xl font-bold {{ $score->status === 'critical' ? 'text-rose-600' : ($score->status === 'warning' ? 'text-amber-600' : 'text-emerald-600') }}">
                    {{ number_format((float) $score->risk_score, 2) }}
                </div>
                <div class="mt-1 text-xs text-slate-500">{{ $score->date->format('d.m.Y') }} | {{ strtoupper($score->status) }}</div>
                <div class="mt-3 text-xs text-slate-600">
                    Drivers:
                    {{ collect((array) data_get($score->reasons, 'drivers', []))->pluck('metric')->implode(', ') ?: '-' }}
                </div>
            </div>
        @empty
            <div class="panel-card p-4 xl:col-span-3 text-slate-500">Risk skoru kaydı yok.</div>
        @endforelse
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="panel-card p-4">
            <h3 class="text-base font-semibold mb-3">KPI Ekle (Manuel)</h3>
            <form method="POST" action="{{ route('portal.marketplace-risk.kpi.store') }}" class="grid grid-cols-2 gap-2">
                @csrf
                <select name="marketplace" class="col-span-2 border border-slate-200 rounded px-2 py-2" required>
                    @foreach($marketplaces as $marketplace)
                        <option value="{{ $marketplace->code }}">{{ $marketplace->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="date" value="{{ now()->toDateString() }}" class="col-span-2 border border-slate-200 rounded px-2 py-2" required>
                <input name="late_shipment_rate" type="number" step="0.01" min="0" max="100" placeholder="late_shipment_rate" class="border border-slate-200 rounded px-2 py-2">
                <input name="cancellation_rate" type="number" step="0.01" min="0" max="100" placeholder="cancellation_rate" class="border border-slate-200 rounded px-2 py-2">
                <input name="return_rate" type="number" step="0.01" min="0" max="100" placeholder="return_rate" class="border border-slate-200 rounded px-2 py-2">
                <input name="performance_score" type="number" step="0.01" min="0" max="100" placeholder="performance_score" class="border border-slate-200 rounded px-2 py-2">
                <input name="rating_score" type="number" step="0.01" min="0" max="5" placeholder="rating_score" class="border border-slate-200 rounded px-2 py-2">
                <input name="odr" type="number" step="0.01" min="0" max="100" placeholder="odr (amazon)" class="border border-slate-200 rounded px-2 py-2">
                <input name="valid_tracking_rate" type="number" step="0.01" min="0" max="100" placeholder="valid_tracking_rate (amazon)" class="border border-slate-200 rounded px-2 py-2">
                <input name="source" value="manual" class="border border-slate-200 rounded px-2 py-2" placeholder="source">
                <button class="col-span-2 btn btn-solid-accent">Kaydet</button>
            </form>
        </div>

        <div class="panel-card p-4">
            <h3 class="text-base font-semibold mb-3">CSV Import</h3>
            <p class="text-xs text-slate-500 mb-2">Kolonlar: date, marketplace, late_shipment_rate, cancellation_rate, return_rate, performance_score, rating_score, odr, valid_tracking_rate</p>
            <form method="POST" action="{{ route('portal.marketplace-risk.kpi.import-csv') }}" enctype="multipart/form-data" class="space-y-2">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" class="w-full border border-slate-200 rounded px-2 py-2" required>
                <button class="btn btn-solid-accent w-full">CSV Yükle</button>
            </form>

            <h3 class="text-base font-semibold mt-6 mb-2">Alerts</h3>
            <div class="max-h-56 overflow-auto space-y-2">
                @forelse($alerts as $alert)
                    <div class="border border-slate-200 rounded p-2 text-sm">
                        <div class="font-medium uppercase">{{ $alert->marketplace }} | {{ $alert->date->format('d.m.Y') }}</div>
                        <div class="{{ $alert->status === 'critical' ? 'text-rose-600' : 'text-amber-600' }}">{{ strtoupper($alert->status) }} - {{ number_format((float) $alert->risk_score, 2) }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Uyarı kaydı yok.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="panel-card p-4">
            <h3 class="text-base font-semibold mb-3">Risk Profiles</h3>
            <form method="POST" action="{{ route('portal.marketplace-risk.profiles.store') }}" class="space-y-2 mb-4">
                @csrf
                <div class="grid grid-cols-2 gap-2">
                    <select name="marketplace" class="border border-slate-200 rounded px-2 py-2" required>
                        @foreach($marketplaces as $marketplace)
                            <option value="{{ $marketplace->code }}">{{ $marketplace->name }}</option>
                        @endforeach
                    </select>
                    <input name="name" placeholder="Profil adı" class="border border-slate-200 rounded px-2 py-2" required>
                </div>
                <textarea name="weights" rows="3" class="w-full border border-slate-200 rounded px-2 py-2" required>{"late_shipment_rate":0.16,"cancellation_rate":0.16,"return_rate":0.14,"performance_score":0.18,"rating_score":0.12,"odr":0.14,"valid_tracking_rate":0.10}</textarea>
                <textarea name="thresholds" rows="2" class="w-full border border-slate-200 rounded px-2 py-2" required>{"warning":45,"critical":70}</textarea>
                <textarea name="metric_thresholds" rows="4" class="w-full border border-slate-200 rounded px-2 py-2" required>{"late_shipment_rate":{"warning":4,"critical":8,"direction":"higher_worse"},"cancellation_rate":{"warning":2,"critical":5,"direction":"higher_worse"},"return_rate":{"warning":8,"critical":15,"direction":"higher_worse"},"performance_score":{"warning":85,"critical":70,"direction":"lower_worse"},"rating_score":{"warning":4.4,"critical":4.0,"direction":"lower_worse"},"odr":{"warning":1,"critical":2,"direction":"higher_worse"},"valid_tracking_rate":{"warning":97,"critical":93,"direction":"lower_worse"}}</textarea>
                <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" checked> Varsayılan</label>
                <button class="btn btn-solid-accent w-full">Profil Kaydet</button>
            </form>

            <div class="space-y-2 max-h-96 overflow-auto">
                @foreach($profiles as $profile)
                    <form method="POST" action="{{ route('portal.marketplace-risk.profiles.update', $profile) }}" class="border border-slate-200 rounded p-2 space-y-2">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-2">
                            <input name="name" value="{{ $profile->name }}" class="border border-slate-200 rounded px-2 py-1">
                            <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_default" value="1" @checked($profile->is_default)> Varsayılan</label>
                        </div>
                        <textarea name="weights" rows="2" class="w-full border border-slate-200 rounded px-2 py-1">{{ json_encode($profile->weights, JSON_UNESCAPED_UNICODE) }}</textarea>
                        <textarea name="thresholds" rows="2" class="w-full border border-slate-200 rounded px-2 py-1">{{ json_encode($profile->thresholds, JSON_UNESCAPED_UNICODE) }}</textarea>
                        <textarea name="metric_thresholds" rows="3" class="w-full border border-slate-200 rounded px-2 py-1">{{ json_encode($profile->metric_thresholds, JSON_UNESCAPED_UNICODE) }}</textarea>
                        <div class="flex justify-end gap-2">
                            <button class="btn btn-outline-accent">Güncelle</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('portal.marketplace-risk.profiles.destroy', $profile) }}" class="text-right">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Silinsin mi?')" class="px-3 py-1 border border-rose-300 text-rose-600 rounded">Sil</button>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="panel-card p-4">
            <h3 class="text-base font-semibold mb-3">KPI List</h3>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="px-2 py-2">Tarih</th>
                            <th class="px-2 py-2">Market</th>
                            <th class="px-2 py-2">Late</th>
                            <th class="px-2 py-2">Cancel</th>
                            <th class="px-2 py-2">Return</th>
                            <th class="px-2 py-2">Perf</th>
                            <th class="px-2 py-2">Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kpis as $kpi)
                            <tr class="border-b border-slate-100">
                                <td class="px-2 py-2">{{ $kpi->date->format('d.m.Y') }}</td>
                                <td class="px-2 py-2 uppercase">{{ $kpi->marketplace }}</td>
                                <td class="px-2 py-2">{{ $kpi->late_shipment_rate }}</td>
                                <td class="px-2 py-2">{{ $kpi->cancellation_rate }}</td>
                                <td class="px-2 py-2">{{ $kpi->return_rate }}</td>
                                <td class="px-2 py-2">{{ $kpi->performance_score }}</td>
                                <td class="px-2 py-2">{{ $kpi->rating_score }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-2 py-4 text-center text-slate-500">KPI kaydı yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $kpis->links() }}</div>
        </div>
    </div>

    <div class="panel-card p-4">
        <h3 class="text-base font-semibold mb-3">Risk Scores</h3>
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="px-3 py-2">Tarih</th>
                        <th class="px-3 py-2">Pazaryeri</th>
                        <th class="px-3 py-2">Score</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Drivers</th>
                        <th class="px-3 py-2">Trend</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($scores as $score)
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-2">{{ $score->date->format('d.m.Y') }}</td>
                            <td class="px-3 py-2 uppercase">{{ $score->marketplace }}</td>
                            <td class="px-3 py-2 font-semibold">{{ number_format((float) $score->risk_score, 2) }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex px-2 py-1 rounded text-xs {{ $score->status === 'critical' ? 'bg-rose-100 text-rose-700' : ($score->status === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                    {{ strtoupper($score->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs">{{ collect((array) data_get($score->reasons, 'drivers', []))->pluck('metric')->implode(', ') ?: '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ collect((array) data_get($score->reasons, 'trends', []))->pluck('metric')->implode(', ') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">Risk skoru bulunamadı.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $scores->links() }}</div>
    </div>
</div>
@endsection

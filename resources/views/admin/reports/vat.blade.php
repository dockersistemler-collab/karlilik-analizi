@extends('layouts.admin')

@section('header')
    KDV Raporu
@endsection

@section('content')
    <div class="panel-card p-6 mb-6">
        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-end gap-3">
            <div class="min-w-[180px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Satış Kanalı</label>
                <select name="marketplace_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                    <option value="">Tümü</option>
                    @foreach($marketplaces as $marketplace)
                        <option value="{{ $marketplace->id }}" @selected(($filters['marketplace_id'] ?? null) == $marketplace->id)>
                            {{ $marketplace->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Başlangıç</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Bitiş</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Hızlı Seçim</label>
                <select name="quick_range" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                    <option value="">Seç</option>
                    @foreach($quickRanges as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['quick_range'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2 lg:ml-auto">
                <button type="submit" class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('admin.reports.vat') }}" class="btn btn-outline">Temizle</a>
            </div>
        </form>
    </div>

    <div class="panel-card p-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-4">Aylık KDV Toplamı</h3>
        <div class="h-64">
            <canvas id="vat-chart"></canvas>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const vatLabels = @json($chart['labels']);
        const vatValues = @json($chart['values']);

        new Chart(document.getElementById('vat-chart'), {
            type: 'bar',
            data: {
                labels: vatLabels,
                datasets: [{
                    label: 'KDV',
                    data: vatValues,
                    backgroundColor: '#ff4439',
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
@endpush

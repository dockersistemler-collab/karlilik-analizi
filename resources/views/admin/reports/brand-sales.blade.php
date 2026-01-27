@extends('layouts.admin')

@section('header')
    Marka Bazlı Satış Raporu
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
            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Grafik Tipi</label>
                <select name="chart_type" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                    <option value="pie" @selected($chartType === 'pie')>Pasta Grafik</option>
                    <option value="horizontal" @selected($chartType === 'horizontal')>Yatay Grafik</option>
                    <option value="bar" @selected($chartType === 'bar')>Çubuk Grafik</option>
                </select>
            </div>
            <div class="flex items-center gap-2 lg:ml-auto">
                <button type="submit" class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('admin.reports.brand-sales') }}" class="btn btn-outline">Temizle</a>
            </div>
        </form>
    </div>

    <div class="panel-card p-6 mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-4">Marka</th>
                        @foreach($marketplaces as $marketplace)
                            <th class="text-right py-2 pr-4">{{ $marketplace->name }}</th>
                        @endforeach
                        <th class="text-right py-2">Toplam</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($report['table'] as $row)
                        <tr>
                            <td class="py-3 pr-4 text-slate-800 font-semibold">{{ $row['brand'] }}</td>
                            @foreach($marketplaces as $marketplace)
                                <td class="py-3 pr-4 text-right text-slate-700">{{ number_format($row['mp_' . $marketplace->id], 2, ',', '.') }} ₺</td>
                            @endforeach
                            <td class="py-3 text-right text-slate-800 font-semibold">{{ number_format($row['revenue'], 2, ',', '.') }} ₺</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $marketplaces->count() + 2 }}" class="py-4 text-center text-slate-500">Kayıt bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="panel-card p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Marka Bazlı Toplam Ciro</h3>
            <div class="h-64">
                <canvas id="brand-revenue-chart"></canvas>
            </div>
        </div>
        <div class="panel-card p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Marka Bazlı Toplam Sipariş</h3>
            <div class="h-64">
                <canvas id="brand-orders-chart"></canvas>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const labels = @json($report['chart']['labels']);
        const revenue = @json($report['chart']['revenue']);
        const orders = @json($report['chart']['orders']);
        const chartType = @json($chartType);

        const createConfig = (type, data, label) => {
            const config = {
                type: type === 'pie' ? 'pie' : 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        backgroundColor: ['#ff4439', '#111827', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'],
                    }]
                },
                options: {
                    indexAxis: type === 'horizontal' ? 'y' : 'x',
                    plugins: { legend: { display: type === 'pie' } },
                    scales: type === 'pie' ? {} : { y: { beginAtZero: true } }
                }
            };
            return config;
        };

        new Chart(document.getElementById('brand-revenue-chart'), createConfig(chartType, revenue, 'Ciro'));
        new Chart(document.getElementById('brand-orders-chart'), createConfig(chartType, orders, 'Sipariş'));
    </script>
@endpush

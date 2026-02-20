@extends('layouts.admin')



@section('header')

    Sipariş ve Ciro Raporu

@endsection



@section('content')

    @php

        $activePlan = auth()->user()?->getActivePlan();
$ownerUser = auth()->user();

        $canExport = $ownerUser ? app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, 'feature.exports') : false;

    @endphp

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

                <a href="{{ route('portal.reports.index') }}" class="btn btn-outline">Temizle</a>

            </div>

            @if($reportExportsEnabled && $canExport)

                <details class="relative">

                    <summary class="btn btn-outline list-none cursor-pointer">Excel Aktar</summary>

                    <div class="absolute right-0 mt-2 w-64 bg-white border border-slate-200 rounded-lg shadow-lg p-2 z-10">

                        <a href="{{ route('portal.reports.orders-revenue.export', request()->query()) }}" class="block px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-md">Siparişleri Excel'e Aktar</a>

                        <a href="{{ route('portal.reports.orders-revenue.invoiced-export', request()->query()) }}" class="block px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-md">Faturalandırılmış Siparişleri Excel'e Aktar</a>

                    </div>

                </details>

            @endif

        </form>

    </div>



    <div class="panel-card p-6 mb-6">

        <div class="flex items-center justify-between mb-4">

            <h3 class="text-sm font-semibold text-slate-700">Sayısal Ciro İstatistiği</h3>

            <span class="text-xs text-slate-400">{{ $report['granularity'] === 'monthly' ? 'Aylık' : 'Günlük' }} görünüm</span>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead class="text-xs uppercase text-slate-400">

                    <tr>

                        <th class="text-left py-2 pr-4">Tarih</th>

                        @foreach($report['marketplaces'] as $marketplace)

                            <th class="text-right py-2 pr-4">{{ $marketplace->name }}</th>

                        @endforeach

                        <th class="text-right py-2">Toplam</th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse($report['table'] as $row)

                        <tr>

                            <td class="py-3 pr-4 text-slate-600">{{ $row['period'] }}</td>

                            @foreach($report['marketplaces'] as $marketplace)

                                <td class="py-3 pr-4 text-right text-slate-700">{{ number_format($row['mp_' . $marketplace->id], 2, ',', '.') }} ?</td>

                            @endforeach

                            <td class="py-3 text-right text-slate-800 font-semibold">{{ number_format($row['total'], 2, ',', '.') }} ?</td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="{{ $report['marketplaces']->count() + 2 }}" class="py-4 text-center text-slate-500">Kayıt bulunamadı.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>



    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="panel-card p-6 lg:col-span-2">

            <div class="flex items-center justify-between mb-4">

                <h3 class="text-sm font-semibold text-slate-700">Grafiksel Sipariş / Ciro İstatistiği</h3>

                <select id="orders-revenue-chart-mode" class="text-xs px-2 py-1 border border-slate-200 rounded-lg">

                    <option value="revenue">Ciro</option>

                    <option value="orders">Sipariş Sayısı</option>

                </select>

            </div>

            <div class="h-64">

                <canvas id="orders-revenue-chart"></canvas>

            </div>

        </div>

        <div class="panel-card p-6">

            <h3 class="text-sm font-semibold text-slate-700 mb-4">Pazaryeri Ciro Dağılımı</h3>

            <div class="h-56">

                <canvas id="marketplace-revenue-chart"></canvas>

            </div>

        </div>

        <div class="panel-card p-6">

            <h3 class="text-sm font-semibold text-slate-700 mb-4">Pazaryeri Sipariş Dağılımı</h3>

            <div class="h-56">

                <canvas id="marketplace-orders-chart"></canvas>

            </div>

        </div>

    </div>

@endsection



@push('scripts')

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>

        const chartLabels = @json($report['chart']['labels']);

        const revenueData = @json($report['chart']['revenue']);

        const ordersData = @json($report['chart']['orders']);



        const chartCtx = document.getElementById('orders-revenue-chart');

        const modeSelect = document.getElementById('orders-revenue-chart-mode');



        const lineChart = new Chart(chartCtx, {

            type: 'line',

            data: {

                labels: chartLabels,

                datasets: [{

                    label: 'Ciro',

                    data: revenueData,

                    borderColor: '#ff4439',

                    backgroundColor: 'rgba(255, 68, 57, 0.15)',

                    tension: 0.3,

                    fill: true,

                }]

            },

            options: {

                plugins: { legend: { display: false } },

                scales: { y: { beginAtZero: true } }

            }

        });



        modeSelect.addEventListener('change', () => {

            const mode = modeSelect.value;

            lineChart.data.datasets[0].label = mode === 'orders' ? 'Sipariş Sayısı' : 'Ciro';

            lineChart.data.datasets[0].data = mode === 'orders' ? ordersData : revenueData;

            lineChart.update();

        });



        const distributionLabels = @json($report['distribution']['labels']);

        const distributionRevenue = @json($report['distribution']['revenue']);

        const distributionOrders = @json($report['distribution']['orders']);



        new Chart(document.getElementById('marketplace-revenue-chart'), {

            type: 'doughnut',

            data: {

                labels: distributionLabels,

                datasets: [{

                    data: distributionRevenue,

                    backgroundColor: ['#ff4439', '#111827', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'],

                }]

            },

            options: { plugins: { legend: { position: 'bottom' } } }

        });



        new Chart(document.getElementById('marketplace-orders-chart'), {

            type: 'doughnut',

            data: {

                labels: distributionLabels,

                datasets: [{

                    data: distributionOrders,

                    backgroundColor: ['#111827', '#ff4439', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6'],

                }]

            },

            options: { plugins: { legend: { position: 'bottom' } } }

        });

    </script>

@endpush






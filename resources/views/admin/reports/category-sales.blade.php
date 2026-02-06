@extends('layouts.admin')



@section('header')

    Kategori Bazlý Satýþ Raporu

@endsection



@section('content')

    <div class="panel-card p-6 mb-6">

        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-end gap-3">

            <div class="min-w-[180px]">

                <label class="block text-xs font-medium text-slate-500 mb-1">Satýþ Kanalý</label>

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

                <label class="block text-xs font-medium text-slate-500 mb-1">Baþlangýç</label>

                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

            </div>

            <div class="min-w-[150px]">

                <label class="block text-xs font-medium text-slate-500 mb-1">Bitiþ</label>

                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

            </div>

            <div class="min-w-[150px]">

                <label class="block text-xs font-medium text-slate-500 mb-1">Hýzlý Seçim</label>

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

                <a href="{{ route('portal.reports.category-sales') }}" class="btn btn-outline">Temizle</a>

            </div>

        </form>

    </div>



    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div class="panel-card p-6">

            <h3 class="text-sm font-semibold text-slate-700 mb-4">Kategori Bazlý Ciro</h3>

            <div class="h-64">

                <canvas id="category-revenue-chart"></canvas>

            </div>

        </div>

        <div class="panel-card p-6">

            <h3 class="text-sm font-semibold text-slate-700 mb-4">Kategori Bazlý Toplam Sipariþ</h3>

            <div class="h-64">

                <canvas id="category-orders-chart"></canvas>

            </div>

        </div>

    </div>

@endsection



@push('scripts')

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>

        const labels = @json($rows->pluck('label'));

        const revenue = @json($rows->pluck('revenue'));

        const orders = @json($rows->pluck('orders'));

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



        new Chart(document.getElementById('category-revenue-chart'), createConfig(chartType, revenue, 'Ciro'));

        new Chart(document.getElementById('category-orders-chart'), createConfig(chartType, orders, 'Sipariþ'));

    </script>

@endpush






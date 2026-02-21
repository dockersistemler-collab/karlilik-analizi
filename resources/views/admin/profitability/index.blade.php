@extends('layouts.admin')

@section('header')
    Karlilik
@endsection

@section('content')
    <style>
        .profitability-quick-wrap .report-filter-quick {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 6px;
            border: 1px solid #dbe3ee;
            border-radius: 12px;
            background: #f8fafc;
            justify-content: flex-end;
            scrollbar-width: thin;
        }
        .profitability-quick-wrap .report-filter-quick > * {
            flex: 0 0 auto;
        }
        .profitability-quick-wrap .report-filter-chip {
            min-height: 42px;
            padding: 8px 18px;
            border: 1px solid #dbe3ee;
            border-radius: 6px;
            background: #ffffff;
            color: #374151;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            font-size: 13px;
            font-weight: 700;
            transform: none;
        }
        .profitability-quick-wrap .report-filter-chip:hover {
            border-color: #cbd5e1;
            background: #ffffff;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.1);
            transform: none;
        }
        .profitability-quick-wrap .report-filter-chip.is-active {
            border-color: #cbd5e1;
            background: #ffffff;
            color: #111827;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.12);
        }
    </style>

    <div class="panel-card p-6 mb-6 report-filter-panel">
        <form method="GET" action="{{ route('portal.profitability.index') }}" class="report-filter-form">
            <div class="w-full report-filter-field" style="display:flex;justify-content:space-between;align-items:flex-start;gap:14px;margin-bottom:10px;flex-wrap:wrap;">
                <div class="min-w-[320px]" style="text-align:left;flex:1 1 auto;">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Satis Kanali</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($marketplaces as $marketplace)
                            @php
                                $checked = in_array($marketplace->code, $filters['marketplaces'], true);
                            @endphp
                            <label class="report-filter-chip text-xs cursor-pointer {{ $checked ? 'is-active' : '' }}" data-mp-card>
                                <input type="checkbox"
                                       name="marketplaces[]"
                                       value="{{ $marketplace->code }}"
                                       class="sr-only"
                                       @checked($checked)
                                       data-mp-checkbox>
                                <span>{{ $marketplace->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="min-w-[260px]" style="text-align:right;flex:0 0 auto;">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Hizli Secim</label>
                    <div class="profitability-quick-wrap">
                    <div class="report-filter-quick">
                        @php
                            $quickRangeOrder = ['today', 'last_7_days', 'last_30_days', 'this_week', 'this_month', 'last_month', 'last_3_months', 'last_1_year'];
                        @endphp
                        @foreach($quickRangeOrder as $key)
                            @if(isset($quickRanges[$key]))
                                <button type="submit"
                                        name="quick_range"
                                        value="{{ $key }}"
                                        class="report-filter-chip text-xs {{ ($filters['quick_range'] ?? '') === $key ? 'is-active' : '' }}">
                                    {{ $quickRanges[$key] }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                    </div>
                </div>
            </div>

            <div class="w-full" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px;">
                <div class="min-w-[150px] report-filter-field">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Baslangic</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="report-filter-control">
                </div>

                <div class="min-w-[150px] report-filter-field">
                    <label class="block text-xs font-medium text-slate-500 mb-1">Bitis</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="report-filter-control">
                </div>

                <div class="min-w-[200px] report-filter-field">
                    <label class="block text-xs font-medium text-slate-500 mb-1">SKU</label>
                    <input type="text" name="sku" value="{{ $filters['sku'] ?? '' }}" placeholder="SKU ara" class="report-filter-control">
                </div>

                <div class="report-filter-actions">
                    <button type="submit" class="report-filter-btn report-filter-btn-primary">Filtrele</button>
                    <a href="{{ route('portal.profitability.index') }}" class="report-filter-btn report-filter-btn-secondary">Temizle</a>
                </div>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 mb-6">
        <div class="panel-card p-4" style="background: linear-gradient(135deg, #dff5ef, #c8efe2);">
            <div class="text-xs text-slate-500">Net Satis</div>
            <div class="text-xl font-semibold text-slate-900">{{ number_format($kpis['net_sales'], 2, ',', '.') }} TL</div>
        </div>
        <div class="panel-card p-4" style="background: linear-gradient(135deg, #d9efff, #c2e3ff);">
            <div class="text-xs text-slate-500">Ucretler Toplami</div>
            <div class="text-xl font-semibold text-slate-900">{{ number_format($kpis['fees_total'], 2, ',', '.') }} TL</div>
        </div>
        <div class="panel-card p-4" style="background: linear-gradient(135deg, #fff2c7, #f6dea4);">
            <div class="text-xs text-slate-500">COGS</div>
            <div class="text-xl font-semibold text-slate-900">{{ number_format($kpis['cogs_total'], 2, ',', '.') }} TL</div>
        </div>
        <div class="panel-card p-4" style="background: linear-gradient(135deg, #f0ece9, #e6dfd7);">
            <div class="text-xs text-slate-500">Brut Kar</div>
            <div class="text-xl font-semibold text-emerald-700">{{ number_format($kpis['gross_profit'], 2, ',', '.') }} TL</div>
        </div>
        <div class="panel-card p-4" style="background: linear-gradient(135deg, #e2f6ee, #cfeee2);">
            <div class="text-xs text-slate-500">Katki Marji</div>
            <div class="text-xl font-semibold text-emerald-700">{{ number_format($kpis['contribution_margin'], 2, ',', '.') }} TL</div>
        </div>
        <div class="panel-card p-4" style="background: linear-gradient(135deg, #ffe9df, #ffd6c7);">
            <div class="text-xs text-slate-500">Iade Orani</div>
            <div class="text-xl font-semibold text-slate-900">{{ number_format($kpis['refund_rate'], 2, ',', '.') }} %</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="panel-card p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700">Net Satis + Katki Marji Trendi</h3>
            </div>
            <div class="h-64">
                <canvas id="profitability-trend-chart"></canvas>
            </div>
        </div>
        <div class="panel-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700">Pazaryerine Gore</h3>
            </div>
            <div class="h-64">
                <canvas id="profitability-marketplace-chart"></canvas>
            </div>
        </div>
    </div>

    <div class="panel-card p-6">
        <h3 class="text-sm font-semibold text-slate-700 mb-4">SKU Performansi</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-4">SKU</th>
                        <th class="text-right py-2 pr-4">Net Satis</th>
                        <th class="text-right py-2">Katki Marji</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($topBottom['top'] as $row)
                        <tr>
                            <td class="py-3 pr-4 text-slate-600">{{ $row->sku ?: '-' }}</td>
                            <td class="py-3 pr-4 text-right text-slate-700">{{ number_format((float) $row->net_sales, 2, ',', '.') }} TL</td>
                            <td class="py-3 text-right text-emerald-700">{{ number_format((float) $row->contribution_margin, 2, ',', '.') }} TL</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-slate-500">Kayit bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6 border-t border-slate-100 pt-4">
            <h4 class="text-xs font-semibold text-slate-500 mb-2">En Dusuk 5 SKU</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase text-slate-400">
                        <tr>
                            <th class="text-left py-2 pr-4">SKU</th>
                            <th class="text-right py-2 pr-4">Net Satis</th>
                            <th class="text-right py-2">Katki Marji</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($topBottom['bottom'] as $row)
                            <tr>
                                <td class="py-3 pr-4 text-slate-600">{{ $row->sku ?: '-' }}</td>
                                <td class="py-3 pr-4 text-right text-slate-700">{{ number_format((float) $row->net_sales, 2, ',', '.') }} TL</td>
                                <td class="py-3 text-right text-red-600">{{ number_format((float) $row->contribution_margin, 2, ',', '.') }} TL</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-4 text-center text-slate-500">Kayit bulunamadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.querySelectorAll('[data-mp-card]').forEach((card) => {
            const checkbox = card.querySelector('[data-mp-checkbox]');
            if (!checkbox) return;
            card.addEventListener('click', () => {
                window.setTimeout(() => {
                    card.classList.toggle('is-active', checkbox.checked);
                }, 0);
            });
        });

        const trendLabels = @json($trend['labels']);
        const trendNetSales = @json($trend['net_sales']);
        const trendContribution = @json($trend['contribution_margin']);

        new Chart(document.getElementById('profitability-trend-chart'), {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        label: 'Net Satis',
                        data: trendNetSales,
                        borderColor: '#ff6b4a',
                        backgroundColor: 'rgba(255, 107, 74, 0.18)',
                        tension: 0.35,
                        fill: true,
                    },
                    {
                        label: 'Katki Marji',
                        data: trendContribution,
                        borderColor: '#6cc9b3',
                        backgroundColor: 'rgba(108, 201, 179, 0.2)',
                        tension: 0.35,
                        fill: true,
                    },
                ]
            },
            options: {
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        const marketplaceLabels = @json($byMarketplace['labels']);
        const marketplaceNetSales = @json($byMarketplace['net_sales']);
        const marketplaceContribution = @json($byMarketplace['contribution_margin']);

        new Chart(document.getElementById('profitability-marketplace-chart'), {
            type: 'bar',
            data: {
                labels: marketplaceLabels,
                datasets: [
                    {
                        label: 'Net Satis',
                        data: marketplaceNetSales,
                        backgroundColor: 'rgba(255, 107, 74, 0.75)',
                    },
                    {
                        label: 'Katki Marji',
                        data: marketplaceContribution,
                        backgroundColor: 'rgba(108, 201, 179, 0.75)',
                    },
                ]
            },
            options: {
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
@endpush




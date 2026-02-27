@extends('layouts.admin')

@section('header')
    Genel Bakış
@endsection

@section('content')
    <style>
        .range-pill,
        .period-tab {
            border: 1px solid #dbe3ee;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: #334155;
            font-weight: 600;
            border-radius: 12px;
            transition: transform .18s ease, box-shadow .2s ease, border-color .2s ease, color .2s ease;
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.05);
        }
        .range-pill:hover,
        .period-tab:hover {
            border-color: #c7d7ec;
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.09);
        }
        .range-pill.is-active,
        .period-tab.is-active {
            background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
            border-color: #fecdd3;
            color: #9f1239;
            box-shadow: 0 12px 24px rgba(244, 63, 94, 0.2);
        }
    </style>
<div class="space-y-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            <div class="rounded-2xl border border-slate-100 bg-emerald-50/80 p-6 shadow-sm">
                <div class="text-sm font-semibold text-slate-900">G&uuml;nl&uuml;k Gelen Sipariş</div>
                <div class="mt-3 text-5xl font-bold text-slate-900" id="kpi-daily-orders-admin">-</div>
                <div class="mt-3 text-sm text-slate-700">Bugün gelen sipariş sayısı</div>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-yellow-50/80 p-6 shadow-sm">
                <div class="text-sm font-semibold text-slate-900">G&uuml;nl&uuml;k Ürün Satışı</div>
                <div class="mt-3 text-5xl font-bold text-slate-900" id="kpi-daily-items-admin">-</div>
                <div class="mt-3 text-sm text-slate-700">Bugün satılan toplam ürün sayısı</div>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-sky-50/80 p-6 shadow-sm">
                <div class="text-sm font-semibold text-slate-900">Bu Ay Gelen Sipariş</div>
                <div class="mt-3 text-5xl font-bold text-slate-900" id="kpi-monthly-orders-admin">-</div>
                <div class="mt-3 text-sm text-slate-700">Bu ay gelen sipariş sayısı</div>
            </div>
            <div class="rounded-2xl border border-slate-100 bg-rose-50/80 p-6 shadow-sm">
                <div class="text-sm font-semibold text-slate-900">Toplam Ürün Satışı</div>
                <div class="mt-3 text-5xl font-bold text-slate-900" id="kpi-monthly-items-admin">-</div>
                <div class="mt-3 text-sm text-slate-700">Bu ay gelen toplam ürün sayısı</div>
            </div>
        </div>

        @if(!empty($communicationStats))
            <a href="{{ route('portal.communication-center.questions') }}" class="block rounded-3xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">Bekleyen İletişim</h2>
                    <span class="text-xs text-slate-500">Detaya git</span>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <div class="text-slate-500">Bekleyen</div>
                        <div class="text-2xl font-bold text-slate-900">{{ number_format((int) $communicationStats['pending']) }}</div>
                    </div>
                    <div class="rounded-xl bg-amber-50 p-4">
                        <div class="text-amber-700">Kritik</div>
                        <div class="text-2xl font-bold text-amber-800">{{ number_format((int) $communicationStats['critical']) }}</div>
                    </div>
                    <div class="rounded-xl bg-sky-50 p-4">
                        <div class="text-sky-700">Ort. Cevap Süresi (7g)</div>
                        <div class="text-2xl font-bold text-sky-800">{{ number_format((int) $communicationStats['avg_response_sec']) }} sn</div>
                    </div>
                </div>
            </a>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col h-[408px] min-h-0">
                                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">Bugünkü Net Kar</h2>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <button type="button" data-range="day" class="net-kar-range-pill range-pill shrink-0 whitespace-nowrap rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600">G&uuml;nl&uuml;k</button>
                        <button type="button" data-range="week" class="net-kar-range-pill range-pill shrink-0 whitespace-nowrap rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600">Haftal&#305;k</button>
                        <button type="button" data-range="month" class="net-kar-range-pill range-pill shrink-0 whitespace-nowrap rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600">Ayl&#305;k</button>
                        <button type="button" data-range="quarter" class="net-kar-range-pill range-pill shrink-0 whitespace-nowrap rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600">3 Ayl&#305;k</button>
                        <button type="button" data-range="half" class="net-kar-range-pill range-pill shrink-0 whitespace-nowrap rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600">6 Ayl&#305;k</button>
                        <button type="button" data-range="year" class="net-kar-range-pill range-pill shrink-0 whitespace-nowrap rounded-full border border-slate-200 px-3 py-1 text-[11px] text-slate-600">Y&#305;ll&#305;k</button>
                    </div>
                </div>
                <div class="mt-6 rounded-2xl border border-slate-100 bg-rose-50/40 p-4 flex-1">
                    <div class="relative h-full w-full">
                        <canvas id="net-kar-chart-admin"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col h-[408px] min-h-0">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">G&uuml;nl&uuml;k Pazaryeri Satış</h2>
                    <div class="relative">
                        <button id="platform-toggle-admin" type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                            <span id="platform-label-admin">PLATFORM</span>
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M5.25 7.5 10 12.25 14.75 7.5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div id="platform-menu-admin" class="absolute right-0 z-10 mt-2 hidden w-40 rounded-xl border border-slate-200 bg-white p-2 text-xs shadow-lg">
                            <button type="button" data-platform="all" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">Tümü</button>
                            @foreach(($marketplaces ?? []) as $marketplace)
                                <button type="button" data-platform="{{ $marketplace->code ?? $marketplace->name }}" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">{{ $marketplace->name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-200/60 text-rose-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M5 12h14M7 7h10M7 17h6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div class="text-sm font-semibold text-slate-900">GÜNLÜK CİRO</div>
                    </div>
                    <div class="rounded-2xl border border-rose-200/40 bg-transparent px-6 py-3 text-center text-sm font-semibold text-slate-900 shadow-lg shadow-rose-200/60 min-w-[120px]" id="daily-sales-total-admin">
                        0 TL
                    </div>
                </div>

                <div class="mt-8 flex-1 min-h-0">
                    <div class="space-y-3 pr-2 pb-3" id="daily-sales-marketplaces-admin"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 items-stretch">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col w-full min-w-0 mini-metric-card">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">En Çok Satılan 10 Ürün</h3>
                </div>
                <div class="mt-3 period-tabs" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));column-gap:16px;row-gap:8px;align-items:center;width:100%;">
    <button type="button" data-period="day" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">G&uuml;nl&uuml;k</button>
    <button type="button" data-period="week" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Haftal&#305;k</button>
    <button type="button" data-period="month" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Ayl&#305;k</button>
    <button type="button" data-period="quarter" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">3 Ayl&#305;k</button>
    <button type="button" data-period="half" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">6 Ayl&#305;k</button>
    <button type="button" data-period="year" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Y&#305;ll&#305;k</button>
</div>
                <div class="mt-4 space-y-3">
                    @foreach([
                        ['Ayakkabı', 42, 'bg-amber-400'],
                        ['Palto', 56, 'bg-orange-400'],
                        ['Bluz', 85, 'bg-violet-300'],
                        ['Etek', 45, 'bg-emerald-200'],
                        ['Ceket', 35, 'bg-slate-900 text-white'],
                    ] as $row)
                        <div class="flex items-center gap-3 text-xs">
                            <div class="w-20 font-semibold text-slate-800 truncate">{{ $row[0] }}</div>
                            <div class="flex-1 h-8 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $row[2] }}" style="width: {{ min(100, $row[1]) }}%;">
                                    <span class="flex h-full items-center justify-end pr-3 text-xs font-semibold text-slate-900">
                                        {{ $row[1] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col w-full min-w-0 mini-metric-card">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">En Çok Satılan Marka</h3>
                </div>
                <div class="mt-3 period-tabs" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));column-gap:16px;row-gap:8px;align-items:center;width:100%;">
    <button type="button" data-period="day" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">G&uuml;nl&uuml;k</button>
    <button type="button" data-period="week" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Haftal&#305;k</button>
    <button type="button" data-period="month" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Ayl&#305;k</button>
    <button type="button" data-period="quarter" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">3 Ayl&#305;k</button>
    <button type="button" data-period="half" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">6 Ayl&#305;k</button>
    <button type="button" data-period="year" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Y&#305;ll&#305;k</button>
</div>
                <div class="mt-4 space-y-3">
                    @foreach([
                        ['Luna', 65, 'bg-amber-400'],
                        ['Vera', 48, 'bg-orange-400'],
                        ['Atlas', 72, 'bg-violet-300'],
                        ['Mira', 39, 'bg-emerald-200'],
                        ['Nova', 30, 'bg-slate-900 text-white'],
                    ] as $row)
                        <div class="flex items-center gap-3 text-xs">
                            <div class="w-20 font-semibold text-slate-800 truncate">{{ $row[0] }}</div>
                            <div class="flex-1 h-8 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $row[2] }}" style="width: {{ min(100, $row[1]) }}%;">
                                    <span class="flex h-full items-center justify-end pr-3 text-xs font-semibold text-slate-900">
                                        {{ $row[1] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col w-full min-w-0 mini-metric-card">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">En Çok Satış Yapılan Kategoriler</h3>
                </div>
                <div class="mt-3 period-tabs" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));column-gap:16px;row-gap:8px;align-items:center;width:100%;">
    <button type="button" data-period="day" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">G&uuml;nl&uuml;k</button>
    <button type="button" data-period="week" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Haftal&#305;k</button>
    <button type="button" data-period="month" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Ayl&#305;k</button>
    <button type="button" data-period="quarter" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">3 Ayl&#305;k</button>
    <button type="button" data-period="half" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">6 Ayl&#305;k</button>
    <button type="button" data-period="year" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Y&#305;ll&#305;k</button>
</div>
                <div class="mt-4 space-y-3">
                    @foreach([
                        ['Ayakkabı', 58, 'bg-amber-400'],
                        ['Giyim', 62, 'bg-orange-400'],
                        ['Aksesuar', 44, 'bg-violet-300'],
                        ['Çanta', 36, 'bg-emerald-200'],
                        ['Ev', 28, 'bg-slate-900 text-white'],
                    ] as $row)
                        <div class="flex items-center gap-3 text-xs">
                            <div class="w-20 font-semibold text-slate-800 truncate">{{ $row[0] }}</div>
                            <div class="flex-1 h-8 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $row[2] }}" style="width: {{ min(100, $row[1]) }}%;">
                                    <span class="flex h-full items-center justify-end pr-3 text-xs font-semibold text-slate-900">
                                        {{ $row[1] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col w-full min-w-0 mini-metric-card">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">Pazaryeri Satış Adedi Dağılımı</h3>
                </div>
                <div class="mt-3 period-tabs" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));column-gap:16px;row-gap:8px;align-items:center;width:100%;">
    <button type="button" data-period="day" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">G&uuml;nl&uuml;k</button>
    <button type="button" data-period="week" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Haftal&#305;k</button>
    <button type="button" data-period="month" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Ayl&#305;k</button>
    <button type="button" data-period="quarter" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">3 Ayl&#305;k</button>
    <button type="button" data-period="half" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">6 Ayl&#305;k</button>
    <button type="button" data-period="year" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50" style="width:100%;white-space:nowrap;">Y&#305;ll&#305;k</button>
</div>
                <div class="mt-4 space-y-3">
                    @foreach([
                        ['Trendyol', 42, 'bg-amber-400'],
                        ['Hepsiburada', 56, 'bg-orange-400'],
                        ['N11', 85, 'bg-violet-300'],
                        ['Çiçek Sepeti', 45, 'bg-emerald-200'],
                        ['Amazon', 35, 'bg-sky-400 text-slate-900'],
                    ] as $row)
                        <div class="flex items-center gap-3 text-xs">
                            <div class="w-24 font-semibold text-slate-800 truncate">{{ $row[0] }}</div>
                            <div class="flex-1 h-8 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full {{ $row[2] }}" style="width: {{ min(100, $row[1]) }}%;">
                                    <span class="flex h-full items-center justify-end pr-3 text-xs font-semibold text-slate-900">
                                        {{ $row[1] }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col lg:col-span-2">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900 whitespace-nowrap">Türkiye Sipariş Dağılımı</h2>
                        <div class="relative">
                            <button id="map-range-toggle-admin" type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                                <span id="map-range-label-admin">Haftal&#305;k</span>
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M5.25 7.5 10 12.25 14.75 7.5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <div id="map-range-menu-admin" class="absolute right-0 z-10 mt-2 hidden w-40 rounded-xl border border-slate-200 bg-white p-2 text-xs shadow-lg">
                                <button type="button" data-range="day" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">G&uuml;nl&uuml;k</button>
                                <button type="button" data-range="week" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">Haftal&#305;k</button>
                                <button type="button" data-range="month" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">Ayl&#305;k</button>
                                <button type="button" data-range="quarter" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">3 Ayl&#305;k</button>
                                <button type="button" data-range="half" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">6 Ayl&#305;k</button>
                                <button type="button" data-range="year" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">Y&#305;ll&#305;k</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <button type="button" data-range="day" class="map-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">G&uuml;nl&uuml;k</button>
                        <button type="button" data-range="week" class="map-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">Haftal&#305;k</button>
                        <button type="button" data-range="month" class="map-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">Ayl&#305;k</button>
                        <button type="button" data-range="quarter" class="map-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">3 Ayl&#305;k</button>
                        <button type="button" data-range="half" class="map-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">6 Ayl&#305;k</button>
                        <button type="button" data-range="year" class="map-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">Y&#305;ll&#305;k</button>
                    </div>
                </div>
                <div class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 p-3 h-[420px] overflow-hidden">
                    <div id="turkey-map-admin" class="h-full w-full"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>
    <script>
        const adminMapStyles = document.createElement('style');
        adminMapStyles.textContent = `
            #turkey-map-admin .leaflet-pane,
            #turkey-map-admin .leaflet-layer,
            #turkey-map-admin .leaflet-marker-icon,
            #turkey-map-admin .leaflet-marker-shadow,
            #turkey-map-admin .leaflet-tile,
            #turkey-map-admin .leaflet-tile-container,
            #turkey-map-admin .leaflet-pane > svg,
            #turkey-map-admin .leaflet-pane > canvas,
            #turkey-map-admin .leaflet-zoom-animated,
            #turkey-map-admin .leaflet-zoom-animated g,
            #turkey-map-admin .leaflet-interactive {
                transform-origin: center center;
            }
            #turkey-map-admin .leaflet-container {
                background: transparent;
            }
            #turkey-map-admin .leaflet-tooltip {
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
            }
            #turkey-map-admin .map-tooltip {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                gap: 1px;
                min-width: 40px;
            }
            #turkey-map-admin .map-name {
                font-size: 10px;
                font-weight: 700;
                color: #1f2937;
                line-height: 1.1;
                text-align: center;
                text-shadow: 0 1px 0 rgba(255,255,255,0.8);
            }
            #turkey-map-admin .map-count {
                font-size: 9px;
                font-weight: 700;
                color: #f97316;
                line-height: 1;
            }
            #turkey-map-admin .leaflet-interactive {
                transition: stroke 0.15s ease, stroke-width 0.15s ease;
            }
            #turkey-map-admin .leaflet-zoom-box {
                display: none !important;
            }
            
            .mini-metric-card {
                min-height: 380px;
            }
            .range-pill,
            .period-tab {
                border: 1px solid #dbe3ee;
                background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
                color: #334155;
                font-weight: 600;
                border-radius: 12px;
                transition: transform .18s ease, box-shadow .2s ease, border-color .2s ease, color .2s ease;
                box-shadow: 0 6px 14px rgba(15, 23, 42, 0.05);
            }
            .range-pill:hover,
            .period-tab:hover {
                border-color: #c7d7ec;
                transform: translateY(-1px);
                box-shadow: 0 10px 20px rgba(15, 23, 42, 0.09);
            }
            .range-pill.is-active {
                background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
                border-color: #fecdd3;
                color: #9f1239;
                box-shadow: 0 12px 24px rgba(244, 63, 94, 0.2);
            }
            .period-tab.is-active {
                background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
                border-color: #fecdd3;
                color: #9f1239;
                box-shadow: 0 12px 24px rgba(244, 63, 94, 0.2);
            }
            #turkey-map-admin .leaflet-zoom-box {
                display: none !important;
            }
            .map-label-wrapper {
                background: transparent;
                border: none;
                box-shadow: none;
            }
        `;
        document.head.appendChild(adminMapStyles);
        const metricsEndpointAdmin = @json(route('portal.dashboard.metrics'));
        const mapEndpointAdmin = @json(route('portal.dashboard.map'));
        const netKarCtxAdmin = document.getElementById('net-kar-chart-admin');
        let netKarChartAdmin = null;
        let netKarRangeAdmin = 'day';
        let netKarMetaAdmin = { range: 'day', start: null, end: null };
        let selectedPlatformAdmin = 'all';
        let mapRangeAdmin = 'week';
        let mapInstanceAdmin = null;
        let mapLayerAdmin = null;

        const formatNumber = (value) => Number(value || 0).toLocaleString('tr-TR');
        const formatCurrency = (value) => `${formatNumber(value)} TL`;
        const fallbackMarketplacesAdmin = @json($fallbackMarketplaces ?? []);

        const palette = [
            'bg-amber-400/90',
            'bg-amber-300/90',
            'bg-violet-300/90',
            'bg-emerald-200/90',
            'bg-slate-900',
        ];
        const textPalette = [
            'text-slate-900',
            'text-slate-900',
            'text-slate-900',
            'text-slate-900',
            'text-white',
        ];

        const formatNetKarTitleAdmin = (index, labels, timestamps) => {
            const range = netKarMetaAdmin?.range ?? 'week';
            const ts = timestamps?.[index];
            const date = ts ? new Date(ts) : null;
            const baseDate = date ?? (netKarMetaAdmin?.start ? new Date(netKarMetaAdmin.start) : null);
            if (!baseDate) {
                return labels[index] ?? '';
            }

            if (range === 'day') {
                const datePart = new Intl.DateTimeFormat('tr-TR', {
                    timeZone: 'Europe/Istanbul',
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                }).format(baseDate);
                const timePart = new Intl.DateTimeFormat('tr-TR', {
                    timeZone: 'Europe/Istanbul',
                    hour: '2-digit',
                    minute: '2-digit',
                }).format(baseDate);
                return `${datePart} ${timePart} (GMT+3)`;
            }

            return new Intl.DateTimeFormat('tr-TR', {
                timeZone: 'Europe/Istanbul',
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            }).format(baseDate);
        };

        const resolveNetKarScaleAdmin = (range) => {
            switch (range) {
                case 'day':
                    return { max: 50000, step: 10000 };
                case 'week':
                    return { max: 200000, step: 40000 };
                case 'month':
                    return { max: 400000, step: 80000 };
                case 'quarter':
                    return { max: 600000, step: 120000 };
                case 'half':
                    return { max: 1000000, step: 200000 };
                case 'year':
                    return { max: 5000000, step: 1000000 };
                default:
                    return { max: null, step: null };
            }
        };

        const applyNetKarScaleAdmin = (values) => {
            const scale = resolveNetKarScaleAdmin(netKarMetaAdmin?.range ?? 'week');
            const maxValue = Math.max(...(values ?? []), 0);
            const maxRounded = scale.max
                ? Math.max(scale.max, Math.ceil(maxValue / scale.max) * scale.max)
                : maxValue;
            return { maxRounded, step: scale.step };
        };

        const updateChartAdmin = (labels, values) => {
            if (!netKarCtxAdmin) return;
            const scale = applyNetKarScaleAdmin(values);
            if (!netKarChartAdmin) {
                netKarChartAdmin = new Chart(netKarCtxAdmin, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            data: values,
                            borderColor: '#4fb6ff',
                            backgroundColor: 'rgba(79, 182, 255, 0.15)',
                            tension: 0.35,
                            fill: true,
                            pointRadius: 4,
                            pointBackgroundColor: '#4fb6ff',
                            pointBorderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
    legend: { display: false },
    tooltip: {
        callbacks: {
                                                title: (items) => {
                if (!items?.length) return '';
                const idx = items[0].dataIndex ?? 0;
                const ts = netKarMetaAdmin?.timestamps ?? [];
                return formatNetKarTitleAdmin(idx, labels, ts);
            },
            label: (ctx) => `${formatNumber(ctx.parsed.y || 0)} TL`
        }
    }
},
scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#6b7280', font: { size: 11 } }
                            },
                            y: {
                                grid: { color: 'rgba(148,163,184,0.25)' },
                                min: 0,
                                suggestedMax: scale.maxRounded,
                                ticks: {
                                    color: '#6b7280',
                                    font: { size: 11 },
                                    callback: (value) => `${Number(value || 0).toLocaleString('tr-TR')} TL`,
                                    stepSize: scale.step ?? undefined,
                                }
                            }
                        }
                    }
                });
                return;
            }

            netKarChartAdmin.data.labels = labels;
            netKarChartAdmin.data.datasets[0].data = values;
            netKarChartAdmin.options.scales.y.min = 0;
            netKarChartAdmin.options.scales.y.suggestedMax = scale.maxRounded;
            if (scale.step) {
                netKarChartAdmin.options.scales.y.ticks.stepSize = scale.step;
            } else {
                delete netKarChartAdmin.options.scales.y.ticks.stepSize;
            }
            netKarChartAdmin.update();
        };

        const marketplaceIconAdmin = (row) => {
            const code = (row.code || row.name || '').toString().toLowerCase();
            if (code.includes('trendyol')) return 'T';
            if (code.includes('hepsi')) return 'H';
            if (code.includes('n11')) return 'N';
            if (code.includes('cicek') || code.includes('çiçek')) return 'Ç';
            if (code.includes('amazon')) return 'A';
            return (row.name || '?').toString().charAt(0).toUpperCase();
        };

        const renderMarketplacesAdmin = (rows) => {
            const container = document.getElementById('daily-sales-marketplaces-admin');
            if (!container) return;
            container.innerHTML = '';

            rows.forEach((row, index) => {
                const colorClass = palette[index % palette.length];
                const textClass = textPalette[index % textPalette.length];
                const item = document.createElement('div');
                item.className = 'flex items-center gap-3';
                item.innerHTML = `
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/80 text-sm font-semibold text-slate-700 shadow-sm border border-slate-200/60">
                        ${marketplaceIconAdmin(row)}
                    </div>
                    <div class="flex-1">
                        <div class="rounded-lg ${colorClass} px-3 py-2 text-sm font-semibold ${textClass}">
                            ${row.name}
                        </div>
                    </div>
                    <div class="text-sm font-semibold text-slate-700">${formatCurrency(row.total)}</div>
                `;
                container.appendChild(item);
            });
        };

        const applyMetricsAdmin = (data) => {
            if (!data) return;
            document.getElementById('kpi-daily-orders-admin').textContent = formatNumber(data.kpis?.daily_orders ?? 0);
            document.getElementById('kpi-daily-items-admin').textContent = formatNumber(data.kpis?.daily_items ?? 0);
            document.getElementById('kpi-monthly-orders-admin').textContent = formatNumber(data.kpis?.monthly_orders ?? 0);
            document.getElementById('kpi-monthly-items-admin').textContent = formatNumber(data.kpis?.monthly_items ?? 0);
            const totalEl = document.getElementById('daily-sales-total-admin');
            const rowsAll = data.daily_sales?.marketplaces ?? [];
            const rows = rowsAll.length ? rowsAll : fallbackMarketplacesAdmin;
            const filtered = selectedPlatformAdmin === 'all'
                ? rows
                : rows.filter((row) => {
                    const key = (row.code || row.name || '').toString().toLowerCase();
                    return key === selectedPlatformAdmin || (row.name || '').toString().toLowerCase() === selectedPlatformAdmin;
                });
            const filteredTotal = filtered.reduce((sum, row) => sum + Number(row.total || 0), 0);
            if (totalEl) {
                totalEl.childNodes[0].textContent = formatCurrency(filteredTotal) + ' ';
            }
            renderMarketplacesAdmin(filtered);
            const chart = data.net_profit_chart ?? {};
            if (chart.range) {
                netKarRangeAdmin = chart.range;
                netKarMetaAdmin = {
                    range: chart.range,
                    start: chart.start ?? null,
                    end: chart.end ?? null,
                    timestamps: chart.timestamps ?? [],
                };
                const labelMap = {
                    day: 'GÜNLÜK (GMT+3)',
                    week: 'Haftal&#305;k',
                    month: 'Ayl&#305;k',
                    quarter: '3 Ayl&#305;k',
                    half: '6 Ayl&#305;k',
                    year: 'Y&#305;ll&#305;k',
                };
                const labelEl = document.getElementById('net-kar-range-label-admin');
                if (labelEl) labelEl.textContent = labelMap[chart.range] ?? 'Haftal&#305;k';
                setActiveNetKarPillAdmin(chart.range);
            }
            updateChartAdmin(chart.labels ?? [], chart.values ?? []);
        };

        const fetchMetricsAdmin = async (range = netKarRangeAdmin) => {
            try {
                const url = `${metricsEndpointAdmin}?range=${encodeURIComponent(range)}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                applyMetricsAdmin(data);
            } catch (error) {
                // no-op
            }
        };

        if (netKarCtxAdmin) {
            setActiveNetKarPillAdmin(netKarRangeAdmin);
            fetchMetricsAdmin();
            setInterval(fetchMetricsAdmin, 15000);
        }

        const normalizeMapKeyAdmin = (value = '') => {
            let normalized = value.toString().toLowerCase();
            normalized = normalized.replace(/ç/g, 'c')
                .replace(/ğ/g, 'g')
                .replace(/ı/g, 'i')
                .replace(/ö/g, 'o')
                .replace(/ş/g, 's')
                .replace(/ü/g, 'u');
            normalized = normalized.replace(/[^a-z0-9\s]/g, ' ');
            normalized = normalized.replace(/\s+/g, ' ').trim();
            return normalized;
        };

        const initMapAdmin = () => {
            const container = document.getElementById('turkey-map-admin');
            if (!container || typeof L === 'undefined') return;

            mapInstanceAdmin = L.map(container, {
                zoomControl: false,
                attributionControl: false,
                scrollWheelZoom: false,
                dragging: false,
                doubleClickZoom: false,
                boxZoom: false,
                touchZoom: false,
                keyboard: false,
            }).setView([39.0, 35.0], 5);

            mapInstanceAdmin.boxZoom.disable();
            mapInstanceAdmin.dragging.disable();
            mapInstanceAdmin.touchZoom.disable();
            mapInstanceAdmin.doubleClickZoom.disable();
            mapInstanceAdmin.scrollWheelZoom.disable();
            mapInstanceAdmin.keyboard.disable();
            container.addEventListener('mousedown', (e) => e.preventDefault());
            container.addEventListener('mousemove', (e) => e.preventDefault());

            fetch('/maps/turkey-provinces.geojson')
                .then(response => response.json())
                .then((geojson) => {
                    mapLayerAdmin = L.geoJSON(geojson, {
                        style: () => ({
                            fillColor: '#f1f5f9',
                            color: '#ffffff',
                            weight: 1,
                            fillOpacity: 0.5,
                        }),
                        onEachFeature: (feature, layer) => {
                            const name = feature.properties?.name || 'Bilinmeyen';
                            const tooltipContent = `<div class="map-tooltip"><span class="map-name">${name}</span><span class="map-count">0 adet</span></div>`;
                            layer.bindTooltip(tooltipContent, {
                                permanent: true,
                                direction: 'center',
                                className: 'map-label-wrapper',
                                });
                        },
                    }).addTo(mapInstanceAdmin);

                    const bounds = mapLayerAdmin.getBounds().pad(0.05);
                    mapInstanceAdmin.fitBounds(bounds, { padding: [0, 0] });
                    mapInstanceAdmin.setZoom(mapInstanceAdmin.getZoom() + 0.7);
                    setTimeout(() => mapInstanceAdmin.invalidateSize(), 0);
                });
        };

        const updateMapAdmin = (mapData) => {
            if (!mapLayerAdmin) return;
            const values = Object.values(mapData || {});
            const maxValue = Math.max(...values, 1);

            mapLayerAdmin.eachLayer((layer) => {
                const name = layer.feature?.properties?.name || '';
                const key = normalizeMapKeyAdmin(name);
                const value = mapData?.[key] ?? 0;
                const lightness = 70 - Math.min(50, (value / maxValue) * 50);
                layer.setStyle({
                    fillColor: value ? `hsl(26, 72%, ${lightness}%)` : '#f1f5f9',
                    fillOpacity: value ? 0.82 : 0.4,
                });
                const tooltipContent = `<div class="map-tooltip"><span class="map-name">${name}</span><span class="map-count">${value} adet</span></div>`;
                layer.bindTooltip(tooltipContent, {
                    permanent: true,
                    direction: 'center',
                    className: 'map-label-wrapper',
                });

                layer.on('mouseover', () => {
                    layer.setStyle({ weight: 2.5, color: '#f97316' });
                });
                layer.on('mouseout', () => {
                    layer.setStyle({ weight: 1, color: '#ffffff' });
                });
            });
        };

        function setActiveMapPillAdmin(range) {
            document.querySelectorAll('.map-range-pill').forEach((pill) => {
                if (pill.dataset.range === range) {
                    pill.classList.add('is-active');
                } else {
                    pill.classList.remove('is-active');
                }
            });
        }

        function setActiveNetKarPillAdmin(range) {
            document.querySelectorAll('.net-kar-range-pill').forEach((pill) => {
                if (pill.dataset.range === range) {
                    pill.classList.add('is-active');
                } else {
                    pill.classList.remove('is-active');
                }
            });
        }
        const fetchMapAdmin = async (range = mapRangeAdmin) => {
            try {
                const url = `${mapEndpointAdmin}?range=${encodeURIComponent(range)}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                updateMapAdmin(data.map || {});
                setActiveMapPillAdmin(range);
            } catch (error) {
                // no-op
            }
        };

        initMapAdmin();
        if (mapEndpointAdmin) {
            setActiveMapPillAdmin(mapRangeAdmin);
            fetchMapAdmin();
            setInterval(fetchMapAdmin, 60000);
        }

        const mapToggleAdmin = document.getElementById('map-range-toggle-admin');
        const mapMenuAdmin = document.getElementById('map-range-menu-admin');
        if (mapToggleAdmin && mapMenuAdmin) {
            mapToggleAdmin.addEventListener('click', (event) => {
                event.stopPropagation();
                mapMenuAdmin.classList.toggle('hidden');
            });
            mapMenuAdmin.querySelectorAll('button[data-range]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    mapRangeAdmin = btn.dataset.range;
                    const labelEl = document.getElementById('map-range-label-admin');
                    if (labelEl) labelEl.textContent = btn.textContent.trim().toUpperCase();
                mapMenuAdmin.classList.add('hidden');
                    fetchMapAdmin(mapRangeAdmin);
                });
            });
            document.addEventListener('click', (event) => {
                if (!mapMenuAdmin.contains(event.target) && !mapToggleAdmin.contains(event.target)) {
                    mapMenuAdmin.classList.add('hidden');
                }
            });
        }

        document.querySelectorAll('.map-range-pill').forEach((pill) => {
            pill.addEventListener('click', () => {
                mapRangeAdmin = pill.dataset.range;
                const labelEl = document.getElementById('map-range-label-admin');
                if (labelEl) labelEl.textContent = pill.textContent.trim().toUpperCase();
                fetchMapAdmin(mapRangeAdmin);
            });
        });

        document.querySelectorAll('.net-kar-range-pill').forEach((pill) => {
            pill.addEventListener('click', () => {
                netKarRangeAdmin = pill.dataset.range;
                setActiveNetKarPillAdmin(netKarRangeAdmin);
                fetchMetricsAdmin(netKarRangeAdmin);
            });
        });

        const platformToggleAdmin = document.getElementById('platform-toggle-admin');
        const platformMenuAdmin = document.getElementById('platform-menu-admin');
        if (platformToggleAdmin && platformMenuAdmin) {
            platformToggleAdmin.addEventListener('click', (event) => {
                event.stopPropagation();
                platformMenuAdmin.classList.toggle('hidden');
            });
            platformMenuAdmin.querySelectorAll('button[data-platform]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    selectedPlatformAdmin = (btn.dataset.platform || 'all').toString().toLowerCase();
                    const labelEl = document.getElementById('platform-label-admin');
                    if (labelEl) labelEl.textContent = btn.textContent.trim();
                platformMenuAdmin.classList.add('hidden');
                    fetchMetricsAdmin();
                });
            });
            document.addEventListener('click', (event) => {
                if (!platformMenuAdmin.contains(event.target) && !platformToggleAdmin.contains(event.target)) {
                    platformMenuAdmin.classList.add('hidden');
                }
            });
        }

        const rangeToggleAdmin = document.getElementById('net-kar-range-toggle-admin');
        const rangeMenuAdmin = document.getElementById('net-kar-range-menu-admin');
        if (rangeToggleAdmin && rangeMenuAdmin) {
            rangeToggleAdmin.addEventListener('click', (event) => {
                event.stopPropagation();
                rangeMenuAdmin.classList.toggle('hidden');
            });
            const netKarLabelMapAdmin = {
                day: 'GÜNLÜK (GMT+3)',
                week: 'Haftal&#305;k',
                month: 'Ayl&#305;k',
                quarter: '3 Ayl&#305;k',
                half: '6 Ayl&#305;k',
                year: 'Y&#305;ll&#305;k',
            };
            rangeMenuAdmin.querySelectorAll('button[data-range]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    netKarRangeAdmin = btn.dataset.range;
                    const labelEl = document.getElementById('net-kar-range-label-admin');
                    if (labelEl) labelEl.textContent = netKarLabelMapAdmin[netKarRangeAdmin] ?? btn.textContent.trim().toUpperCase();
                rangeMenuAdmin.classList.add('hidden');
                    fetchMetricsAdmin(netKarRangeAdmin);
                });
            });
            document.addEventListener('click', (event) => {
                if (!rangeMenuAdmin.contains(event.target) && !rangeToggleAdmin.contains(event.target)) {
                    rangeMenuAdmin.classList.add('hidden');
                }
            });
        }

    </script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.period-tabs').forEach((row) => {
            const pills = Array.from(row.querySelectorAll('.period-tab'));
            if (!pills.length) return;
            const setActive = (target) => {
                pills.forEach((pill) => pill.classList.toggle('is-active', pill === target));
            };
            setActive(pills[0]);
            pills.forEach((pill) => {
                pill.addEventListener('click', () => setActive(pill));
            });
        });
    });
</script>
@endpush






















































































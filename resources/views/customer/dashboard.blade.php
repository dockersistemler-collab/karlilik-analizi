@extends('layouts.admin')

@section('header', 'Genel Bakış')

@section('content')
<div class="space-y-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            <div class="relative overflow-hidden rounded-3xl border border-emerald-100/80 bg-gradient-to-br from-emerald-50 via-white to-emerald-100/60 p-6 shadow-[0_16px_40px_-28px_rgba(16,185,129,0.55)]">
                <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-emerald-200/35 blur-2xl"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="text-sm font-semibold text-slate-700">G&uuml;nl&uuml;k Gelen Sipariş</div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-emerald-200/80 bg-white/90 text-emerald-600 shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M4 6h16M7 10h10M9 14h6M10 18h4" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
                <div class="relative mt-5 text-6xl font-black tracking-tight text-slate-900" id="kpi-daily-orders-customer">-</div>
                <div class="relative mt-2 text-sm text-slate-600">Bugün gelen sipariş sayısı</div>
            </div>
            <div class="relative overflow-hidden rounded-3xl border border-amber-100/80 bg-gradient-to-br from-amber-50 via-white to-yellow-100/70 p-6 shadow-[0_16px_40px_-28px_rgba(245,158,11,0.5)]">
                <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-amber-200/35 blur-2xl"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="text-sm font-semibold text-slate-700">G&uuml;nl&uuml;k Ürün Satışı</div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-200/80 bg-white/90 text-amber-600 shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M5 7h14l-1.4 8.1a2 2 0 0 1-2 1.7H8.4a2 2 0 0 1-2-1.7L5 7Zm2-3h10" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>
                <div class="relative mt-5 text-6xl font-black tracking-tight text-slate-900" id="kpi-daily-items-customer">-</div>
                <div class="relative mt-2 text-sm text-slate-600">Bugün satılan toplam ürün sayısı</div>
            </div>
            <div class="relative overflow-hidden rounded-3xl border border-sky-100/90 bg-gradient-to-br from-sky-50 via-white to-blue-100/65 p-6 shadow-[0_16px_40px_-28px_rgba(56,189,248,0.5)]">
                <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-sky-200/35 blur-2xl"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="text-sm font-semibold text-slate-700">Bu Ay Gelen Sipariş</div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200/80 bg-white/90 text-sky-600 shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M8 3v3M16 3v3M4 9h16M6 6h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                </div>
                <div class="relative mt-5 text-6xl font-black tracking-tight text-slate-900" id="kpi-monthly-orders-customer">-</div>
                <div class="relative mt-2 text-sm text-slate-600">Bu ay gelen sipariş sayısı</div>
            </div>
            <div class="relative overflow-hidden rounded-3xl border border-rose-100/90 bg-gradient-to-br from-rose-50 via-white to-pink-100/65 p-6 shadow-[0_16px_40px_-28px_rgba(244,114,182,0.45)]">
                <div class="absolute -right-6 -top-6 h-20 w-20 rounded-full bg-rose-200/35 blur-2xl"></div>
                <div class="relative flex items-start justify-between gap-4">
                    <div class="text-sm font-semibold text-slate-700">Toplam Ürün Satışı</div>
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200/80 bg-white/90 text-rose-500 shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 5v14M5 12h14" stroke-linecap="round"/>
                        </svg>
                    </span>
                </div>
                <div class="relative mt-5 text-6xl font-black tracking-tight text-slate-900" id="kpi-monthly-items-customer">-</div>
                <div class="relative mt-2 text-sm text-slate-600">Bu ay gelen toplam ürün sayısı</div>
            </div>
        </div>
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
                        <canvas id="net-kar-chart-customer"></canvas>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col h-[408px] min-h-0">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">G&uuml;nl&uuml;k Pazaryeri Satış</h2>
                    <div class="relative">
                        <button id="platform-toggle-customer" type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                            <span id="platform-label-customer">PLATFORM</span>
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M5.25 7.5 10 12.25 14.75 7.5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                        <div id="platform-menu-customer" class="absolute right-0 z-10 mt-2 hidden w-40 rounded-xl border border-slate-200 bg-white p-2 text-xs shadow-lg">
                            <button type="button" data-platform="all" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">Tümü</button>
                            @foreach(($marketplaces ?? []) as $marketplace)
                                <button type="button" data-platform="{{ $marketplace->code ?? $marketplace->name }}" class="w-full rounded-lg px-3 py-2 text-left text-slate-600 hover:bg-slate-100">{{ $marketplace->name }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <div class="inline-flex items-center gap-4">
                        <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-500">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M5 6h14M7 12h10M9 18h6" stroke-linecap="round"/>
                            </svg>
                        </span>
                        <div class="text-3xl font-semibold text-slate-800 leading-none">GÜNLÜK CİRO</div>
                    </div>
                    <div id="daily-sales-total-customer" class="min-w-[120px] text-right rounded-xl border border-rose-100 px-4 py-3 text-3xl font-bold text-slate-800 shadow-[0_10px_22px_-18px_rgba(251,113,133,0.65)]">
                        0 TL
                    </div>
                </div>

                <div class="mt-5 flex-1 min-h-0 overflow-hidden">
                    <div class="space-y-1.5 pr-1 pb-1" id="daily-sales-marketplaces-customer"></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 items-stretch">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col w-full min-w-0 mini-metric-card">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">En Çok Satılan 10 Ürün</h3>
                </div>
                <div class="mt-3 period-tabs grid grid-cols-3 gap-x-3 gap-y-0.5 items-center w-full">
    <button type="button" data-period="day" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">G&uuml;nl&uuml;k</button>
    <button type="button" data-period="week" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Haftal&#305;k</button>
    <button type="button" data-period="month" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Ayl&#305;k</button>
    <button type="button" data-period="quarter" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">3 Ayl&#305;k</button>
    <button type="button" data-period="half" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">6 Ayl&#305;k</button>
    <button type="button" data-period="year" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Y&#305;ll&#305;k</button>
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
                <div class="mt-3 period-tabs grid grid-cols-3 gap-x-3 gap-y-0.5 items-center w-full">
    <button type="button" data-period="day" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">G&uuml;nl&uuml;k</button>
    <button type="button" data-period="week" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Haftal&#305;k</button>
    <button type="button" data-period="month" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Ayl&#305;k</button>
    <button type="button" data-period="quarter" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">3 Ayl&#305;k</button>
    <button type="button" data-period="half" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">6 Ayl&#305;k</button>
    <button type="button" data-period="year" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Y&#305;ll&#305;k</button>
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
                <div class="mt-3 period-tabs grid grid-cols-3 gap-x-3 gap-y-0.5 items-center w-full">
    <button type="button" data-period="day" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">G&uuml;nl&uuml;k</button>
    <button type="button" data-period="week" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Haftal&#305;k</button>
    <button type="button" data-period="month" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Ayl&#305;k</button>
    <button type="button" data-period="quarter" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">3 Ayl&#305;k</button>
    <button type="button" data-period="half" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">6 Ayl&#305;k</button>
    <button type="button" data-period="year" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Y&#305;ll&#305;k</button>
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
                <div class="mt-3 period-tabs grid grid-cols-3 gap-x-3 gap-y-0.5 items-center w-full">
    <button type="button" data-period="day" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">G&uuml;nl&uuml;k</button>
    <button type="button" data-period="week" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Haftal&#305;k</button>
    <button type="button" data-period="month" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Ayl&#305;k</button>
    <button type="button" data-period="quarter" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">3 Ayl&#305;k</button>
    <button type="button" data-period="half" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">6 Ayl&#305;k</button>
    <button type="button" data-period="year" class="period-tab inline-flex items-center justify-center px-1 py-0.5 text-xs rounded-md transition text-gray-500 hover:text-gray-800 hover:bg-gray-50">Y&#305;ll&#305;k</button>
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

        <div class="mt-6 flex flex-col lg:flex-row gap-4">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col w-full lg:w-[65%]">
                <div class="flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900 whitespace-nowrap">Türkiye Sipariş Dağılımı</h2>
                        <div class="relative">
                            <button id="map-range-toggle-customer" type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600">
                                <span id="map-range-label-customer">Haftal&#305;k</span>
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M5.25 7.5 10 12.25 14.75 7.5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <div id="map-range-menu-customer" class="absolute right-0 z-10 mt-2 hidden w-40 rounded-xl border border-slate-200 bg-white p-2 text-xs shadow-lg">
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
                    <div id="turkey-map-customer" class="h-full w-full"></div>
                </div>
            </div>
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-sm flex flex-col w-full lg:w-[35%]">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">Dağılım Tablosu</h3>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                    <button type="button" data-range="day" class="map-table-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">Günlük</button>
                    <button type="button" data-range="week" class="map-table-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">Haftalık</button>
                    <button type="button" data-range="month" class="map-table-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">Aylık</button>
                    <button type="button" data-range="quarter" class="map-table-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">3 Aylık</button>
                    <button type="button" data-range="half" class="map-table-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">6 Aylık</button>
                    <button type="button" data-range="year" class="map-table-range-pill range-pill rounded-full border border-slate-200 px-3 py-1 text-slate-600">Yıllık</button>
                </div>
                <div class="mt-3">
                    <input id="map-table-search-customer" type="text" placeholder="İl ara..." class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-rose-200">
                </div>
                <div class="mt-3">
                    <div class="max-h-[420px] overflow-auto pr-1">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="text-left text-slate-500">
                                    <th class="pb-2">�?ehir</th>
                                    <th class="pb-2 text-right">Adet</th>
                                </tr>
                            </thead>
                            <tbody id="map-table-body-customer" class="text-slate-700"></tbody>
                        </table>
                    </div>
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
        const customerMapStyles = document.createElement('style');
        customerMapStyles.textContent = `
            #turkey-map-customer .leaflet-pane,
            #turkey-map-customer .leaflet-layer,
            #turkey-map-customer .leaflet-marker-icon,
            #turkey-map-customer .leaflet-marker-shadow,
            #turkey-map-customer .leaflet-tile,
            #turkey-map-customer .leaflet-tile-container,
            #turkey-map-customer .leaflet-pane > svg,
            #turkey-map-customer .leaflet-pane > canvas,
            #turkey-map-customer .leaflet-zoom-animated,
            #turkey-map-customer .leaflet-zoom-animated g,
            #turkey-map-customer .leaflet-interactive {
                transform-origin: center center;
            }
            #turkey-map-customer,
            #turkey-map-customer.leaflet-container,
            #turkey-map-customer .leaflet-container {
                background: transparent !important;
            }
            #turkey-map-customer .leaflet-tooltip {
                background: transparent;
                border: none;
                box-shadow: none;
                padding: 0;
            }
            #turkey-map-customer .map-tooltip {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                gap: 1px;
                min-width: 40px;
            }
            #turkey-map-customer .map-name {
                font-size: 11px;
                font-weight: 700;
                color: #1f2937;
                line-height: 1.1;
                text-align: center;
                text-shadow: 0 1px 0 rgba(255,255,255,0.8);
            }
            #turkey-map-customer .map-count {
                font-size: 11px;
                font-weight: 700;
                color: #f97316;
                line-height: 1;
            }
            #turkey-map-customer .leaflet-interactive {
                transition: stroke 0.15s ease, stroke-width 0.15s ease;
            }
            #turkey-map-customer .leaflet-zoom-box {
                display: none !important;
            }
            .mini-range-row {
                display: flex;
                justify-content: flex-start;
                align-items: center;
                gap: 6px;
                width: 100%;
                text-align: left;
            }
            .mini-range-row .mini-range-pill {
                padding: 0;
                font-size: 6px;
                line-height: 1;
                letter-spacing: -0.08em;
                text-align: left;
                white-space: nowrap;
                transform: scaleX(0.92);
                transform-origin: left center;
            }
            .mini-range-row .mini-range-pill.is-active {
                background: #fda4af;
                border-color: #fb7185;
                color: #7f1d1d; font-weight: 600;
                box-shadow: 0 6px 16px rgba(251, 113, 133, 0.35);
            }
            .mini-metric-card {
                min-height: 380px;
            }
            .range-pill.is-active {
                background: #111827;
                color: #ffffff;
                border-color: #111827;
            }
            .map-label-wrapper {
                background: transparent;
                border: none;
                box-shadow: none;
            }
        `;
        document.head.appendChild(customerMapStyles);
        const metricsEndpointCustomer = @json(route('portal.dashboard.metrics'));
        const mapEndpointCustomer = @json(route('portal.dashboard.map'));
        const netKarCtxCustomer = document.getElementById('net-kar-chart-customer');
        let netKarChartCustomer = null;
        let netKarRangeCustomer = 'day';
        let netKarMetaCustomer = { range: 'day', start: null, end: null };
        let selectedPlatformCustomer = 'all';
        let mapRangeCustomer = 'day';
        let mapTableRangeCustomer = 'day';
        let mapInstanceCustomer = null;
        let mapLayerCustomer = null;
        let activeMapPopupCustomer = null;
        let activeMapLayerCustomer = null;
        let mapDataCustomer = null;
        let tableDataCustomer = null;

        const formatNumber = (value) => Number(value || 0).toLocaleString('tr-TR');
        const formatCurrency = (value) => `${formatNumber(value)} TL`;
        const fallbackMarketplacesCustomer = @json($fallbackMarketplaces ?? []);

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

        const formatNetKarTitleCustomer = (index, labels, timestamps) => {
            const range = netKarMetaCustomer?.range ?? 'week';
            const ts = timestamps?.[index];
            const date = ts ? new Date(ts) : null;
            const baseDate = date ?? (netKarMetaCustomer?.start ? new Date(netKarMetaCustomer.start) : null);
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

        const resolveNetKarScaleCustomer = (range) => {
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

        const applyNetKarScaleCustomer = (values) => {
            const scale = resolveNetKarScaleCustomer(netKarMetaCustomer?.range ?? 'week');
            const maxValue = Math.max(...(values ?? []), 0);
            const maxRounded = scale.max
                ? Math.max(scale.max, Math.ceil(maxValue / scale.max) * scale.max)
                : maxValue;
            return { maxRounded, step: scale.step };
        };

        const updateChartCustomer = (labels, values) => {
            if (!netKarCtxCustomer) return;
            const scale = applyNetKarScaleCustomer(values);
            if (!netKarChartCustomer) {
                netKarChartCustomer = new Chart(netKarCtxCustomer, {
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
                const ts = netKarMetaCustomer?.timestamps ?? [];
                return formatNetKarTitleCustomer(idx, labels, ts);
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

            netKarChartCustomer.data.labels = labels;
            netKarChartCustomer.data.datasets[0].data = values;
            netKarChartCustomer.options.scales.y.min = 0;
            netKarChartCustomer.options.scales.y.suggestedMax = scale.maxRounded;
            if (scale.step) {
                netKarChartCustomer.options.scales.y.ticks.stepSize = scale.step;
            } else {
                delete netKarChartCustomer.options.scales.y.ticks.stepSize;
            }
            netKarChartCustomer.update();
        };

        const marketplaceIconCustomer = (row) => {
            const code = (row.code || row.name || '').toString().toLowerCase();
            if (code.includes('trendyol')) return 'T';
            if (code.includes('hepsi')) return 'H';
            if (code.includes('n11')) return 'N';
            if (code.includes('cicek') || code.includes('çiçek')) return 'Ç';
            if (code.includes('amazon')) return 'A';
            return (row.name || '?').toString().charAt(0).toUpperCase();
        };

        const renderMarketplacesCustomer = (rows) => {
            const container = document.getElementById('daily-sales-marketplaces-customer');
            if (!container) return;
            container.innerHTML = '';

            if (!rows.length) {
                const empty = document.createElement('div');
                empty.className = 'rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-400';
                empty.textContent = 'Veri yok';
                container.appendChild(empty);
                return;
            }

            const palette = ['#f6c338', '#f4d463', '#b8aceb', '#9edfc2', '#0f172a'];
            const textPalette = ['#1f2937', '#1f2937', '#1f2937', '#1f2937', '#f8fafc'];
            const maxValue = Math.max(...rows.map((row) => Number(row.total || 0)), 1);

            rows.forEach((row, index) => {
                const icon = marketplaceIconCustomer(row);
                const ratio = Number(row.total || 0) / maxValue;
                const width = Math.max(28, Math.round(60 + (ratio * 40)));
                const tone = palette[index % palette.length];
                const textColor = textPalette[index % textPalette.length];

                const item = document.createElement('div');
                item.className = 'flex items-center gap-2';
                item.innerHTML = `
                    <div class="h-8 w-8 shrink-0 rounded-lg border border-slate-200 bg-white text-slate-700 flex items-center justify-center font-bold text-sm">${icon}</div>
                    <div class="h-8 rounded-lg px-3 font-semibold text-sm flex items-center" style="width:${width}%; background:${tone}; color:${textColor};">${row.name}</div>
                    <div class="ml-auto text-xl font-bold text-slate-700 whitespace-nowrap">${formatCurrency(row.total)}</div>
                `;
                container.appendChild(item);
            });
        };
        const applyMetricsCustomer = (data) => {
            if (!data) return;
            document.getElementById('kpi-daily-orders-customer').textContent = formatNumber(data.kpis?.daily_orders ?? 0);
            document.getElementById('kpi-daily-items-customer').textContent = formatNumber(data.kpis?.daily_items ?? 0);
            document.getElementById('kpi-monthly-orders-customer').textContent = formatNumber(data.kpis?.monthly_orders ?? 0);
            document.getElementById('kpi-monthly-items-customer').textContent = formatNumber(data.kpis?.monthly_items ?? 0);
            const totalEl = document.getElementById('daily-sales-total-customer');
            const rowsAll = data.daily_sales?.marketplaces ?? [];
            const rows = rowsAll.length ? rowsAll : fallbackMarketplacesCustomer;
            const filtered = selectedPlatformCustomer === 'all'
                ? rows
                : rows.filter((row) => {
                    const key = (row.code || row.name || '').toString().toLowerCase();
                    return key === selectedPlatformCustomer || (row.name || '').toString().toLowerCase() === selectedPlatformCustomer;
                });
            const filteredTotal = filtered.reduce((sum, row) => sum + Number(row.total || 0), 0);
            if (totalEl) {
                totalEl.childNodes[0].textContent = formatCurrency(filteredTotal) + ' ';
            }
            renderMarketplacesCustomer(filtered);
            const chart = data.net_profit_chart ?? {};
            if (chart.range) {
                netKarRangeCustomer = chart.range;
                netKarMetaCustomer = {
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
                const labelEl = document.getElementById('net-kar-range-label-customer');
                if (labelEl) labelEl.textContent = labelMap[chart.range] ?? 'Haftal&#305;k';
                setActiveNetKarPillCustomer(chart.range);
            }
            updateChartCustomer(chart.labels ?? [], chart.values ?? []);
        };

        const fetchMetricsCustomer = async (range = netKarRangeCustomer) => {
            try {
                const url = `${metricsEndpointCustomer}?range=${encodeURIComponent(range)}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                applyMetricsCustomer(data);
            } catch (error) {
                // no-op
            }
        };

        if (netKarCtxCustomer) {
            setActiveNetKarPillCustomer(netKarRangeCustomer);
            fetchMetricsCustomer();
            setInterval(fetchMetricsCustomer, 15000);
        }

        const normalizeMapKeyCustomer = (value = '') => {
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

        const initMapCustomer = () => {
            const container = document.getElementById('turkey-map-customer');
            if (!container || typeof L === 'undefined') return;

            mapInstanceCustomer = L.map(container, {
                zoomControl: false,
                attributionControl: false,
                scrollWheelZoom: false,
                dragging: false,
                doubleClickZoom: false,
                boxZoom: false,
                touchZoom: false,
                keyboard: false,
            }).setView([39.0, 35.0], 5);

            mapInstanceCustomer.boxZoom.disable();
            mapInstanceCustomer.dragging.disable();
            mapInstanceCustomer.touchZoom.disable();
            mapInstanceCustomer.doubleClickZoom.disable();
            mapInstanceCustomer.scrollWheelZoom.disable();
            mapInstanceCustomer.keyboard.disable();
            container.addEventListener('mousedown', (e) => e.preventDefault());
            container.addEventListener('mousemove', (e) => e.preventDefault());

            fetch('/maps/turkey-provinces.geojson')
                .then(response => response.json())
                .then((geojson) => {
                    mapLayerCustomer = L.geoJSON(geojson, {
                        style: () => ({
                            fillColor: '#f1f5f9',
                            color: '#cbd5e1',
                            weight: 1,
                            fillOpacity: 0.5,
                        }),
                        onEachFeature: (feature, layer) => {
                            const name = feature.properties?.name || 'Bilinmeyen';
                            setLayerLabelCustomer(layer, name, 0);
                        },
                    }).addTo(mapInstanceCustomer);

                    const bounds = mapLayerCustomer.getBounds().pad(0.0);
                    mapInstanceCustomer.fitBounds(bounds, { padding: [0, 0] });
                    mapInstanceCustomer.setZoom(mapInstanceCustomer.getZoom() + 0.7);
                    setTimeout(() => mapInstanceCustomer.invalidateSize(), 0);

                    if (mapDataCustomer) {
                        updateMapCustomer(mapDataCustomer);
                    }
                    if (tableDataCustomer || mapDataCustomer) {
                        renderTableCustomer(tableDataCustomer ?? mapDataCustomer ?? {});
                    }
                });
        };

        const setLayerLabelCustomer = (layer, name, value) => {
            const tooltipContent = `<div class="map-tooltip"><span class="map-name">${name}</span><span class="map-count">${value} adet</span></div>`;
            if (layer.getTooltip()) {
                layer.setTooltipContent(tooltipContent);
            } else {
                layer.bindTooltip(tooltipContent, {
                    permanent: true,
                    direction: 'center',
                    className: 'map-label-wrapper',
                });
            }

            if (layer.getBounds && layer.getTooltip()) {
                const center = layer.getBounds().getCenter();
                layer.getTooltip().setLatLng(center);
            }
        };

        const renderTableCustomer = (mapData) => {
            if (!mapLayerCustomer) return;
            const tableBody = document.getElementById('map-table-body-customer');
            if (!tableBody) return;

            const searchInput = document.getElementById('map-table-search-customer');
            const searchTerm = searchInput?.value?.trim().toLowerCase() ?? '';
            const tableRows = [];

            mapLayerCustomer.eachLayer((layer) => {
                const name = layer.feature?.properties?.name || '';
                const key = normalizeMapKeyCustomer(name);
                const value = mapData?.[key] ?? 0;
                tableRows.push({ name, value });
            });

            tableBody.innerHTML = '';
            tableRows
                .sort((a, b) => b.value - a.value || a.name.localeCompare(b.name, 'tr'))
                .filter((row) => (searchTerm ? row.name.toLowerCase().includes(searchTerm) : true))
                .forEach((row) => {
                    const tr = document.createElement('tr');
                    tr.className = 'border-b border-slate-100 last:border-0';

                    const nameTd = document.createElement('td');
                    nameTd.className = 'py-2 font-semibold';
                    nameTd.textContent = row.name;

                    const valueTd = document.createElement('td');
                    valueTd.className = 'py-2 text-right font-semibold text-slate-900';
                    valueTd.textContent = row.value.toLocaleString('tr-TR');

                    tr.appendChild(nameTd);
                    tr.appendChild(valueTd);
                    tableBody.appendChild(tr);
                });
        };

        const updateMapCustomer = (mapData) => {
            if (!mapLayerCustomer) {
                mapDataCustomer = mapData;
                return;
            }
            const values = Object.values(mapData || {});
            const maxValue = Math.max(...values, 1);

            mapLayerCustomer.eachLayer((layer) => {
                const name = layer.feature?.properties?.name || '';
                const key = normalizeMapKeyCustomer(name);
                const value = mapData?.[key] ?? 0;
                const lightness = 70 - Math.min(50, (value / maxValue) * 50);
                layer.setStyle({
                    fillColor: value ? `hsl(26, 72%, ${lightness}%)` : '#f1f5f9',
                    fillOpacity: value ? 0.82 : 0.4,
                });
                setLayerLabelCustomer(layer, name, value);

                layer.on('mouseover', () => {
                    layer.setStyle({ weight: 2.5, color: '#f97316' });
                });
                layer.on('mouseout', () => {
                    if (activeMapLayerCustomer === layer) return;
                    layer.setStyle({ weight: 1, color: '#cbd5e1' });
                });

                layer.off('click').on('click', (event) => {
                    if (!mapInstanceCustomer) return;
                    if (activeMapLayerCustomer === layer) {
                        if (activeMapPopupCustomer) {
                            mapInstanceCustomer.closePopup(activeMapPopupCustomer);
                            activeMapPopupCustomer = null;
                        }
                        activeMapLayerCustomer = null;
                        layer.setStyle({ weight: 1, color: '#cbd5e1' });
                        return;
                    }

                    if (activeMapLayerCustomer) {
                        activeMapLayerCustomer.setStyle({ weight: 1, color: '#cbd5e1' });
                    }

                    activeMapLayerCustomer = layer;
                    layer.setStyle({ weight: 3.2, color: '#334155' });

                    const popupContent = `<div class="map-tooltip"><span class="map-name">${name}</span><span class="map-count">${value} adet</span></div>`;
                    if (activeMapPopupCustomer) {
                        mapInstanceCustomer.closePopup(activeMapPopupCustomer);
                    }
                    activeMapPopupCustomer = L.popup({
                        closeButton: true,
                        autoClose: true,
                        closeOnClick: true,
                        className: 'map-popup'
                    })
                        .setLatLng(event.latlng)
                        .setContent(popupContent)
                        .openOn(mapInstanceCustomer);
                });
            });
        };

        function setActiveMapPillCustomer(range) {
            document.querySelectorAll('.map-range-pill').forEach((pill) => {
                if (pill.dataset.range === range) {
                    pill.classList.add('is-active');
                    pill.classList.add('text-white');
                    pill.style.backgroundColor = '#fca5a5';
                    pill.style.borderColor = '#f87171';
                    pill.style.color = '#111827';
                    pill.style.boxShadow = '0 2px 8px rgba(248,113,113,0.35)';
                } else {
                    pill.classList.remove('is-active');
                    pill.classList.remove('text-white');
                    pill.style.backgroundColor = '';
                    pill.style.borderColor = '';
                    pill.style.color = '';
                    pill.style.boxShadow = '';
                }
            });
        }

        function setActiveMapTablePillCustomer(range) {
            document.querySelectorAll('.map-table-range-pill').forEach((pill) => {
                if (pill.dataset.range === range) {
                    pill.classList.add('is-active');
                    pill.classList.add('text-white');
                    pill.style.backgroundColor = '#fca5a5';
                    pill.style.borderColor = '#f87171';
                    pill.style.color = '#111827';
                    pill.style.boxShadow = '0 2px 8px rgba(248,113,113,0.35)';
                } else {
                    pill.classList.remove('is-active');
                    pill.classList.remove('text-white');
                    pill.style.backgroundColor = '';
                    pill.style.borderColor = '';
                    pill.style.color = '';
                    pill.style.boxShadow = '';
                }
            });
        }

        function setActiveNetKarPillCustomer(range) {
            document.querySelectorAll('.net-kar-range-pill').forEach((pill) => {
                if (pill.dataset.range === range) {
                    pill.classList.add('is-active');
                    pill.classList.add('text-white');
                    pill.style.backgroundColor = '#fca5a5';
                    pill.style.borderColor = '#f87171';
                    pill.style.color = '#111827';
                    pill.style.boxShadow = '0 2px 8px rgba(248,113,113,0.35)';
                } else {
                    pill.classList.remove('is-active');
                    pill.classList.remove('text-white');
                    pill.style.backgroundColor = '';
                    pill.style.borderColor = '';
                    pill.style.color = '';
                    pill.style.boxShadow = '';
                }
            });
        }
        const fetchMapCustomer = async (range = mapRangeCustomer) => {
            try {
                const url = `${mapEndpointCustomer}?range=${encodeURIComponent(range)}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                mapDataCustomer = data.map || {};
                updateMapCustomer(mapDataCustomer);
                setActiveMapPillCustomer(range);
                if (!tableDataCustomer) {
                    tableDataCustomer = mapDataCustomer;
                    renderTableCustomer(tableDataCustomer);
                }
            } catch (error) {
                // no-op
            }
        };

        const fetchMapTableCustomer = async (range = mapTableRangeCustomer) => {
            try {
                const url = `${mapEndpointCustomer}?range=${encodeURIComponent(range)}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                tableDataCustomer = data.map || {};
                setActiveMapTablePillCustomer(range);
                renderTableCustomer(tableDataCustomer);
            } catch (error) {
                // no-op
            }
        };

        initMapCustomer();
        if (mapEndpointCustomer) {
            setActiveMapPillCustomer(mapRangeCustomer);
            setActiveMapTablePillCustomer(mapTableRangeCustomer);
            fetchMapCustomer();
            fetchMapTableCustomer(mapTableRangeCustomer);
            setInterval(fetchMapCustomer, 60000);
        }

        const mapToggleCustomer = document.getElementById('map-range-toggle-customer');
        const mapMenuCustomer = document.getElementById('map-range-menu-customer');
        if (mapToggleCustomer && mapMenuCustomer) {
            mapToggleCustomer.addEventListener('click', (event) => {
                event.stopPropagation();
                mapMenuCustomer.classList.toggle('hidden');
            });
            mapMenuCustomer.querySelectorAll('button[data-range]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    mapRangeCustomer = btn.dataset.range;
                    const labelEl = document.getElementById('map-range-label-customer');
                    if (labelEl) labelEl.textContent = btn.textContent.trim().toUpperCase();
                mapMenuCustomer.classList.add('hidden');
                    fetchMapCustomer(mapRangeCustomer);
                });
            });
            document.addEventListener('click', (event) => {
                if (!mapMenuCustomer.contains(event.target) && !mapToggleCustomer.contains(event.target)) {
                    mapMenuCustomer.classList.add('hidden');
                }
            });
        }

        document.querySelectorAll('.map-range-pill').forEach((pill) => {
            pill.addEventListener('click', () => {
                mapRangeCustomer = pill.dataset.range;
                const labelEl = document.getElementById('map-range-label-customer');
                if (labelEl) labelEl.textContent = pill.textContent.trim().toUpperCase();
                fetchMapCustomer(mapRangeCustomer);
            });
        });

        document.querySelectorAll('.map-table-range-pill').forEach((pill) => {
            pill.addEventListener('click', () => {
                mapTableRangeCustomer = pill.dataset.range;
                fetchMapTableCustomer(mapTableRangeCustomer);
            });
        });

        const mapTableSearchCustomer = document.getElementById('map-table-search-customer');
        if (mapTableSearchCustomer) {
            mapTableSearchCustomer.addEventListener('input', () => {
                renderTableCustomer(tableDataCustomer ?? mapDataCustomer);
            });
        }

        document.querySelectorAll('.net-kar-range-pill').forEach((pill) => {
            pill.addEventListener('click', () => {
                netKarRangeCustomer = pill.dataset.range;
                setActiveNetKarPillCustomer(netKarRangeCustomer);
                fetchMetricsCustomer(netKarRangeCustomer);
            });
        });

        const platformToggleCustomer = document.getElementById('platform-toggle-customer');
        const platformMenuCustomer = document.getElementById('platform-menu-customer');
        if (platformToggleCustomer && platformMenuCustomer) {
            platformToggleCustomer.addEventListener('click', (event) => {
                event.stopPropagation();
                platformMenuCustomer.classList.toggle('hidden');
            });
            platformMenuCustomer.querySelectorAll('button[data-platform]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    selectedPlatformCustomer = (btn.dataset.platform || 'all').toString().toLowerCase();
                    const labelEl = document.getElementById('platform-label-customer');
                    if (labelEl) labelEl.textContent = btn.textContent.trim();
                platformMenuCustomer.classList.add('hidden');
                    fetchMetricsCustomer();
                });
            });
            document.addEventListener('click', (event) => {
                if (!platformMenuCustomer.contains(event.target) && !platformToggleCustomer.contains(event.target)) {
                    platformMenuCustomer.classList.add('hidden');
                }
            });
        }

        const rangeToggleCustomer = document.getElementById('net-kar-range-toggle-customer');
        const rangeMenuCustomer = document.getElementById('net-kar-range-menu-customer');
        if (rangeToggleCustomer && rangeMenuCustomer) {
            rangeToggleCustomer.addEventListener('click', (event) => {
                event.stopPropagation();
                rangeMenuCustomer.classList.toggle('hidden');
            });
            const netKarLabelMapCustomer = {
                day: 'GÜNLÜK (GMT+3)',
                week: 'Haftal&#305;k',
                month: 'Ayl&#305;k',
                quarter: '3 Ayl&#305;k',
                half: '6 Ayl&#305;k',
                year: 'Y&#305;ll&#305;k',
            };
            rangeMenuCustomer.querySelectorAll('button[data-range]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    event.stopPropagation();
                    netKarRangeCustomer = btn.dataset.range;
                    const labelEl = document.getElementById('net-kar-range-label-customer');
                    if (labelEl) labelEl.textContent = netKarLabelMapCustomer[netKarRangeCustomer] ?? btn.textContent.trim().toUpperCase();
                rangeMenuCustomer.classList.add('hidden');
                    fetchMetricsCustomer(netKarRangeCustomer);
                });
            });
            document.addEventListener('click', (event) => {
                if (!rangeMenuCustomer.contains(event.target) && !rangeToggleCustomer.contains(event.target)) {
                    rangeMenuCustomer.classList.add('hidden');
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
















































































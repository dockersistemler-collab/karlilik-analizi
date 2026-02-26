@extends('layouts.admin')

@section('header')
    Pazaryeri Zeka Kontrol Kulesi
@endsection

@section('content')
@php
    $cfo = (array) data_get($payload, 'cfo', []);
    $ops = (array) data_get($payload, 'ops', []);
    $risk = (array) data_get($payload, 'risk', []);
    $campaigns = (array) data_get($payload, 'campaigns', []);
    $setupNeeded = (array) data_get($payload, 'widgets.setup_needed', []);

    $profitLeak = (array) data_get($cfo, 'profit_leak_breakdown', []);
    $health = (float) data_get($cfo, 'account_health_score', 0);
    $healthTone = $health >= 70 ? 'text-emerald-600' : ($health >= 45 ? 'text-amber-600' : 'text-rose-600');
    $healthBar = max(0, min(100, $health));
@endphp

<style>
    .ct-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 18px;
    }
    .ct-glass {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(148, 163, 184, 0.22);
        border-radius: 14px;
    }
    .ct-kpi {
        border-radius: 12px;
        border: 1px solid #dbe3ef;
        background: linear-gradient(145deg, #ffffff, #f8fbff);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
    }
    .ct-chip {
        border: 1px solid #dbe3ef;
        border-radius: 999px;
        padding: 0.28rem 0.65rem;
        font-size: 0.74rem;
        font-weight: 600;
        color: #334155;
        background: #f8fafc;
    }
</style>

<div class="ct-shell space-y-4">
    <div class="ct-glass p-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <div class="text-[11px] uppercase tracking-[0.18em] text-slate-500">Yönetici Finansal Görünümü</div>
                <h2 class="text-2xl font-semibold text-slate-900 mt-1">Pazaryeri Zeka Kontrol Kulesi</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="ct-chip">Tarih: {{ $date->format('d.m.Y') }}</span>
                <span class="ct-chip">Aralık: {{ strtoupper($range) }}</span>
                @if($marketplace)
                    <span class="ct-chip">Pazaryeri: {{ strtoupper($marketplace) }}</span>
                @endif
            </div>
        </div>
        <form method="GET" action="{{ route('portal.control-tower.index') }}" class="grid grid-cols-1 md:grid-cols-7 gap-2 mt-4">
            <select name="view" class="border border-slate-200 rounded px-3 py-2 bg-white">
                <option value="cfo" @selected($viewMode === 'cfo')>CFO</option>
                <option value="ops" @selected($viewMode === 'ops')>OPS</option>
            </select>
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="border border-slate-200 rounded px-3 py-2 bg-white">
            <select name="range" class="border border-slate-200 rounded px-3 py-2 bg-white">
                <option value="30d" @selected($range === '30d')>Son 30 gün</option>
                <option value="7d" @selected($range === '7d')>Son 7 gün</option>
            </select>
            <select name="marketplace" class="border border-slate-200 rounded px-3 py-2 bg-white">
                <option value="">Tüm pazaryerleri</option>
                @foreach(['trendyol','hepsiburada','amazon','n11'] as $mp)
                    <option value="{{ $mp }}" @selected($marketplace === $mp)>{{ strtoupper($mp) }}</option>
                @endforeach
            </select>
            <select name="severity" class="border border-slate-200 rounded px-3 py-2 bg-white">
                <option value="">Tüm seviyeler</option>
                @foreach(['info','warning','critical'] as $sev)
                    <option value="{{ $sev }}" @selected(request('severity') === $sev)>{{ strtoupper($sev) }}</option>
                @endforeach
            </select>
            <button class="btn btn-solid-accent">Filtreleri Uygula</button>
            <div class="flex gap-2">
                <a href="{{ route('portal.control-tower.index', ['view' => $viewMode]) }}" class="btn btn-outline">Sıfırla</a>
                <button name="refresh" value="1" class="btn btn-outline">Yenile</button>
            </div>
        </form>
    </div>

    @if($setupNeeded !== [])
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @foreach($setupNeeded as $row)
                @include('admin.control-tower.components.ct-alert-card', [
                    'title' => 'Kurulum Gerekli: '.strtoupper((string) data_get($row, 'source')),
                    'message' => (string) data_get($row, 'message'),
                    'severity' => 'warning',
                ])
            @endforeach
        </div>
    @endif

    @if($viewMode === 'cfo')
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
            <div class="xl:col-span-4 ct-kpi p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Kâr ve Marj</h3>
                    <span class="text-xs text-slate-400">30D</span>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div class="rounded-xl bg-emerald-50 border border-emerald-100 p-3">
                        <div class="text-xs text-slate-500">Net Kâr</div>
                        <div class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format((float) data_get($cfo, 'net_profit_30d', 0), 2) }}</div>
                        <div class="text-xs text-emerald-700 mt-1">{{ number_format((float) data_get($cfo, 'net_profit_change_pct', 0), 2) }}% önceki döneme göre</div>
                    </div>
                    <div class="rounded-xl bg-indigo-50 border border-indigo-100 p-3">
                        <div class="text-xs text-slate-500">Ort. Marj</div>
                        <div class="text-3xl font-bold text-indigo-700 mt-1">%{{ number_format((float) data_get($cfo, 'avg_margin_30d', 0), 2) }}</div>
                        <div class="text-xs text-indigo-700 mt-1">Hedef: 20%</div>
                    </div>
                </div>
                <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-slate-600">Ciro</span>
                        <strong>{{ number_format((float) data_get($cfo, 'revenue_30d', 0), 2) }}</strong>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                        <span class="text-slate-600">Maliyet Sızıntısı</span>
                        <strong class="text-rose-600">{{ number_format((float) data_get($cfo, 'cost_leak_30d', 0), 2) }}</strong>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-4 ct-kpi p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Nakit Akışı Projeksiyonu</h3>
                    <span class="text-xs text-slate-400">Tahmin</span>
                </div>
                <div class="mt-3 rounded-xl border border-sky-100 bg-sky-50 p-3">
                    <div class="text-xs text-slate-500">30 Günlük Tahmin</div>
                    <div class="text-3xl font-bold text-sky-700 mt-1">{{ number_format((float) data_get($cfo, 'cashflow_30d_forecast', 0), 2) }}</div>
                </div>
                <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                    @php($trend = collect((array) data_get($cfo, 'cashflow_trend', []))->pluck('net_profit')->all())
                    @include('admin.control-tower.components.ct-mini-trend', ['values' => $trend, 'width' => 420, 'height' => 80, 'stroke' => '#2563eb'])
                </div>
            </div>

            <div class="xl:col-span-4 ct-kpi p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Hesap Sağlığı</h3>
                    <span class="text-xs text-slate-400">Risk Seviyesi</span>
                </div>
                <div class="mt-3">
                    <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-emerald-500 via-amber-400 to-rose-500" style="width: {{ $healthBar }}%"></div>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <div class="text-sm text-slate-500">Skor</div>
                        <div class="text-3xl font-bold {{ $healthTone }}">{{ number_format($health, 1) }}</div>
                    </div>
                </div>
                <div class="mt-3 space-y-2 text-sm">
                    <div class="flex items-center justify-between"><span>Kritik Sorunlar</span><strong class="text-rose-600">{{ (int) data_get($risk, 'critical_count', 0) }}</strong></div>
                    <div class="flex items-center justify-between"><span>Geç Kargo</span><strong>{{ number_format((float) data_get($ops, 'late_shipments_delta_7d.value', 0), 4) }}</strong></div>
                    <div class="flex items-center justify-between"><span>İade Oranı</span><strong>{{ number_format((float) data_get($ops, 'return_rate_delta_7d.value', 0), 4) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
            <div class="xl:col-span-8 ct-kpi p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Kâr Sızıntısı Analizi</h3>
                    <a href="{{ route('portal.control-tower.drill.profit-leak', ['date' => $date->toDateString()]) }}" class="btn btn-solid-accent px-3 py-1 text-xs">Düzelt / Git</a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-3">
                    @foreach($profitLeak as $name => $value)
                        <div class="rounded-xl p-3 border border-slate-200 bg-gradient-to-r from-orange-50 to-rose-50">
                            <div class="text-[11px] uppercase tracking-wide text-slate-500">{{ str_replace('_', ' ', (string) $name) }}</div>
                            <div class="text-xl font-bold text-rose-600 mt-1">{{ number_format((float) $value, 2) }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 overflow-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b">
                                <th class="px-2 py-2">Kâr Kaybı Kırılımı</th>
                                <th class="px-2 py-2">Etki</th>
                                <th class="px-2 py-2">Aksiyon</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($profitLeak as $name => $value)
                                <tr class="border-b border-slate-100">
                                    <td class="px-2 py-2 font-semibold">{{ ucwords(str_replace('_', ' ', (string) $name)) }}</td>
                                    <td class="px-2 py-2">{{ number_format((float) $value, 2) }}</td>
                                    <td class="px-2 py-2">
                                        <a href="{{ route('portal.control-tower.drill.profit-leak', ['date' => $date->toDateString()]) }}" class="btn btn-outline px-2 py-1 text-xs">Düzelt</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="xl:col-span-4 ct-kpi p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">BuyBox ve Trafik Etkisi</h3>
                    <a href="{{ route('portal.control-tower.drill.buybox', ['date' => $date->toDateString()]) }}" class="btn btn-outline px-2 py-1 text-xs">İncele</a>
                </div>
                <div class="text-sm mt-3">Kazanma Oranı: <strong>%{{ number_format(((float) data_get($ops, 'buybox_win_rate_overall', 0))*100, 2) }}</strong></div>
                <div class="text-sm">Kaybeden SKU: <strong>{{ (int) data_get($ops, 'losing_sku_count', 0) }}</strong></div>
                <div class="mt-3 space-y-2">
                    @foreach((array) data_get($ops, 'buybox_win_rate_per_marketplace', []) as $mp => $rate)
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                            <span class="text-sm">{{ strtoupper($mp) }}</span>
                            <strong class="text-emerald-600">%{{ number_format(((float) $rate) * 100, 2) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
            <div class="xl:col-span-7 ct-kpi p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">BuyBox Savunması</h3>
                    <a href="{{ route('portal.control-tower.drill.buybox', ['date' => $date->toDateString()]) }}" class="btn btn-solid-accent px-3 py-1 text-xs">Fiyatı Kontrol Et ve Düzelt</a>
                </div>
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="text-xs text-slate-500">Kazanma Oranı</div>
                        <div class="text-3xl font-bold text-indigo-700 mt-1">%{{ number_format(((float) data_get($ops, 'buybox_win_rate_overall', 0))*100, 2) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <div class="text-xs text-slate-500">Kaybeden SKU</div>
                        <div class="text-3xl font-bold text-rose-600 mt-1">{{ (int) data_get($ops, 'losing_sku_count', 0) }}</div>
                    </div>
                </div>
                <div class="mt-3 rounded-xl border border-slate-200 bg-white p-3">
                    @php($bbTrend = collect((array) data_get($ops, 'buybox_trend', []))->pluck('win_rate')->map(fn ($v) => ((float) $v) * 100)->all())
                    @include('admin.control-tower.components.ct-mini-trend', ['values' => $bbTrend, 'width' => 520, 'height' => 88, 'stroke' => '#1d4ed8'])
                </div>
            </div>

            <div class="xl:col-span-5 ct-kpi p-4">
                <h3 class="font-semibold text-slate-800">Altyapı Uyarıları</h3>
                <div class="mt-3 space-y-2">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm flex justify-between"><span>Mağaza puanı düşüşü</span><strong>{{ count((array) data_get($ops, 'store_score_delta_7d', [])) }}</strong></div>
                    <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm flex justify-between"><span>Geç kargo artışı</span><strong>{{ number_format((float) data_get($ops, 'late_shipments_delta_7d.value', 0), 4) }}</strong></div>
                    <div class="rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm flex justify-between"><span>İade artışı</span><strong>{{ number_format((float) data_get($ops, 'return_rate_delta_7d.value', 0), 4) }}</strong></div>
                    <div class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-sm flex justify-between"><span>Algoritma uyarıları</span><strong>{{ (int) data_get($ops, 'algorithm_alert_count', 0) }}</strong></div>
                </div>

                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-3">
                    <div class="text-sm font-semibold text-slate-800">Mağaza Puanı Nasıl Hesaplanır?</div>
                    <div class="text-xs text-slate-600 mt-2 space-y-1">
                        <div>Veri kaynağı: <code>marketplace_offer_snapshots.store_score</code></div>
                        <div>BuyBox skorlama ağırlıkları (varsayılan):</div>
                        <div class="grid grid-cols-2 gap-x-3 gap-y-1">
                            <span>Fiyat rekabeti</span><strong>%35</strong>
                            <span>Mağaza puanı</span><strong>%25</strong>
                            <span>Kargo hızı</span><strong>%20</strong>
                            <span>Stok durumu</span><strong>%10</strong>
                            <span>Promosyon</span><strong>%10</strong>
                        </div>
                        <div class="pt-1">Control Tower sinyali: <code>STORE_SCORE_DROP</code> (7 günlük ortalama, 30 gün ortalamasından anlamlı düşükse).</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
            <div class="xl:col-span-6 ct-kpi p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Algoritma Uyarısı</h3>
                    <a href="{{ route('portal.control-tower.drill.campaigns', ['date' => $date->toDateString()]) }}" class="btn btn-outline px-2 py-1 text-xs">Detaya İn</a>
                </div>
                <div class="text-sm mt-3">Olası değişim adedi: <strong>{{ (int) data_get($ops, 'algorithm_alert_count', 0) }}</strong></div>
            </div>
            <div class="xl:col-span-6 ct-kpi p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-slate-800">Kampanya ve Şok Takibi</h3>
                    <a href="{{ route('portal.control-tower.drill.campaigns', ['date' => $date->toDateString()]) }}" class="btn btn-outline px-2 py-1 text-xs">Aç</a>
                </div>
                <div class="grid grid-cols-2 gap-2 mt-3 text-sm">
                    <div class="rounded-lg border border-slate-200 p-2">Kampanyalar: <strong>{{ (int) data_get($campaigns, 'campaign_count', 0) }}</strong></div>
                    <div class="rounded-lg border border-slate-200 p-2">İçe Aktarım: <strong>{{ (int) data_get($campaigns, 'import_campaign_count', 0) }}</strong></div>
                    <div class="rounded-lg border border-slate-200 p-2">Şoklar: <strong>{{ (int) data_get($campaigns, 'shock_count', 0) }}</strong></div>
                    <div class="rounded-lg border border-slate-200 p-2">Promosyon Günleri: <strong>{{ (int) data_get($campaigns, 'promo_day_count', 0) }}</strong></div>
                </div>
            </div>
        </div>

        <div class="ct-kpi p-4">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-semibold text-slate-800">Görev / Aksiyon Kuyruğu</h3>
                <a href="{{ route('portal.control-tower.drill.actions', ['date' => $date->toDateString()]) }}" class="btn btn-outline px-2 py-1 text-xs">Tümünü Gör</a>
            </div>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="px-2 py-2">Seviye</th>
                            <th class="px-2 py-2">Tür</th>
                            <th class="px-2 py-2">Başlık</th>
                            <th class="px-2 py-2">Etki</th>
                            <th class="px-2 py-2">Aksiyon</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(collect((array) data_get($ops, 'task_queue', []))->sortByDesc(fn($r) => in_array(data_get($r, 'severity'), ['critical','high'], true) ? 2 : 1) as $task)
                            <tr class="border-b border-slate-100">
                                <td class="px-2 py-2 uppercase">{{ data_get($task, 'severity') }}</td>
                                <td class="px-2 py-2">{{ data_get($task, 'action_type') }}</td>
                                <td class="px-2 py-2">{{ data_get($task, 'title') }}</td>
                                <td class="px-2 py-2 text-xs">
                                    @php($delta = data_get($task, 'impact_delta'))
                                    {{ is_array($delta) ? json_encode($delta, JSON_UNESCAPED_UNICODE) : '-' }}
                                </td>
                                <td class="px-2 py-2">
                                    <div class="flex gap-1">
                                        <form method="POST" action="{{ route('portal.action-engine.apply', (int) data_get($task, 'id')) }}">
                                            @csrf
                                            <button class="btn btn-solid-accent px-2 py-1 text-xs">Uygula</button>
                                        </form>
                                        <form method="POST" action="{{ route('portal.action-engine.dismiss', (int) data_get($task, 'id')) }}">
                                            @csrf
                                            <button class="btn btn-outline px-2 py-1 text-xs">Reddet</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-2 py-4 text-center text-slate-500">Açık görev yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @include('admin.control-tower.components.ct-signal-list', [
        'signals' => collect($signals)->take(12)->values()->all(),
        'title' => 'Sinyal -> Neden -> Aksiyon',
    ])
</div>
@endsection

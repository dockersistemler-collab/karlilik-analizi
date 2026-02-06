@extends('layouts.admin')



@section('header')

    Genel BakıÅŸ

@endsection



@section('content')

    @if(!empty($isPortal) && $portalBilling)

        @php

            $badgeClass = match ($portalBilling['badge'] ?? 'unknown') {

                'active' => 'badge badge-success',

                'past_due' => 'badge badge-warning',

                'canceled' => 'badge badge-danger',

                default => 'badge badge-muted',

            };

            $statusLabel = match ($portalBilling['badge'] ?? 'unknown') {

                'active' => 'active',

                'past_due' => 'past_due',

                'canceled' => 'canceled',

                default => 'unknown',

            };

        @endphp

        <div class="panel-card p-4 mb-6 flex flex-col gap-3">

            <div class="flex flex-wrap items-center gap-3">

                <h3 class="text-sm font-semibold text-slate-800">Subscription Status</h3>

                <span class="{{ $badgeClass }}">{{ $statusLabel }}</span>

            </div>

            @if(($portalBilling['is_past_due'] ?? false))

                <div class="panel-card px-4 py-3 border border-amber-200 text-amber-800 bg-amber-50/60 flex flex-col gap-2">

                    <div class="font-semibold text-sm">Odeme alinamadi</div>

                    <div class="text-xs text-amber-700">

                        Odeme tekrar denenecek. Kartinizi guncelleyerek kesintisiz devam edebilirsiniz.

                    </div>

                    <div class="text-xs text-amber-700">

                        Bu bildirim Portal ana sayfada ( /portal ) gorunur.

                    </div>

                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">

                        <div class="text-xs text-amber-700">

                            @if($portalBilling['next_retry_at'])

                                Sonraki deneme: {{ optional($portalBilling['next_retry_at'])->format('d.m.Y H:i') }}

                            @else

                                Sonraki deneme: -

                            @endif

                            @if(!empty($portalBilling['last_failure_message']))

                                <span class="block">Son hata: {{ $portalBilling['last_failure_message'] }}</span>

                            @endif

                        </div>

                        <a href="{{ route('portal.billing.card-update') }}" class="btn btn-outline-accent">Kart Guncelle</a>

                    </div>

                </div>

            @endif

        </div>

    @endif



    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

        @foreach($kpis as $kpi)

            <div class="rounded-2xl p-5 text-white shadow-lg" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.95), rgba(14, 116, 144, 0.95));">

                <div class="flex items-start justify-between">

                    <div class="flex items-center justify-center h-9 w-9 rounded-xl bg-white/20 text-white">

                        <i class="fa-solid {{ $kpi['icon'] }} text-sm"></i>

                    </div>

                    <span class="text-[10px] uppercase tracking-[0.24em] text-white/70">{{ $kpi['title'] }}</span>

                </div>

                <div class="mt-4 text-3xl font-semibold">

                    {{ $kpi['value'] }} <span class="text-base font-medium text-white/80">{{ $kpi['unit'] }}</span>

                </div>

                <p class="mt-2 text-xs text-white/75">{{ $kpi['description'] }}</p>

            </div>

        @endforeach

    </div>



        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <div class="panel-card p-6 lg:col-span-2">

                <div class="flex items-center justify-between mb-4">

                    <h3 class="text-sm font-semibold text-slate-700">Satılan Ãœrünler İstatistikleri</h3>

                    <span class="text-xs text-slate-400">Grafik alanı</span>

                </div>

                <div class="flex flex-wrap items-center gap-2 mb-4">

                <a href="{{ route('portal.dashboard', ['range' => 'day']) }}"

                   class="btn {{ $range === 'day' ? 'btn-solid-accent' : 'btn-outline' }}">Günlük</a>

                <a href="{{ route('portal.dashboard', ['range' => 'week']) }}"

                   class="btn {{ $range === 'week' ? 'btn-solid-accent' : 'btn-outline' }}">Haftalık</a>

                <a href="{{ route('portal.dashboard', ['range' => 'month']) }}"

                   class="btn {{ $range === 'month' ? 'btn-solid-accent' : 'btn-outline' }}">Aylık</a>

                <a href="{{ route('portal.dashboard', ['range' => 'year']) }}"

                   class="btn {{ $range === 'year' ? 'btn-solid-accent' : 'btn-outline' }}">Yıllık</a>

                </div>

                <div class="h-[360px] md:h-[460px] lg:h-[560px] xl:h-[620px] rounded-xl border border-dashed border-slate-200 bg-slate-50/60 relative overflow-hidden">

                    <div id="turkey-map" class="absolute inset-0"></div>

                </div>

                <style>

            .map-label {

                display: inline-flex;

                flex-direction: column;

                align-items: center;

                gap: 0.1rem;

                min-width: 96px;

            }

            .map-label .map-name {

                font-size: 0.55rem;

                font-weight: 600;

                text-transform: uppercase;

                letter-spacing: 0.04em;

                color: #0f172a;

            }

            .map-label .map-count {

                font-size: 0.5rem;

                font-weight: 600;

                color: #d97706;

            }

            .leaflet-tooltip.map-label-wrapper {

                background: transparent;

                border: none;

                box-shadow: none;

                padding: 0;

                border-radius: 0;

            }

            @media (max-width: 1024px) {

                .map-label { min-width: 76px; }

                .map-label .map-name { font-size: 0.52rem; }

                .map-label .map-count { font-size: 0.46rem; }

            }

            @media (max-width: 640px) {

                .map-label { min-width: 60px; }

                .map-label .map-name { font-size: 0.48rem; letter-spacing: 0.02em; }

                .map-label .map-count { font-size: 0.42rem; }

            }

        </style>

        </div>



        <div class="panel-card p-6">

            <h3 class="text-sm font-semibold text-slate-700 mb-4">Duyurular ve Yenilikler</h3>

            <div class="space-y-4">

                <div class="flex items-start gap-3">

                    <div class="h-8 w-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center">

                        <i class="fa-solid fa-bullhorn text-xs"></i>

                    </div>

                    <div>

                        <p class="text-sm text-slate-700">Yeni rapor filtreleri yayında.</p>

                        <p class="text-xs text-slate-400">28 Ocak 2026 · 09:30</p>

                    </div>

                </div>

                <div class="flex items-start gap-3">

                    <div class="h-8 w-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center">

                        <i class="fa-solid fa-circle-check text-xs"></i>

                    </div>

                    <div>

                        <p class="text-sm text-slate-700">Stok raporu güncellendi.</p>

                        <p class="text-xs text-slate-400">27 Ocak 2026 · 17:45</p>

                    </div>

                </div>

                <div class="flex items-start gap-3">

                    <div class="h-8 w-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center">

                        <i class="fa-solid fa-sparkles text-xs"></i>

                    </div>

                    <div>

                        <p class="text-sm text-slate-700">Yeni tema iyileÅŸtirmeleri eklendi.</p>

                        <p class="text-xs text-slate-400">26 Ocak 2026 · 12:10</p>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="panel-card p-6">

            <div class="flex items-center justify-between mb-4">

                <h3 class="text-sm font-semibold text-slate-700">Pazaryeri SatıÅŸ DaÄŸılımı</h3>

                <span class="text-xs text-slate-400">Pie</span>

            </div>

            <div class="flex flex-wrap gap-2 mb-4">

                <span class="px-2 py-1 rounded-full bg-slate-100 text-xs text-slate-500">Trendyol</span>

                <span class="px-2 py-1 rounded-full bg-slate-100 text-xs text-slate-500">Hepsiburada</span>

                <span class="px-2 py-1 rounded-full bg-slate-100 text-xs text-slate-500">N11</span>

            </div>

            <div class="h-56 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 flex items-center justify-center text-sm text-slate-400">

                Pie chart placeholder

            </div>

        </div>



        <div class="panel-card p-6">

            <div class="flex items-center justify-between mb-4">

                <h3 class="text-sm font-semibold text-slate-700">En Ã‡ok Satan 10 Ãœrün</h3>

                <span class="text-xs text-slate-400">Bu ay</span>

            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="text-xs uppercase text-slate-400">

                        <tr>

                            <th class="text-left py-2 pr-3">stok_kodu</th>

                            <th class="text-left py-2 pr-3">urun_adi</th>

                            <th class="text-left py-2 pr-3">secenek</th>

                            <th class="text-left py-2">satis_adedi</th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse($topProducts as $row)

                            <tr>

                                <td class="py-2 pr-3 text-slate-700">{{ $row['stock_code'] ?? '-' }}</td>

                                <td class="py-2 pr-3 text-slate-700">{{ $row['name'] }}</td>

                                <td class="py-2 pr-3 text-slate-500">{{ $row['variant'] ?? '-' }}</td>

                                <td class="py-2 text-slate-700 font-semibold">{{ $row['quantity'] }}</td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="4" class="py-4 text-center text-slate-500">Veri bulunmuyor</td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>



        <div class="panel-card p-6">

            <div class="space-y-4">

                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/70 p-4 text-sm text-slate-500">

                    Reklam alanı 1 (banner/CTA)

                </div>

                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/70 p-4 text-sm text-slate-500">

                    Reklam alanı 2 (kampanya görseli)

                </div>

            </div>

        </div>

    </div>

@endsection



@push('scripts')

    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}">

    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

        <script>

        const mapData = @json($mapData);

        const mapContainer = document.getElementById('turkey-map');

        const normalize = (value = '') => {

            const replacements = { 'ç': 'c', 'ÄŸ': 'g', 'ı': 'i', 'İ': 'i', 'ö': 'o', 'ÅŸ': 's', 'ü': 'u' };

            let normalized = value.toString().toLowerCase();

            Object.entries(replacements).forEach(([search, replace]) => {

                normalized = normalized.replaceAll(search, replace);

            });

            normalized = normalized.replace(/[^a-z0-9\s]/g, ' ');

            normalized = normalized.replace(/\s+/g, ' ').trim();

            return normalized;

        };

        const maxValue = Math.max(...Object.values(mapData), 1);

        const colorScale = (value) => {

            if (!value) return '#f1f5f9';

            const lightness = 70 - Math.min(50, (value / maxValue) * 50);

            return `hsl(215, 85%, ${lightness}%)`;

        };



        if (mapContainer && typeof L !== 'undefined') {

            const map = L.map(mapContainer, {

                zoomControl: false,

                attributionControl: false,

                scrollWheelZoom: false,

                dragging: false,

                doubleClickZoom: false,

                boxZoom: false,

                touchZoom: false,

            }).setView([39.0, 35.0], 5);



            fetch('/maps/turkey-provinces.geojson')

                .then(response => response.json())

                .then(geojson => {

                    const layer = L.geoJSON(geojson, {

                        style: feature => {

                            const name = normalize(feature.properties?.name);

                            const value = mapData[name] || 0;

                            return {

                                fillColor: colorScale(value),

                                color: '#ffffff',

                                weight: 1,

                                fillOpacity: value ? 0.8 : 0.3,

                            };

                        },

                    onEachFeature: (feature, layer) => {

                        const name = feature.properties?.name || 'Bilinmeyen';

                        const value = mapData[normalize(name)] || 0;

                        const tooltipContent = `<div class="map-label"><span class="map-name">${name}</span><span class="map-count">${value} adet</span></div>`;

                        layer.bindTooltip(tooltipContent, {

                            permanent: true,

                            direction: 'center',

                            className: 'map-label-wrapper',

                        });

                        layer.on('mouseover', () => layer.setStyle({ weight: 3 }));

                        layer.on('mouseout', () => layer.setStyle({ weight: 1 }));

                    },

                    }).addTo(map);

                    const bounds = layer.getBounds().pad(0.08);

                    map.fitBounds(bounds, { padding: [30, 30] });

                    setTimeout(() => {

                        const zoomOffset = Math.min(map.getZoom() + 0.6, 7);

                        map.setZoom(zoomOffset);

                        map.invalidateSize();

                    }, 0);

                })

                .catch(() => {

                    mapContainer.innerHTML = '<div class="flex h-full flex-col items-center justify-center text-sm text-slate-500 gap-2">' +

                        '<span class="text-lg font-semibold">Harita yüklenemedi</span>' +

                        '<span>GeoJSON verisi alınamadı.</span>' +

                        '</div>';

                });

        } else if (mapContainer) {

            mapContainer.innerHTML = '<div class="flex h-full flex-col items-center justify-center text-sm text-slate-500 gap-2">' +

                '<span class="text-lg font-semibold">Harita Müşteri tarafında yüklenemedi</span>' +

                '<span>Leaflet kütüphanesi yüklenemedi.</span>' +

                '</div>';

        }

    </script>

@endpush








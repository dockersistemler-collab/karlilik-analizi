@extends('layouts.admin')

@section('header')
    Dashboard
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
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                        <div class="text-xs text-amber-700">
                            @if($portalBilling['next_retry_at'])
                                Sonraki deneme: {{ optional($portalBilling['next_retry_at'])->format('d.m.Y H:i') }}
                            @else
                                Sonraki deneme: -
                            @endif
                        </div>
                        <a href="{{ route('portal.billing.card-update') }}" class="btn btn-outline-accent">Kart Guncelle</a>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
        <div class="panel-card p-6 relative overflow-hidden">
            <div class="absolute -left-12 -top-12 h-56 w-56 rounded-full border border-slate-200/70 opacity-70"></div>
            <div class="absolute right-8 bottom-0 h-40 w-40 rounded-full bg-orange-100/50"></div>
            <div class="relative">
                <h3 class="text-3xl font-semibold text-slate-900">Hos geldin</h3>
                <p class="mt-2 text-sm text-slate-600">
                    Satis, maliyet ve iade verilerini tek panelden takip edip kararlilik seviyeni anlik gorebilirsin.
                </p>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <a href="{{ route('portal.profitability.index') }}" class="btn btn-solid-accent">Karlilik Analizi</a>
                    <a href="{{ route('portal.reports.order-profitability') }}" class="btn btn-outline">Raporlari Gor</a>
                </div>
            </div>
        </div>

        <div class="panel-card p-6">
            <div class="flex flex-wrap items-end gap-3">
                <a href="{{ route('portal.dashboard', ['range' => 'day']) }}"
                   class="btn {{ $range === 'day' ? 'btn-solid-accent' : 'btn-outline' }}">Gunluk</a>
                <a href="{{ route('portal.dashboard', ['range' => 'week']) }}"
                   class="btn {{ $range === 'week' ? 'btn-solid-accent' : 'btn-outline' }}">Haftalik</a>
                <a href="{{ route('portal.dashboard', ['range' => 'month']) }}"
                   class="btn {{ $range === 'month' ? 'btn-solid-accent' : 'btn-outline' }}">Aylik</a>
                <a href="{{ route('portal.dashboard', ['range' => 'year']) }}"
                   class="btn {{ $range === 'year' ? 'btn-solid-accent' : 'btn-outline' }}">Yillik</a>
            </div>
            <p class="mt-4 text-xs text-slate-500">Canli performans secilen zaman araligina gore guncellenir.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        @foreach($kpis as $kpi)
            @php
                $cardPalette = match ($loop->index % 4) {
                    0 => 'background: linear-gradient(135deg, #dff5ef, #c8efe2); color: #2a4a40;',
                    1 => 'background: linear-gradient(135deg, #d9efff, #c2e3ff); color: #244a60;',
                    2 => 'background: linear-gradient(135deg, #fff2c7, #f6dea4); color: #5a4417;',
                    default => 'background: linear-gradient(135deg, #f0ece9, #e6dfd7); color: #4b3a32;',
                };
            @endphp
            <div class="rounded-2xl p-5 shadow-sm border border-slate-200/60" style="{{ $cardPalette }}">
                <div class="text-xs uppercase tracking-[0.2em] opacity-80">{{ $kpi['title'] }}</div>
                <div class="mt-2 text-3xl font-semibold">
                    {{ $kpi['value'] }} <span class="text-base font-medium opacity-80">{{ $kpi['unit'] }}</span>
                </div>
                <p class="mt-2 text-xs opacity-80">{{ $kpi['description'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="panel-card p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700">Kar Performansi Harita Dagilimi</h3>
                <span class="text-xs text-slate-400">Turkiye</span>
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
                    color: #2a1f1b;
                }
                .map-label .map-count {
                    font-size: 0.5rem;
                    font-weight: 600;
                    color: #ff6b4a;
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
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Duyuru Akisi</h3>
            <div class="space-y-4">
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
                    <p class="text-sm font-semibold text-slate-700">Komisyon tarife guncellemesi yayinlandi.</p>
                    <p class="text-xs text-slate-500 mt-1">Raporlar menusu altindan kontrol edebilirsin.</p>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
                    <p class="text-sm font-semibold text-slate-700">Canli performans akisi optimize edildi.</p>
                    <p class="text-xs text-slate-500 mt-1">Dakikalik degisimler daha hizli isleniyor.</p>
                </div>
                <div class="rounded-xl border border-slate-200/80 bg-slate-50/70 p-4">
                    <p class="text-sm font-semibold text-slate-700">Uyari sayfasi filtreleri guncellendi.</p>
                    <p class="text-xs text-slate-500 mt-1">Zararda kalan siparisleri daha hizli bulabilirsin.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="panel-card p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-700">En Cok Satan 10 Urun</h3>
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
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Hizli Yonlendirmeler</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <a href="{{ route('portal.profitability.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                    Karlilik Paneli
                </a>
                <a href="{{ route('portal.reports.order-profitability') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                    Siparis Karlilik Raporu
                </a>
                <a href="{{ route('portal.settings.index') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                    Hesap Ayarlari
                </a>
                <a href="{{ route('portal.help.training') }}" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 hover:bg-slate-100">
                    Nasil Yapilir?
                </a>
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
            const replacements = { 'c': 'c', 'g': 'g', 'i': 'i', 'o': 'o', 's': 's', 'u': 'u' };
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
            return `hsl(26, 72%, ${lightness}%)`;
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
                                fillOpacity: value ? 0.78 : 0.32,
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
                        '<span class="text-lg font-semibold">Harita yuklenemedi</span>' +
                        '<span>GeoJSON verisi alinamadi.</span>' +
                        '</div>';
                });
        } else if (mapContainer) {
            mapContainer.innerHTML = '<div class="flex h-full flex-col items-center justify-center text-sm text-slate-500 gap-2">' +
                '<span class="text-lg font-semibold">Harita istemci tarafinda yuklenemedi</span>' +
                '<span>Leaflet kutuphanesi yuklenemedi.</span>' +
                '</div>';
        }
    </script>
@endpush

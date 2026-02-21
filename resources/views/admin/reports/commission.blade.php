@extends('layouts.admin')

@section('header')
    Komisyon Raporu
@endsection

@push('styles')
<style>
    .menu-modern-mini-cards.commission-hero-cards {
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 12px;
        justify-content: center;
        width: 100%;
        align-items: stretch;
    }
    body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-hero-inline {
        width: 100%;
        max-width: none;
        margin-inline: auto;
        display: flex;
        justify-content: center;
    }
    body.menu-modern-shell .menu-modern-hero.has-inline-cards {
        position: relative;
        grid-template-columns: minmax(0, 1fr);
    }
    body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-hero-content {
        padding-right: 0;
    }
    body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-hero-aside {
        position: absolute;
        top: 20px;
        right: 20px;
        min-width: auto;
        padding: 10px 12px;
        border-radius: 14px;
    }
    body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-hero-actions {
        flex-wrap: nowrap;
        gap: 6px;
    }
    body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-chip {
        font-size: 11px;
        padding: 4px 8px;
        white-space: nowrap;
    }
    @media (max-width: 1200px) {
        body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-hero-content {
            padding-right: 0;
        }
        body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-hero-aside {
            position: static;
            margin-top: 10px;
        }
        body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-hero-actions {
            flex-wrap: wrap;
        }
    }
    .menu-modern-mini-cards.commission-hero-cards .menu-modern-mini-card {
        width: auto;
        flex: 1 1 0;
        min-width: 0;
        height: 108px;
        min-height: 108px;
        padding: 12px 12px 14px;
        border: 1px solid #dbe7f5;
        background: linear-gradient(160deg, #ffffff 0%, #f8fbff 100%);
        border-radius: 14px;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
        position: relative;
        overflow: hidden;
        transition: transform .2s ease, box-shadow .25s ease, border-color .2s ease;
    }
    .menu-modern-mini-cards.commission-hero-cards .menu-modern-mini-card .commission-hero-watermark {
        position: absolute;
        right: 10px;
        top: 8px;
        width: 34%;
        height: 44%;
        pointer-events: none;
        opacity: 0.12;
        filter: grayscale(1) contrast(1.05);
        z-index: 1;
        object-fit: contain;
    }
    .menu-modern-mini-cards.commission-hero-cards .menu-modern-mini-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #f97316, #fb923c);
        opacity: 0.9;
    }
    .commission-hero-head {
        display: flex;
        align-items: center;
        gap: 6px;
        position: relative;
        z-index: 2;
    }
    .commission-hero-icon {
        width: 18px;
        height: 18px;
        border-radius: 999px;
        background: #eef4ff;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        border: 1px solid #d9e6ff;
    }
    .menu-modern-mini-cards.commission-hero-cards .menu-modern-mini-card-label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    .menu-modern-mini-cards.commission-hero-cards .menu-modern-mini-card-value {
        margin-top: 4px;
        font-size: 39px;
        line-height: 1;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
        color: #111827;
        position: relative;
        z-index: 2;
    }
    .menu-modern-mini-cards.commission-hero-cards .menu-modern-mini-card.is-total {
        border-style: solid;
        border-color: #bfdbfe;
        background: linear-gradient(160deg, #f8fbff 0%, #eef5ff 100%);
    }
    .menu-modern-mini-cards.commission-hero-cards .menu-modern-mini-card.is-total .menu-modern-mini-card-value {
        color: #0f766e;
    }
    @media (max-width: 1280px) {
        .menu-modern-mini-cards.commission-hero-cards {
            flex-wrap: wrap !important;
        }
    }
    @media (max-width: 900px) {
        .menu-modern-mini-cards.commission-hero-cards {
            justify-content: stretch;
        }
        .menu-modern-mini-cards.commission-hero-cards .menu-modern-mini-card { width: auto; height: auto; min-height: 96px; }
    }
    .menu-modern-mini-cards.commission-hero-cards .menu-modern-mini-card:hover {
        transform: translateY(-2px);
        border-color: #bfdbfe;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }
    body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-title {
        color: #111827;
        font-weight: 700;
        letter-spacing: -0.02em;
    }
</style>
@endpush

@section('hero-inline-cards')
    @php
        $commissionCards = collect($report['cards'] ?? []);
        $commissionTotal = (float) ($report['total'] ?? 0);
        $heroLogoMap = [
            'amazon tr' => asset('images/brands/amazon.png'),
            'hepsiburada' => asset('images/brands/hepsiburada.png'),
            'n11' => asset('images/brands/n11.png'),
            'trendyol' => asset('images/brands/trendyol.png'),
            'cicek sepeti' => asset('images/brands/ciceksepeti.png'),
        ];
    @endphp
    <div class="menu-modern-mini-cards commission-hero-cards">
        @foreach($commissionCards->take(5) as $card)
            @php
                $heroNameKey = \Illuminate\Support\Str::of(trim((string) ($card['name'] ?? '')))->lower()->ascii()->value();
                $heroLogo = $heroLogoMap[$heroNameKey] ?? null;
            @endphp
            <div class="menu-modern-mini-card">
                @if($heroLogo)
                    <img class="commission-hero-watermark" src="{{ $heroLogo }}" alt="" aria-hidden="true">
                @endif
                <div class="commission-hero-head">
                    <span class="commission-hero-icon"><i class="fa-solid fa-percent"></i></span>
                    <span class="menu-modern-mini-card-label">{{ $card['name'] ?? '-' }}</span>
                </div>
                <strong class="menu-modern-mini-card-value">{{ number_format((float) ($card['total'] ?? 0), 2, ',', '.') }} TL</strong>
            </div>
        @endforeach
        <div class="menu-modern-mini-card is-total">
            <div class="commission-hero-head">
                <span class="commission-hero-icon"><i class="fa-solid fa-sack-dollar"></i></span>
                <span class="menu-modern-mini-card-label">Toplam Komisyon</span>
            </div>
            <strong class="menu-modern-mini-card-value">{{ number_format($commissionTotal, 2, ',', '.') }} TL</strong>
        </div>
    </div>
@endsection

@section('content')
    <style>
        .commission-table-logo {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            object-fit: contain;
            background: #fff;
            border: 1px solid #dbe7f5;
            padding: 3px;
        }
        .commission-inline-row > td { padding: 10px 0 0 0; }
        .commission-inline-row.is-hidden { display: none; }
        .commission-inline-panel {
            margin: 0 8px 10px;
            border: 1px solid #cfe0f5;
            border-radius: 18px;
            background: #f8fbff;
            padding: 14px;
            position: relative;
        }
        .commission-inline-brand {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid #dbe7f5;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 4px;
        }
        .commission-inline-brand img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .commission-inline-brand.is-visible { display: inline-flex; }
        .commission-inline-title { font-size: 19px; font-weight: 800; color: #0f172a; line-height: 1.2; }
        .commission-inline-subtitle { margin-top: 2px; font-size: 11px; color: #64748b; }
        .commission-inline-top { margin-top: 12px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .commission-inline-box { border: 1px solid #d4deea; border-radius: 16px; background: #fff; padding: 10px 14px; min-height: 52px; }
        .commission-inline-box span { display:block; font-size: 11px; color: #94a3b8; margin-bottom: 2px; }
        .commission-inline-box strong { display:block; font-size: 15px; line-height: 1.1; color: #0f172a; font-weight: 800; }
        .commission-inline-box strong.commission-green { color: #0f9a6f; }
        .commission-inline-metrics { margin-top: 10px; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
        .commission-inline-metric { border: 1px solid #d4deea; border-radius: 14px; background: #fff; min-height: 42px; padding: 8px 10px; display: flex; align-items: center; gap: 8px; }
        .commission-inline-icon { width: 24px; height: 24px; border-radius: 999px; border: 1px solid #d3dee8; background: #f3f6fa; color: #1e3a5f; display:inline-flex; align-items:center; justify-content:center; font-size: 11px; flex: 0 0 auto; }
        .commission-inline-content span { display:block; font-size: 11px; color: #64748b; line-height: 1.2; }
        .commission-inline-content strong { display:block; font-size: 13px; color: #334155; line-height: 1.2; margin-top: 1px; font-weight: 700; }
        @media (max-width: 1100px) { .commission-inline-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>

    <div class="panel-card p-6 mb-6 report-filter-panel">
        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-end gap-3 report-filter-form">
            <div class="min-w-[180px] report-filter-field">
                <label class="block text-xs font-medium text-slate-500 mb-1">Satis Kanali</label>
                <select name="marketplace_id" class="report-filter-control">
                    <option value="">Tumu</option>
                    @foreach($marketplaces as $marketplace)
                        <option value="{{ $marketplace->id }}" @selected(($filters['marketplace_id'] ?? null) == $marketplace->id)>{{ $marketplace->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[150px] report-filter-field">
                <label class="block text-xs font-medium text-slate-500 mb-1">Baslangic</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="report-filter-control">
            </div>

            <div class="min-w-[150px] report-filter-field">
                <label class="block text-xs font-medium text-slate-500 mb-1">Bitis</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="report-filter-control">
            </div>

            <div class="min-w-[220px] report-filter-field">
                <label class="block text-xs font-medium text-slate-500 mb-1">Hizli Secim</label>
                <div class="report-filter-quick">
                    @foreach($quickRanges as $key => $label)
                        <button type="submit" name="quick_range" value="{{ $key }}" class="report-filter-chip text-xs {{ ($filters['quick_range'] ?? '') === $key ? 'is-active' : '' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="report-filter-actions">
                <button type="submit" class="report-filter-btn report-filter-btn-primary">Filtrele</button>
                <a href="{{ route('portal.reports.commission') }}" class="report-filter-btn report-filter-btn-secondary">Temizle</a>
            </div>
        </form>
    </div>

    @php
        $commissionCards = collect($report['cards'] ?? []);
        $commissionTotal = (float) ($report['total'] ?? 0);
        $marketplaceLogoMap = [
            'amazon tr' => 'images/brands/amazon.png',
            'hepsiburada' => 'images/brands/hepsiburada.png',
            'n11' => 'images/brands/n11.png',
            'trendyol' => 'images/brands/trendyol.png',
            'cicek sepeti' => 'images/brands/ciceksepeti.png',
        ];
        $detailRows = $commissionCards->values()->map(function ($card) use ($commissionTotal, $marketplaceLogoMap) {
            $total = (float) ($card['total'] ?? 0);
            $ratio = $commissionTotal > 0 ? ($total / $commissionTotal) * 100 : 0;
            $name = (string) ($card['name'] ?? '-');
            $nameKey = \Illuminate\Support\Str::of(trim($name))->lower()->ascii()->value();
            $logoPath = $marketplaceLogoMap[$nameKey] ?? null;
            $logoUrl = $logoPath ? asset($logoPath) : null;
            return [
                'name' => $name,
                'total' => $total,
                'ratio' => $ratio,
                'color' => '#ff4439',
                'logo' => $logoUrl,
            ];
        });
    @endphp

    <div class="panel-card p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px] table-fixed">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-2.5 w-[8%]">Gorsel</th>
                        <th class="text-left py-2 pr-2.5 w-[30%]">Pazaryeri</th>
                        <th class="text-center py-2 pr-2.5 w-[20%]">Komisyon Tutari</th>
                        <th class="text-center py-2 pr-2.5 w-[20%]">Toplam Icindeki Pay</th>
                        <th class="text-right py-2 w-[22%]">Detay</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="commission-table-body">
                    @forelse($detailRows as $row)
                        <tr>
                            <td class="py-2 pr-2.5 text-left">
                                @if(!empty($row['logo']))
                                    <img src="{{ $row['logo'] }}" alt="{{ $row['name'] }} logo" class="commission-table-logo">
                                @else
                                    <span class="commission-table-logo inline-flex items-center justify-center text-[10px] text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="py-2 pr-2.5 text-slate-700 font-semibold">{{ $row['name'] }}</td>
                            <td class="py-2 pr-2.5 text-center tabular-nums text-slate-800">{{ number_format($row['total'], 2, ',', '.') }} TL</td>
                            <td class="py-2 pr-2.5 text-center tabular-nums text-emerald-600">{{ number_format($row['ratio'], 2, ',', '.') }} %</td>
                            <td class="py-2 text-right">
                                <button type="button" class="btn btn-solid-accent px-2.5 py-1 text-[10px]" data-commission-toggle-detail data-name="{{ $row['name'] }}" data-total="{{ number_format($row['total'], 2, ',', '.') }} TL" data-ratio="{{ number_format($row['ratio'], 2, ',', '.') }} %" data-color="{{ $row['color'] }}" data-logo="{{ $row['logo'] ?? '' }}" data-overall="{{ number_format($commissionTotal, 2, ',', '.') }} TL">Detayi Gor</button>
                            </td>
                        </tr>
                        <tr class="commission-inline-row is-hidden" data-commission-inline-row>
                            <td colspan="5">
                                <div class="commission-inline-panel">
                                    <div class="commission-inline-brand" data-commission-logo-wrap>
                                        <img src="" alt="Pazaryeri logo" data-commission-field="logo">
                                    </div>
                                    <div class="commission-inline-title">Komisyon Detayi</div>
                                    <div class="commission-inline-subtitle" data-commission-field="name">-</div>
                                    <div class="commission-inline-top">
                                        <div class="commission-inline-box"><span>Komisyon Tutari</span><strong class="commission-green" data-commission-field="total">-</strong></div>
                                        <div class="commission-inline-box"><span>Toplam Icindeki Pay</span><strong class="commission-green" data-commission-field="ratio">-</strong></div>
                                    </div>
                                    <div class="commission-inline-metrics">
                                        <div class="commission-inline-metric"><span class="commission-inline-icon"><i class="fa-solid fa-store"></i></span><div class="commission-inline-content"><span>Pazaryeri</span><strong data-commission-field="name-line">-</strong></div></div>
                                        <div class="commission-inline-metric"><span class="commission-inline-icon"><i class="fa-solid fa-wallet"></i></span><div class="commission-inline-content"><span>Toplam Komisyon</span><strong data-commission-field="overall">-</strong></div></div>
                                        <div class="commission-inline-metric"><span class="commission-inline-icon"><i class="fa-solid fa-palette"></i></span><div class="commission-inline-content"><span>Renk Kodu</span><strong data-commission-field="color">-</strong></div></div>
                                        <div class="commission-inline-metric"><span class="commission-inline-icon"><i class="fa-solid fa-calendar"></i></span><div class="commission-inline-content"><span>Donem</span><strong data-commission-field="period">{{ ($filters['date_from'] ?? '-') . ' / ' . ($filters['date_to'] ?? '-') }}</strong></div></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-center text-slate-500">Kayit bulunamadi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const closeAll = (tbody) => {
        tbody.querySelectorAll('[data-commission-inline-row]').forEach((row) => row.classList.add('is-hidden'));
        tbody.querySelectorAll('[data-commission-toggle-detail]').forEach((btn) => btn.textContent = 'Detayi Gor');
    };

    document.querySelectorAll('[data-commission-toggle-detail]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            const detailRow = row?.nextElementSibling;
            const tbody = row?.parentElement;
            if (!detailRow || !detailRow.matches('[data-commission-inline-row]') || !tbody) return;

            const isOpen = !detailRow.classList.contains('is-hidden');
            closeAll(tbody);
            if (isOpen) return;

            const setField = (key, value) => {
                const el = detailRow.querySelector(`[data-commission-field="${key}"]`);
                if (el) el.textContent = value || '-';
            };

            setField('name', btn.getAttribute('data-name'));
            setField('name-line', btn.getAttribute('data-name'));
            setField('total', btn.getAttribute('data-total'));
            setField('ratio', btn.getAttribute('data-ratio'));
            setField('overall', btn.getAttribute('data-overall'));
            setField('color', btn.getAttribute('data-color'));
            const logo = btn.getAttribute('data-logo') || '';
            const logoEl = detailRow.querySelector('[data-commission-field="logo"]');
            const logoWrap = detailRow.querySelector('[data-commission-logo-wrap]');
            if (logoEl && logoWrap) {
                if (logo) {
                    logoEl.setAttribute('src', logo);
                    logoWrap.classList.add('is-visible');
                } else {
                    logoEl.setAttribute('src', '');
                    logoWrap.classList.remove('is-visible');
                }
            }

            detailRow.classList.remove('is-hidden');
            btn.textContent = 'Detayi Gizle';
        });
    });
})();
</script>
@endpush

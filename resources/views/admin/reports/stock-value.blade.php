@extends('layouts.admin')

@section('header')
    Stoktaki Ürün Tutarları
@endsection

@push('styles')
<style>
    .menu-modern-mini-cards.stock-hero-cards {
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
    .menu-modern-mini-cards.stock-hero-cards .menu-modern-mini-card {
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
    .menu-modern-mini-cards.stock-hero-cards .menu-modern-mini-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #2563eb, #60a5fa);
        opacity: 0.88;
    }
    .stock-hero-head {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 6px;
        width: 100%;
        text-align: left;
        align-self: flex-start;
        position: relative;
        z-index: 2;
    }
    .stock-hero-icon {
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
    .menu-modern-mini-cards.stock-hero-cards .menu-modern-mini-card-label {
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    .menu-modern-mini-cards.stock-hero-cards .menu-modern-mini-card-value {
        margin-top: 4px;
        font-size: 22px;
        line-height: 1;
        font-weight: 750;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
        color: #111827;
        position: relative;
        z-index: 2;
    }
    .menu-modern-mini-cards.stock-hero-cards .menu-modern-mini-card-note {
        margin-top: 5px;
        font-size: 11px;
        color: #64748b;
        position: relative;
        z-index: 2;
    }
    .menu-modern-mini-cards.stock-hero-cards .menu-modern-mini-card.is-total {
        border-style: solid;
        border-color: #bfdbfe;
        background: linear-gradient(160deg, #f8fbff 0%, #eef5ff 100%);
    }
    .menu-modern-mini-cards.stock-hero-cards .menu-modern-mini-card.is-total .menu-modern-mini-card-value {
        color: #0f766e;
    }
    .menu-modern-mini-cards.stock-hero-cards .menu-modern-mini-card:hover {
        transform: translateY(-2px);
        border-color: #bfdbfe;
        box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }
    @media (max-width: 1280px) {
        .menu-modern-mini-cards.stock-hero-cards {
            flex-wrap: wrap !important;
        }
    }
    @media (max-width: 900px) {
        .menu-modern-mini-cards.stock-hero-cards {
            grid-template-columns: repeat(2, minmax(200px, 1fr));
            justify-content: stretch;
        }
        .menu-modern-mini-cards.stock-hero-cards .menu-modern-mini-card {
            width: auto;
            height: auto;
            min-height: 96px;
        }
    }
    body.menu-modern-shell .menu-modern-hero.has-inline-cards .menu-modern-title {
        color: #111827;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .stock-table-card {
        border-radius: 16px;
        border: 1px solid #dbe7f5;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
    }
    .stock-table {
        min-width: 100%;
        font-size: 14px;
    }
    .stock-table thead th {
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-size: 11px;
        font-weight: 700;
        border-bottom: 1px solid #e2e8f0;
        padding-top: 10px;
        padding-bottom: 10px;
    }
    .stock-table tbody tr {
        transition: background-color .2s ease;
    }
    .stock-table tbody tr:hover {
        background: #f8fbff;
    }
    .stock-table tbody td {
        border-bottom: 1px solid #f1f5f9;
    }
    .stock-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .stock-name {
        font-weight: 600;
        color: #0f172a;
    }
    .stock-sku {
        color: #64748b;
    }
    .stock-qty-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 56px;
        padding: 4px 10px;
        border-radius: 999px;
        border: 1px solid #dbe7f5;
        background: #f8fbff;
        color: #1e293b;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .stock-money {
        color: #334155;
        font-variant-numeric: tabular-nums;
    }
    .stock-detail-btn {
        border: 1px solid #bfdbfe;
        border-radius: 10px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 700;
        padding: 6px 10px;
    }
    .stock-inline-row > td { padding: 10px 0 0 0; }
    .stock-inline-row.is-hidden { display: none; }
    .stock-inline-panel {
        margin: 0 8px 10px;
        border: 1px solid #cfe0f5;
        border-radius: 18px;
        background: #f8fbff;
        padding: 14px;
    }
    .stock-inline-title { font-size: 19px; font-weight: 800; color: #0f172a; line-height: 1.2; }
    .stock-inline-subtitle { margin-top: 2px; font-size: 11px; color: #64748b; }
    .stock-inline-top { margin-top: 12px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .stock-inline-box { border: 1px solid #d4deea; border-radius: 16px; background: #fff; padding: 10px 14px; min-height: 52px; }
    .stock-inline-box span { display:block; font-size: 11px; color: #94a3b8; margin-bottom: 2px; }
    .stock-inline-box strong { display:block; font-size: 15px; line-height: 1.1; color: #0f172a; font-weight: 800; }
    .stock-inline-box strong.stock-green { color: #0f9a6f; }
    .stock-inline-metrics { margin-top: 10px; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
    .stock-inline-metric { border: 1px solid #d4deea; border-radius: 14px; background: #fff; min-height: 42px; padding: 8px 10px; display: flex; align-items: center; gap: 8px; }
    .stock-inline-icon { width: 24px; height: 24px; border-radius: 999px; border: 1px solid #d3dee8; background: #f3f6fa; color: #1e3a5f; display:inline-flex; align-items:center; justify-content:center; font-size: 11px; flex: 0 0 auto; }
    .stock-inline-content span { display:block; font-size: 11px; color: #64748b; line-height: 1.2; }
    .stock-inline-content strong { display:block; font-size: 13px; color: #334155; line-height: 1.2; margin-top: 1px; font-weight: 700; }
    .stock-inline-collapse-wrap { display:flex; justify-content:center; margin-top: 8px; }
    .stock-inline-collapse { width:28px; height:20px; border:none; background:transparent; color:#1f2937; display:inline-flex; align-items:center; justify-content:center; transition: color .2s ease, transform .2s ease; }
    .stock-inline-collapse:hover { color:#0f172a; transform: translateY(-1px); }
    .stock-inline-collapse i { font-size: 13px; line-height: 1; }
    @media (max-width: 1100px) { .stock-inline-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .stock-thumb-wrap {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid #dbe7f5;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: zoom-in;
    }
    .stock-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .stock-thumb-placeholder {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid #dbe7f5;
        background: #f8fbff;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .stock-image-popover {
        position: fixed;
        z-index: 1400;
        pointer-events: none;
        width: 150px;
        height: 150px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.28);
        overflow: hidden;
        display: none;
    }
    .stock-image-popover.is-open {
        display: block;
    }
    .stock-image-popover img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #f8fafc;
    }
    .report-filter-panel {
        border-radius: 16px;
        border: 1px solid #dbe7f5;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.06);
    }
    .report-filter-control {
        width: 100%;
        min-height: 40px;
        border: 1px solid #d7e3f2;
        border-radius: 12px;
        background: #fff;
        color: #0f172a;
        font-size: 13px;
        padding: 0 12px;
    }
    .report-filter-control:focus {
        outline: none;
        border-color: #93c5fd;
        box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.2);
    }
    .report-filter-actions {
        display: inline-flex;
        gap: 8px;
        margin-left: auto;
    }
    .report-filter-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 40px;
        padding: 0 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 700;
        border: 1px solid transparent;
    }
    .report-filter-btn-primary {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
    }
    .report-filter-btn-secondary {
        background: #fff;
        color: #334155;
        border-color: #d7e3f2;
    }
</style>
@endpush

@section('hero-inline-cards')
    <div class="menu-modern-mini-cards stock-hero-cards">
        <div class="menu-modern-mini-card">
            <div class="stock-hero-head">
                <span class="stock-hero-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                <span class="menu-modern-mini-card-label">Toplam Ürün Sayısı</span>
            </div>
            <strong class="menu-modern-mini-card-value">{{ number_format($summary['total_products'] ?? 0) }}</strong>
        </div>

        <div class="menu-modern-mini-card">
            <div class="stock-hero-head">
                <span class="stock-hero-icon"><i class="fa-solid fa-chart-line"></i></span>
                <span class="menu-modern-mini-card-label">Toplam Satış Tutarı</span>
            </div>
            <strong class="menu-modern-mini-card-value">{{ number_format($summary['total_sales_amount'] ?? 0, 2, ',', '.') }} TL</strong>
        </div>

        <div class="menu-modern-mini-card">
            <div class="stock-hero-head">
                <span class="stock-hero-icon"><i class="fa-solid fa-arrow-up-wide-short"></i></span>
                <span class="menu-modern-mini-card-label">Yüksek Stoklu Ürün</span>
            </div>
            <strong class="menu-modern-mini-card-value text-[24px]">{{ \Illuminate\Support\Str::limit((string) ($summary['highest_stock']->name ?? '-'), 18) }}</strong>
            <div class="menu-modern-mini-card-note">{{ number_format((int) ($summary['highest_stock']->stock_quantity ?? 0)) }} adet</div>
        </div>

        <div class="menu-modern-mini-card">
            <div class="stock-hero-head">
                <span class="stock-hero-icon"><i class="fa-solid fa-arrow-down-wide-short"></i></span>
                <span class="menu-modern-mini-card-label">Düşük Stoklu Ürün</span>
            </div>
            <strong class="menu-modern-mini-card-value text-[24px]">{{ \Illuminate\Support\Str::limit((string) ($summary['lowest_stock']->name ?? '-'), 18) }}</strong>
            <div class="menu-modern-mini-card-note">{{ number_format((int) ($summary['lowest_stock']->stock_quantity ?? 0)) }} adet</div>
        </div>

        <div class="menu-modern-mini-card is-total">
            <div class="stock-hero-head">
                <span class="stock-hero-icon"><i class="fa-solid fa-wallet"></i></span>
                <span class="menu-modern-mini-card-label">Toplam Stok Maliyeti</span>
            </div>
            <strong class="menu-modern-mini-card-value">{{ number_format($summary['total_cost_amount'] ?? 0, 2, ',', '.') }} TL</strong>
        </div>
    </div>
@endsection

@section('content')
    <div class="panel-card p-6 mb-6 report-filter-panel">
        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-end gap-3">
            <div class="min-w-[220px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Arama</label>
                <input
                    type="text"
                    name="search"
                    value="{{ $filters['search'] ?? '' }}"
                    class="report-filter-control"
                    placeholder="Ürün adı veya stok kodu">
            </div>

            <div class="min-w-[130px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Min Stok</label>
                <input type="number" min="0" name="stock_min" value="{{ $filters['stock_min'] ?? '' }}" class="report-filter-control">
            </div>

            <div class="min-w-[130px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Max Stok</label>
                <input type="number" min="0" name="stock_max" value="{{ $filters['stock_max'] ?? '' }}" class="report-filter-control">
            </div>

            <div class="min-w-[220px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Sıralama</label>
                <select name="sort_by" class="report-filter-control">
                    <option value="stock_desc" @selected(($filters['sort_by'] ?? 'stock_desc') === 'stock_desc')>Stok (Azalan)</option>
                    <option value="stock_asc" @selected(($filters['sort_by'] ?? '') === 'stock_asc')>Stok (Artan)</option>
                    <option value="sales_desc" @selected(($filters['sort_by'] ?? '') === 'sales_desc')>Satış Tutarı (Azalan)</option>
                    <option value="cost_desc" @selected(($filters['sort_by'] ?? '') === 'cost_desc')>Stok Maliyeti (Azalan)</option>
                    <option value="name_asc" @selected(($filters['sort_by'] ?? '') === 'name_asc')>Ürün Adı (A-Z)</option>
                </select>
            </div>

            <div class="report-filter-actions">
                <button type="submit" class="report-filter-btn report-filter-btn-primary">Filtrele</button>
                <a href="{{ route('portal.reports.stock-value') }}" class="report-filter-btn report-filter-btn-secondary">Temizle</a>
            </div>
        </form>
    </div>

    <div class="panel-card p-6 stock-table-card">
        <div class="overflow-x-auto">
            <table class="stock-table">
                <thead>
                    <tr>
                        <th class="text-left pr-4">Görsel</th>
                        <th class="text-left pr-4">Ürün Adı</th>
                        <th class="text-left pr-4">Stok Kodu</th>
                        <th class="text-right pr-4">Alış Maliyeti</th>
                        <th class="text-right pr-4">Satış Fiyatı</th>
                        <th class="text-right pr-4">Stok</th>
                        <th class="text-right pr-4">Stok Maliyeti</th>
                        <th class="text-right">Satış Toplam Tutarı</th>
                        <th class="text-right">Detay</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $stockQty = (int) ($row['stock_quantity'] ?? 0);
                            $costPrice = (float) ($row['cost_price'] ?? 0);
                            $salePrice = (float) ($row['price'] ?? 0);
                            $stockCost = (float) ($row['stock_cost'] ?? 0);
                            $salesTotal = (float) ($row['sales_total'] ?? 0);
                            $marginPerUnit = $salePrice - $costPrice;
                            $marginTotal = $salesTotal - $stockCost;
                            $salesRatio = ((float) ($summary['total_sales_amount'] ?? 0)) > 0
                                ? ($salesTotal / (float) $summary['total_sales_amount']) * 100
                                : 0;
                        @endphp
                        <tr>
                            <td class="py-3 pr-4">
                                @if(!empty($row['image_url']))
                                    <span class="stock-thumb-wrap"
                                          tabindex="0"
                                          role="button"
                                          data-stock-preview-src="{{ $row['image_url'] }}"
                                          data-stock-preview-alt="{{ $row['name'] }}">
                                        <img src="{{ $row['image_url'] }}" alt="{{ $row['name'] }}" class="stock-thumb" loading="lazy">
                                    </span>
                                @else
                                    <span class="stock-thumb-placeholder">
                                        <i class="fas fa-image"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 stock-name">{{ $row['name'] }}</td>
                            <td class="py-3 pr-4 stock-sku">{{ $row['sku'] ?? '-' }}</td>
                            <td class="py-3 pr-4 text-right stock-money">{{ number_format($row['cost_price'], 2, ',', '.') }} TL</td>
                            <td class="py-3 pr-4 text-right stock-money">{{ number_format($row['price'], 2, ',', '.') }} TL</td>
                            <td class="py-3 pr-4 text-right"><span class="stock-qty-badge">{{ number_format($row['stock_quantity']) }}</span></td>
                            <td class="py-3 pr-4 text-right stock-money">{{ number_format($row['stock_cost'], 2, ',', '.') }} TL</td>
                            <td class="py-3 text-right stock-money">{{ number_format($row['sales_total'], 2, ',', '.') }} TL</td>
                            <td class="py-3 text-right">
                                <button type="button"
                                        class="stock-detail-btn"
                                        data-stock-toggle-detail
                                        data-name="{{ $row['name'] }}"
                                        data-sku="{{ $row['sku'] ?? '-' }}"
                                        data-stock="{{ number_format($stockQty) }}"
                                        data-cost="{{ number_format($costPrice, 2, ',', '.') }} TL"
                                        data-price="{{ number_format($salePrice, 2, ',', '.') }} TL"
                                        data-stock-cost="{{ number_format($stockCost, 2, ',', '.') }} TL"
                                        data-sales-total="{{ number_format($salesTotal, 2, ',', '.') }} TL"
                                        data-margin-unit="{{ number_format($marginPerUnit, 2, ',', '.') }} TL"
                                        data-margin-total="{{ number_format($marginTotal, 2, ',', '.') }} TL"
                                        data-ratio="{{ number_format($salesRatio, 2, ',', '.') }} %">
                                    Detayi Gor
                                </button>
                            </td>
                        </tr>
                        <tr class="stock-inline-row is-hidden" data-stock-inline-row>
                            <td colspan="9">
                                <div class="stock-inline-panel">
                                    <div class="stock-inline-title">Stok Detayi</div>
                                    <div class="stock-inline-subtitle" data-stock-field="name">-</div>
                                    <div class="stock-inline-top">
                                        <div class="stock-inline-box"><span>Stok Maliyeti</span><strong class="stock-green" data-stock-field="stock-cost">-</strong></div>
                                        <div class="stock-inline-box"><span>Satis Toplam Tutari</span><strong class="stock-green" data-stock-field="sales-total">-</strong></div>
                                    </div>
                                    <div class="stock-inline-metrics">
                                        <div class="stock-inline-metric"><span class="stock-inline-icon"><i class="fa-solid fa-barcode"></i></span><div class="stock-inline-content"><span>Stok Kodu</span><strong data-stock-field="sku">-</strong></div></div>
                                        <div class="stock-inline-metric"><span class="stock-inline-icon"><i class="fa-solid fa-boxes-stacked"></i></span><div class="stock-inline-content"><span>Stok Adedi</span><strong data-stock-field="stock">-</strong></div></div>
                                        <div class="stock-inline-metric"><span class="stock-inline-icon"><i class="fa-solid fa-scale-balanced"></i></span><div class="stock-inline-content"><span>Birim Marj</span><strong data-stock-field="margin-unit">-</strong></div></div>
                                        <div class="stock-inline-metric"><span class="stock-inline-icon"><i class="fa-solid fa-coins"></i></span><div class="stock-inline-content"><span>Toplam Marj</span><strong data-stock-field="margin-total">-</strong></div></div>
                                    </div>
                                    <div class="stock-inline-collapse-wrap">
                                        <button type="button" class="stock-inline-collapse" data-stock-inline-close title="Detayı kapat" aria-label="Detayı kapat">
                                            <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-5 text-center text-slate-500">Kayıt bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('admin.partials.modern-pagination-bar', [
            'paginator' => $rows,
            'perPageName' => 'per_page',
            'perPageLabel' => 'Sayfa başına',
            'perPageOptions' => [10, 25, 50, 100],
        ])
    </div>

    <div id="stock-image-popover" class="stock-image-popover" aria-hidden="true">
        <img id="stock-image-popover-img" src="" alt="">
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const popover = document.getElementById('stock-image-popover');
        const popoverImg = document.getElementById('stock-image-popover-img');
        const triggers = Array.from(document.querySelectorAll('[data-stock-preview-src]'));

        if (!popover || !popoverImg || !triggers.length) {
            return;
        }

        const placePopover = (event) => {
            const offset = 16;
            const width = 150;
            const height = 150;
            let left = event.clientX + offset;
            let top = event.clientY + offset;

            if (left + width > window.innerWidth - 8) {
                left = event.clientX - width - offset;
            }
            if (top + height > window.innerHeight - 8) {
                top = event.clientY - height - offset;
            }

            popover.style.left = `${Math.max(8, left)}px`;
            popover.style.top = `${Math.max(8, top)}px`;
        };

        const hidePopover = () => {
            popover.classList.remove('is-open');
            popoverImg.removeAttribute('src');
        };

        triggers.forEach((trigger) => {
            const openPopover = (event) => {
                const src = trigger.getAttribute('data-stock-preview-src');
                if (!src) {
                    return;
                }
                popoverImg.src = src;
                popoverImg.alt = trigger.getAttribute('data-stock-preview-alt') || 'Urun gorseli';
                popover.classList.add('is-open');
                placePopover(event);
            };

            trigger.addEventListener('mouseenter', openPopover);
            trigger.addEventListener('mousemove', placePopover);
            trigger.addEventListener('mouseleave', hidePopover);

            trigger.addEventListener('focus', () => {
                const rect = trigger.getBoundingClientRect();
                openPopover({
                    clientX: rect.left + (rect.width / 2),
                    clientY: rect.top + (rect.height / 2),
                });
            });
            trigger.addEventListener('blur', hidePopover);
            trigger.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    hidePopover();
                }
            });
        });
    })();
</script>
<script>
    (function () {
        const closeAll = (tbody) => {
            tbody.querySelectorAll('[data-stock-inline-row]').forEach((row) => row.classList.add('is-hidden'));
            tbody.querySelectorAll('[data-stock-toggle-detail]').forEach((btn) => btn.textContent = 'Detayi Gor');
        };

        document.querySelectorAll('[data-stock-toggle-detail]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const row = btn.closest('tr');
                const detailRow = row?.nextElementSibling;
                const tbody = row?.parentElement;
                if (!detailRow || !detailRow.matches('[data-stock-inline-row]') || !tbody) return;

                const isOpen = !detailRow.classList.contains('is-hidden');
                closeAll(tbody);
                if (isOpen) return;

                const setField = (key, value) => {
                    const el = detailRow.querySelector(`[data-stock-field="${key}"]`);
                    if (el) el.textContent = value || '-';
                };

                setField('name', btn.getAttribute('data-name'));
                setField('sku', btn.getAttribute('data-sku'));
                setField('stock', btn.getAttribute('data-stock'));
                setField('stock-cost', btn.getAttribute('data-stock-cost'));
                setField('sales-total', btn.getAttribute('data-sales-total'));
                setField('margin-unit', btn.getAttribute('data-margin-unit'));
                setField('margin-total', btn.getAttribute('data-margin-total'));

                detailRow.classList.remove('is-hidden');
                btn.textContent = 'Detayi Gizle';
            });
        });

        document.querySelectorAll('[data-stock-inline-close]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const tbody = btn.closest('tbody');
                if (tbody) closeAll(tbody);
            });
        });
    })();
</script>
@endpush



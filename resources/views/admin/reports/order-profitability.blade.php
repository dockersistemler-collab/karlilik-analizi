@extends('layouts.admin')

@section('header')
    Siparis Karlilik Analizi
@endsection

@section('content')
    @php
        $statusOptions = [
            'pending' => 'Beklemede',
            'approved' => 'Onaylandi',
            'shipped' => 'Kargoda',
            'delivered' => 'Teslim',
            'cancelled' => 'Iptal',
            'returned' => 'Iade',
        ];
    @endphp

    <style>
        .rp-check-col {
            width: 40px;
            text-align: center;
        }
        .rp-thumb-wrap {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: zoom-in;
            overflow: hidden;
        }
        .rp-thumb {
            width: 42px;
            height: 42px;
            object-fit: cover;
            border-radius: 10px;
        }
        .rp-thumb-placeholder {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
        }
        .rp-image-popover {
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
        .rp-image-popover.is-open { display: block; }
        .rp-image-popover img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #f8fafc;
        }
        .rp-inline-row > td { padding: 10px 0 0 0; }
        .rp-inline-row.is-hidden { display: none; }
        .rp-inline-panel {
            margin: 0 8px 10px;
            border: 1px solid #cfe0f5;
            border-radius: 18px;
            background: #f8fbff;
            padding: 14px;
        }
        .rp-inline-title { font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.2; }
        .rp-inline-subtitle { margin-top: 2px; font-size: 13px; color: #64748b; }
        .rp-inline-top {
            margin-top: 12px;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }
        .rp-inline-box {
            border: 1px solid #d4deea;
            border-radius: 16px;
            background: #fff;
            padding: 10px 14px;
            min-height: 52px;
        }
        .rp-inline-box span {
            display: block;
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 2px;
        }
        .rp-inline-box strong {
            display: block;
            font-size: 15px;
            line-height: 1.1;
            color: #0f172a;
            font-weight: 800;
        }
        .rp-inline-box strong.rp-green { color: #0f9a6f; }
        .rp-inline-metrics {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }
        .rp-inline-metric {
            border: 1px solid #d4deea;
            border-radius: 14px;
            background: #fff;
            min-height: 42px;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .rp-inline-icon {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            border: 1px solid #d3dee8;
            background: #f3f6fa;
            color: #1e3a5f;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            flex: 0 0 auto;
        }
        .rp-inline-content span {
            display: block;
            font-size: 11px;
            color: #64748b;
            line-height: 1.2;
        }
        .rp-inline-content strong {
            display: block;
            font-size: 13px;
            color: #334155;
            line-height: 1.2;
            margin-top: 1px;
            font-weight: 700;
        }
        .rp-inline-content strong.rp-red { color: #ef4444; }
        .rp-inline-content strong.rp-green { color: #0f9a6f; }
        @media (max-width: 1100px) {
            .rp-inline-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    <div class="panel-card p-6 mb-6 report-filter-panel">
        <form id="profitability-filters" method="GET" action="{{ route('portal.reports.order-profitability') }}" class="flex flex-wrap lg:flex-nowrap items-end gap-3 report-filter-form">
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
                <label class="block text-xs font-medium text-slate-500 mb-1">Durum</label>
                <select name="status" class="report-filter-control">
                    <option value="">Tumu</option>
                    @foreach($statusOptions as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? null) === $key)>{{ $label }}</option>
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
                        <button type="button" class="report-filter-chip text-xs {{ ($filters['quick_range'] ?? '') === $key ? 'is-active' : '' }}" data-quick-range="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>
                <input type="hidden" name="quick_range" id="quick-range-input" value="{{ $filters['quick_range'] ?? '' }}">
            </div>

            <div class="report-filter-actions">
                <button type="submit" class="report-filter-btn report-filter-btn-primary">Filtrele</button>
                <a href="{{ route('portal.reports.order-profitability') }}" class="report-filter-btn report-filter-btn-secondary">Temizle</a>
            </div>
        </form>
    </div>

    <div class="panel-card p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px] table-fixed">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="rp-check-col py-2 pr-2"><input type="checkbox" id="rp-select-all"></th>
                        <th class="text-left py-2 pr-3 w-[70px]">Gorsel</th>
                        <th class="text-left py-2 pr-4 w-[140px]">Pazaryeri</th>
                        <th class="text-left py-2 pr-4 w-[170px]">Siparis No</th>
                        <th class="text-left py-2 pr-4 w-[170px]">Siparis Tarihi</th>
                        <th class="text-center py-2 pr-4 w-[130px]">Siparis Tutari</th>
                        <th class="text-center py-2 pr-4 w-[120px]">Kar Tutari</th>
                        <th class="text-center py-2 pr-4 w-[100px]">Kar Orani</th>
                        <th class="text-center py-2 pr-4 w-[100px]">Kar Marji</th>
                        <th class="text-right py-2 w-[120px]">Detay</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        @php
                            $profitValue = (float) $row['profit_amount'];
                            $profitRate = (float) $row['profit_markup_percent'];
                            $profitMargin = (float) $row['profit_margin_percent'];
                            $detail = json_decode($row['breakdown'] ?? '[]', true);
                        @endphp
                        <tr>
                            @php
                                $imageUrl = $row['image_url'] ?? null;
                                $imageFallback = 'https://placehold.co/96x96/e2e8f0/64748b?text=Urun';
                            @endphp
                            <td class="py-2 pr-2 text-center"><input type="checkbox" class="rp-row-checkbox"></td>
                            <td class="py-2 pr-2.5 text-left">
                                @if($imageUrl)
                                    <span class="rp-thumb-wrap" tabindex="0" role="button" data-rp-preview-src="{{ $imageUrl }}" data-rp-preview-alt="{{ $row['order_number'] }}">
                                        <img src="{{ $imageUrl }}" alt="{{ $row['order_number'] }}" class="rp-thumb" loading="lazy" data-fallback-src="{{ $imageFallback }}" onerror="if(this.dataset.fallbackApplied==='1'){return;} this.dataset.fallbackApplied='1'; this.src=this.dataset.fallbackSrc;">
                                    </span>
                                @else
                                    <span class="rp-thumb-placeholder">-</span>
                                @endif
                            </td>
                            <td class="py-2 pr-2.5 text-slate-600">{{ $row['marketplace_name'] ?? '-' }}</td>
                            <td class="py-2 pr-2.5 text-slate-600">{{ $row['order_number'] }}</td>
                            <td class="py-2 pr-2.5 text-slate-600">{{ $row['order_date'] ? $row['order_date']->locale('tr')->translatedFormat('j M Y') : '-' }}</td>
                            <td class="py-2 pr-2.5 text-center tabular-nums text-slate-700">{{ number_format((float) $row['sale_price'], 2, ',', '.') }} TL</td>
                            <td class="py-2 pr-2.5 text-center tabular-nums {{ $profitValue < 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ number_format($profitValue, 2, ',', '.') }} TL</td>
                            <td class="py-2 pr-2.5 text-center tabular-nums {{ $profitRate < 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ number_format($profitRate, 2, ',', '.') }}</td>
                            <td class="py-2 pr-2.5 text-center tabular-nums {{ $profitMargin < 0 ? 'text-red-500' : 'text-emerald-600' }}">{{ number_format($profitMargin, 2, ',', '.') }}</td>
                            <td class="py-3 text-right">
                                <button type="button" class="btn btn-solid-accent px-2.5 py-1 text-[10px]" data-rp-toggle-detail
                                    data-order-number="{{ $row['order_number'] }}"
                                    data-sale-price="{{ number_format((float) $row['sale_price'], 2, ',', '.') }} TL"
                                    data-profit-amount="{{ number_format((float) $row['profit_amount'], 2, ',', '.') }} TL"
                                    data-product-cost="{{ number_format((float) ($detail['product_cost'] ?? 0), 2, ',', '.') }} TL"
                                    data-commission="{{ number_format((float) ($detail['commission_amount'] ?? 0), 2, ',', '.') }} TL"
                                    data-shipping-fee="{{ number_format((float) ($detail['shipping_fee'] ?? 0), 2, ',', '.') }} TL"
                                    data-platform-fee="{{ number_format((float) ($detail['platform_service_fee'] ?? 0), 2, ',', '.') }} TL"
                                    data-refund-adjustment="{{ number_format((float) ($detail['refunds_shipping_adjustment'] ?? 0), 2, ',', '.') }} TL"
                                    data-withholding-tax="{{ number_format((float) ($detail['withholding_tax_amount'] ?? 0), 2, ',', '.') }} TL"
                                    data-sales-vat="{{ number_format((float) ($detail['sales_vat_amount'] ?? 0), 2, ',', '.') }} TL"
                                    data-vat-rate="{{ number_format((float) ($detail['vat_rate_percent'] ?? 0), 2, ',', '.') }} %"
                                >Detayi Gor</button>
                            </td>
                        </tr>
                        <tr class="rp-inline-row is-hidden" data-rp-inline-row>
                            <td colspan="10">
                                <div class="rp-inline-panel">
                                    <div class="rp-inline-title">Siparis Karlilik Detayi</div>
                                    <div class="rp-inline-subtitle" data-rp-field="order-number">-</div>

                                    <div class="rp-inline-top">
                                        <div class="rp-inline-box">
                                            <span>Siparis Tutari</span>
                                            <strong data-rp-field="sale-price">-</strong>
                                        </div>
                                        <div class="rp-inline-box">
                                            <span>Kar Tutari</span>
                                            <strong class="rp-green" data-rp-field="profit-amount">-</strong>
                                        </div>
                                    </div>

                                    <div class="rp-inline-metrics">
                                        <div class="rp-inline-metric"><span class="rp-inline-icon"><i class="fa-solid fa-box"></i></span><div class="rp-inline-content"><span>Urun Maliyeti</span><strong class="rp-red" data-rp-field="product-cost">-</strong></div></div>
                                        <div class="rp-inline-metric"><span class="rp-inline-icon"><i class="fa-solid fa-percent"></i></span><div class="rp-inline-content"><span>Komisyon</span><strong class="rp-red" data-rp-field="commission">-</strong></div></div>
                                        <div class="rp-inline-metric"><span class="rp-inline-icon"><i class="fa-solid fa-truck"></i></span><div class="rp-inline-content"><span>Kargo</span><strong class="rp-red" data-rp-field="shipping-fee">-</strong></div></div>
                                        <div class="rp-inline-metric"><span class="rp-inline-icon"><i class="fa-solid fa-gears"></i></span><div class="rp-inline-content"><span>Platform Hizmeti</span><strong class="rp-red" data-rp-field="platform-fee">-</strong></div></div>
                                        <div class="rp-inline-metric"><span class="rp-inline-icon"><i class="fa-solid fa-rotate-left"></i></span><div class="rp-inline-content"><span>Iade Kargo Duzeltmesi</span><strong class="rp-red" data-rp-field="refund-adjustment">-</strong></div></div>
                                        <div class="rp-inline-metric"><span class="rp-inline-icon"><i class="fa-solid fa-file-invoice-dollar"></i></span><div class="rp-inline-content"><span>Stopaj (%1)</span><strong class="rp-red" data-rp-field="withholding-tax">-</strong></div></div>
                                        <div class="rp-inline-metric"><span class="rp-inline-icon"><i class="fa-solid fa-receipt"></i></span><div class="rp-inline-content"><span>Satis KDV</span><strong class="rp-red" data-rp-field="sales-vat">-</strong></div></div>
                                        <div class="rp-inline-metric"><span class="rp-inline-icon"><i class="fa-solid fa-chart-line"></i></span><div class="rp-inline-content"><span>KDV Orani</span><strong class="is-neutral" data-rp-field="vat-rate">-</strong></div></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-4 text-center text-slate-500">Kayit bulunamadi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div id="rp-image-popover" class="rp-image-popover" aria-hidden="true">
        <img id="rp-image-popover-img" src="" alt="">
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('profitability-filters');
    const quickInput = document.getElementById('quick-range-input');
    const dateFrom = form?.querySelector('input[name="date_from"]');
    const dateTo = form?.querySelector('input[name="date_to"]');

    document.querySelectorAll('[data-quick-range]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (quickInput) quickInput.value = btn.getAttribute('data-quick-range') || '';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            form?.submit();
        });
    });

    const closeAll = (tbody) => {
        tbody.querySelectorAll('[data-rp-inline-row]').forEach((row) => row.classList.add('is-hidden'));
        tbody.querySelectorAll('[data-rp-toggle-detail]').forEach((btn) => btn.textContent = 'Detayi Gor');
    };

    document.querySelectorAll('[data-rp-toggle-detail]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            const detailRow = row?.nextElementSibling;
            const tbody = row?.parentElement;
            if (!detailRow || !detailRow.matches('[data-rp-inline-row]') || !tbody) return;

            const isOpen = !detailRow.classList.contains('is-hidden');
            closeAll(tbody);
            if (isOpen) return;

            const setField = (key, value) => {
                const el = detailRow.querySelector(`[data-rp-field="${key}"]`);
                if (el) el.textContent = value || '-';
            };

            setField('order-number', btn.getAttribute('data-order-number'));
            setField('sale-price', btn.getAttribute('data-sale-price'));
            setField('profit-amount', btn.getAttribute('data-profit-amount'));
            setField('product-cost', btn.getAttribute('data-product-cost'));
            setField('commission', btn.getAttribute('data-commission'));
            setField('shipping-fee', btn.getAttribute('data-shipping-fee'));
            setField('platform-fee', btn.getAttribute('data-platform-fee'));
            setField('refund-adjustment', btn.getAttribute('data-refund-adjustment'));
            setField('withholding-tax', btn.getAttribute('data-withholding-tax'));
            setField('sales-vat', btn.getAttribute('data-sales-vat'));
            setField('vat-rate', btn.getAttribute('data-vat-rate'));

            detailRow.classList.remove('is-hidden');
            btn.textContent = 'Detayi Gizle';
        });
    });

    const selectAll = document.getElementById('rp-select-all');
    const rowCheckboxes = () => Array.from(document.querySelectorAll('.rp-row-checkbox'));
    selectAll?.addEventListener('change', () => {
        rowCheckboxes().forEach((cb) => {
            cb.checked = Boolean(selectAll.checked);
        });
    });

    const imagePopover = document.getElementById('rp-image-popover');
    const imagePopoverImg = document.getElementById('rp-image-popover-img');
    const imageTriggers = Array.from(document.querySelectorAll('[data-rp-preview-src]'));
    if (imagePopover && imagePopoverImg && imageTriggers.length) {
        const placePopover = (event) => {
            const offset = 16;
            const width = 150;
            const height = 150;
            let left = event.clientX + offset;
            let top = event.clientY + offset;

            if (left + width > window.innerWidth - 8) left = event.clientX - width - offset;
            if (top + height > window.innerHeight - 8) top = event.clientY - height - offset;

            imagePopover.style.left = `${Math.max(8, left)}px`;
            imagePopover.style.top = `${Math.max(8, top)}px`;
        };

        const hidePopover = () => {
            imagePopover.classList.remove('is-open');
            imagePopoverImg.removeAttribute('src');
        };

        const openPopover = (trigger, event) => {
            const thumbImg = trigger.querySelector('img');
            const src = thumbImg?.currentSrc || thumbImg?.getAttribute('src') || trigger.getAttribute('data-rp-preview-src') || '';
            if (!src) return;
            imagePopoverImg.src = src;
            imagePopoverImg.alt = trigger.getAttribute('data-rp-preview-alt') || 'Urun gorseli';
            imagePopover.classList.add('is-open');
            placePopover(event);
        };

        imageTriggers.forEach((trigger) => {
            trigger.addEventListener('mouseenter', (event) => openPopover(trigger, event));
            trigger.addEventListener('mousemove', placePopover);
            trigger.addEventListener('mouseleave', hidePopover);
            trigger.addEventListener('focus', () => {
                const rect = trigger.getBoundingClientRect();
                openPopover(trigger, { clientX: rect.left + (rect.width / 2), clientY: rect.top + (rect.height / 2) });
            });
            trigger.addEventListener('blur', hidePopover);
            trigger.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') hidePopover();
            });
        });
    }
})();
</script>
@endpush



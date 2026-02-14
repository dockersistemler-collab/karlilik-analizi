@extends('layouts.admin')

@section('header')
    Sipariş Kârlılık Analizi
@endsection

@section('content')
    @php
        $statusOptions = [
            'pending' => 'Beklemede',
            'approved' => 'Onaylandı',
            'shipped' => 'Kargoda',
            'delivered' => 'Teslim',
            'cancelled' => 'İptal',
            'returned' => 'İade',
        ];
    @endphp

    <div class="panel-card p-6 mb-6">
        <form id="profitability-filters" method="GET" action="{{ route('portal.reports.order-profitability') }}" class="flex flex-wrap lg:flex-nowrap items-end gap-3">
            <div class="min-w-[180px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Satış Kanalı</label>
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
                <label class="block text-xs font-medium text-slate-500 mb-1">Durum</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                    <option value="">Tümü</option>
                    @foreach($statusOptions as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['status'] ?? null) === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Başlangıç</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>

            <div class="min-w-[150px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Bitiş</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>

            <div class="min-w-[220px]">
                <label class="block text-xs font-medium text-slate-500 mb-1">Hızlı Seçim</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($quickRanges as $key => $label)
                        <button
                            type="button"
                            class="btn btn-outline text-xs {{ ($filters['quick_range'] ?? '') === $key ? 'btn-solid-accent' : '' }}"
                            data-quick-range="{{ $key }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
                <input type="hidden" name="quick_range" id="quick-range-input" value="{{ $filters['quick_range'] ?? '' }}">
            </div>

            <div class="flex items-center gap-2 lg:ml-auto">
                <button type="submit" class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('portal.reports.order-profitability') }}" class="btn btn-outline">Temizle</a>
            </div>
        </form>
    </div>

    <div class="panel-card p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm table-fixed">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-4 w-[140px]">Pazaryeri</th>
                        <th class="text-left py-2 pr-4 w-[170px]">Sipariş Numarası</th>
                        <th class="text-left py-2 pr-4 w-[180px]">Sipariş Tarihi</th>
                        <th class="text-center py-2 pr-4 w-[150px] tabular-nums">Sipariş Tutarı (₺)</th>
                        <th class="text-center py-2 pr-4 w-[150px] tabular-nums">Kâr Tutarı (₺)</th>
                        <th class="text-center py-2 pr-4 w-[120px] tabular-nums">Kâr Oranı (%)</th>
                        <th class="text-center py-2 pr-4 w-[120px] tabular-nums">Kâr Marjı (%)</th>
                        <th class="text-right py-2 w-[130px]">Detay Bilgiler</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="py-3 pr-4 text-slate-600">{{ $row['marketplace_name'] ?? '-' }}</td>
                            <td class="py-3 pr-4 text-slate-600">
                                {{ $row['order_number'] }}
                            </td>
                            <td class="py-3 pr-4 text-slate-600">
                                {{ $row['order_date'] ? $row['order_date']->locale('tr')->translatedFormat('j M Y') : '-' }}
                            </td>
                            <td class="py-3 pr-4 text-center text-slate-700 tabular-nums">{{ number_format((float) $row['sale_price'], 2, ',', '.') }} ₺</td>
                            @php
                                $profitValue = (float) $row['profit_amount'];
                                $profitRate = (float) $row['profit_markup_percent'];
                                $profitMargin = (float) $row['profit_margin_percent'];
                            @endphp
                            <td class="py-3 pr-4 text-center tabular-nums {{ $profitValue < 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                {{ number_format($profitValue, 2, ',', '.') }} ₺
                            </td>
                            <td class="py-3 pr-4 text-center tabular-nums {{ $profitRate < 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                {{ number_format($profitRate, 2, ',', '.') }}
                            </td>
                            <td class="py-3 pr-4 text-center tabular-nums {{ $profitMargin < 0 ? 'text-red-500' : 'text-emerald-600' }}">
                                {{ number_format($profitMargin, 2, ',', '.') }}
                            </td>
                            @php
                                $detail = json_decode($row['breakdown'] ?? '[]', true);
                            @endphp
                            <td class="py-3 text-right">
                                <button
                                    type="button"
                                    class="btn btn-solid-accent px-4 py-2 text-xs"
                                    data-profit-detail
                                    data-order-number="{{ $row['order_number'] }}"
                                    data-sale-price="{{ number_format((float) $row['sale_price'], 2, ',', '.') }} ₺"
                                    data-profit-amount="{{ number_format((float) $row['profit_amount'], 2, ',', '.') }} ₺"
                                    data-product-cost="{{ number_format((float) ($detail['product_cost'] ?? 0), 2, ',', '.') }} ₺"
                                    data-commission="{{ number_format((float) ($detail['commission_amount'] ?? 0), 2, ',', '.') }} ₺"
                                    data-shipping-fee="{{ number_format((float) ($detail['shipping_fee'] ?? 0), 2, ',', '.') }} ₺"
                                    data-platform-fee="{{ number_format((float) ($detail['platform_service_fee'] ?? 0), 2, ',', '.') }} ₺"
                                    data-refund-adjustment="{{ number_format((float) ($detail['refunds_shipping_adjustment'] ?? 0), 2, ',', '.') }} ₺"
                                    data-withholding-tax="{{ number_format((float) ($detail['withholding_tax_amount'] ?? 0), 2, ',', '.') }} ₺"
                                    data-sales-vat="{{ number_format((float) ($detail['sales_vat_amount'] ?? 0), 2, ',', '.') }} ₺"
                                    data-vat-rate="{{ number_format((float) ($detail['vat_rate_percent'] ?? 0), 2, ',', '.') }} %"
                                >Detayı Gör</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-4 text-center text-slate-500">Kayıt bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="profitability-modal" class="fixed inset-0 z-50 hidden">
        <div id="profitability-modal-overlay" class="absolute inset-0 bg-slate-900/50"></div>
        <div class="relative mx-auto mt-24 w-full max-w-lg">
            <div class="panel-card p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-800">Sipariş Kârlılık Detayı</h3>
                        <p class="text-xs text-slate-500" id="modal-order-number">-</p>
                    </div>
                    <button type="button" id="profitability-modal-close" class="btn btn-outline-accent px-3 py-1 text-xs">Kapat</button>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs text-slate-600">
                    <div class="panel-card p-3 border-slate-100">
                        <div class="text-[11px] text-slate-400">Sipariş Tutarı</div>
                        <div class="text-sm font-semibold text-slate-800" id="modal-sale-price">-</div>
                    </div>
                    <div class="panel-card p-3 border-slate-100">
                        <div class="text-[11px] text-slate-400">Kâr Tutarı</div>
                        <div class="text-sm font-semibold text-slate-800" id="modal-profit-amount">-</div>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-2 text-xs text-slate-600">
                    <div class="flex items-center justify-between"><span>Ürün Maliyeti</span><span id="modal-product-cost" class="text-red-500">-</span></div>
                    <div class="flex items-center justify-between"><span>Komisyon</span><span id="modal-commission" class="text-red-500">-</span></div>
                    <div class="flex items-center justify-between"><span>Kargo</span><span id="modal-shipping-fee" class="text-red-500">-</span></div>
                    <div class="flex items-center justify-between"><span>Platform Hizmeti</span><span id="modal-platform-fee" class="text-red-500">-</span></div>
                    <div class="flex items-center justify-between"><span>İade Kargo Düzeltmesi</span><span id="modal-refund-adjustment" class="text-red-500">-</span></div>
                    <div class="flex items-center justify-between"><span>Stopaj (%1)</span><span id="modal-withholding-tax" class="text-red-500">-</span></div>
                    <div class="flex items-center justify-between"><span>Satış KDV</span><span id="modal-sales-vat" class="text-red-500">-</span></div>
                    <div class="flex items-center justify-between"><span>KDV Oranı</span><span id="modal-vat-rate">-</span></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const modal = document.getElementById('profitability-modal');
            const overlay = document.getElementById('profitability-modal-overlay');
            const closeBtn = document.getElementById('profitability-modal-close');

            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value ?? '-';
            };
            const setSignColor = (id, value) => {
                const el = document.getElementById(id);
                if (!el) return;
                const normalized = (value || '').toString().replace(/\s/g, '').replace('₺', '').replace('%', '').replace('.', '').replace(',', '.');
                const num = parseFloat(normalized);
                el.classList.remove('text-emerald-600', 'text-red-500', 'text-slate-800');
                if (!Number.isNaN(num)) {
                    el.classList.add(num < 0 ? 'text-red-500' : 'text-emerald-600');
                }
            };

            function openModal(data) {
                setText('modal-order-number', data.orderNumber);
                setText('modal-sale-price', data.salePrice);
                setText('modal-profit-amount', data.profitAmount);
                setText('modal-product-cost', data.productCost);
                setText('modal-commission', data.commission);
                setText('modal-shipping-fee', data.shippingFee);
                setText('modal-platform-fee', data.platformFee);
                setText('modal-refund-adjustment', data.refundAdjustment);
                setText('modal-withholding-tax', data.withholdingTax);
                setText('modal-sales-vat', data.salesVat);
                setText('modal-vat-rate', data.vatRate);

                setSignColor('modal-profit-amount', data.profitAmount);
                modal?.classList.remove('hidden');
            }

            function closeModal() {
                modal?.classList.add('hidden');
            }

            document.querySelectorAll('[data-profit-detail]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const data = {
                        orderNumber: btn.getAttribute('data-order-number'),
                        salePrice: btn.getAttribute('data-sale-price'),
                        profitAmount: btn.getAttribute('data-profit-amount'),
                        productCost: btn.getAttribute('data-product-cost'),
                        commission: btn.getAttribute('data-commission'),
                        shippingFee: btn.getAttribute('data-shipping-fee'),
                        platformFee: btn.getAttribute('data-platform-fee'),
                        refundAdjustment: btn.getAttribute('data-refund-adjustment'),
                        withholdingTax: btn.getAttribute('data-withholding-tax'),
                        salesVat: btn.getAttribute('data-sales-vat'),
                        vatRate: btn.getAttribute('data-vat-rate'),
                    };
                    openModal(data);
                });
            });

            overlay?.addEventListener('click', closeModal);
            closeBtn?.addEventListener('click', closeModal);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        })();
    </script>
@endpush

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('profitability-filters');
            const quickInput = document.getElementById('quick-range-input');
            const dateFrom = form?.querySelector('input[name="date_from"]');
            const dateTo = form?.querySelector('input[name="date_to"]');

            document.querySelectorAll('[data-quick-range]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (quickInput) {
                        quickInput.value = btn.getAttribute('data-quick-range') || '';
                    }
                    if (dateFrom) dateFrom.value = '';
                    if (dateTo) dateTo.value = '';
                    form?.submit();
                });
            });
        })();
    </script>
@endpush

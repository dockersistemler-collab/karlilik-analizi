@extends('layouts.admin')

@section('header')
    Ne Kazanırım
@endsection

@section('content')
    <style>
        .nk-page {
            background:
                radial-gradient(circle at 14% 10%, rgba(59, 130, 246, 0.05), transparent 240px),
                radial-gradient(circle at 90% 24%, rgba(16, 185, 129, 0.05), transparent 260px),
                #f7f8fa;
            border: 1px solid rgba(15, 23, 42, 0.05);
            border-radius: 16px;
            padding: 20px;
        }

        .nk-layout {
            display: block;
        }

        .nk-left {
            min-width: 0;
            margin-bottom: 22px;
        }

        .nk-right {
            min-width: 0;
        }

        .nk-card {
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.07);
            background: #ffffff;
        }

        .nk-card-header {
            background: transparent;
            border-bottom: 1px solid rgba(148, 163, 184, 0.16);
            padding: 14px 16px;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nk-card .card-body {
            padding: 16px;
        }

        .nk-right-stack {
            max-width: 430px;
            margin-left: auto;
            row-gap: 48px !important;
        }

        .nk-right-stack > .nk-card + .nk-card {
            margin-top: 10px;
        }

        .nk-right-stack .nk-card {
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .nk-right-stack .nk-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.1);
        }

        .nk-gear {
            color: #22c3ee;
            font-size: 22px;
            line-height: 1;
        }

        .nk-soft-input,
        .nk-soft-select {
            border: 1px solid #d7dce4;
            border-radius: 12px;
            background: #f8fafc;
            min-height: 48px;
            font-weight: 600;
            color: #334155;
            box-shadow: inset 0 1px 1px rgba(15, 23, 42, 0.03);
        }

        .nk-soft-input:focus,
        .nk-soft-select:focus {
            border-color: #38bdf8;
            box-shadow: 0 0 0 0.2rem rgba(56, 189, 248, 0.16);
        }

        .nk-kdv-row,
        .nk-profit-row {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        .nk-kdv-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
            user-select: none;
        }

        .nk-kdv-radio {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .nk-kdv-toggle {
            width: 42px;
            height: 28px;
            border-radius: 999px;
            border: 1px solid #c9ced6;
            background: #f3f4f6;
            position: relative;
            transition: background-color .18s ease, border-color .18s ease;
        }

        .nk-kdv-toggle::after {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #2f3135;
            transition: transform .18s ease, background-color .18s ease;
        }

        .nk-kdv-radio:checked + .nk-kdv-toggle {
            border-color: #fb923c;
            background: #ffe8d8;
        }

        .nk-kdv-radio:checked + .nk-kdv-toggle::after {
            transform: translateX(14px);
            background: #fb923c;
        }

        .nk-result {
            font-size: 52px;
            line-height: 1;
            font-weight: 800;
            color: #0f172a;
            text-align: right;
            margin: 4px 0 18px;
        }

        .nk-action {
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: #ffffff;
            font-weight: 700;
            min-height: 50px;
            box-shadow: 0 8px 18px rgba(22, 163, 74, 0.3);
        }

        .nk-action:hover {
            color: #ffffff;
            filter: brightness(0.98);
        }

        .nk-table-wrap {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            max-height: 520px;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .nk-profit-table {
            width: 100%;
            table-layout: auto;
            font-size: 13px;
        }

        .nk-profit-table thead th {
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .03em;
            font-size: 11px;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 8px;
            font-weight: 600;
            white-space: normal;
        }

        .nk-profit-table tbody td {
            border-bottom: 1px solid #f1f5f9;
            padding: 10px 8px;
            color: #475569;
            vertical-align: middle;
        }

        .nk-profit-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .nk-check-col {
            width: 44px;
            text-align: center;
        }

        .nk-num-col {
            text-align: center;
            font-variant-numeric: tabular-nums;
        }

        .nk-detail-col {
            text-align: right;
        }

        .nk-detail-btn {
            border: 0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            padding: 6px 10px;
            color: #ffffff;
            background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
            white-space: nowrap;
        }

        .nk-modal {
            position: fixed;
            inset: 0;
            z-index: 1100;
            display: none;
        }

        .nk-modal.is-open {
            display: block;
        }

        .nk-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
        }

        .nk-modal-dialog {
            position: relative;
            width: min(680px, calc(100% - 32px));
            margin: 70px auto 0;
        }

        .nk-modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 14px;
        }

        .nk-modal-box {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
            background: #ffffff;
        }

        .nk-modal-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            padding: 4px 0;
            color: #475569;
        }

        .nk-negative {
            color: #ef4444;
        }

        .nk-positive {
            color: #10b981;
        }

        @media (min-width: 992px) {
            .nk-layout {
                display: grid;
                grid-template-columns: minmax(0, 1fr) 430px;
                align-items: start;
                column-gap: 30px;
            }

            .nk-left {
                margin-bottom: 0;
            }
        }

        @media (max-width: 991.98px) {
            .nk-right-stack {
                max-width: 100%;
                margin-left: 0;
                row-gap: 32px !important;
            }

            .nk-result {
                text-align: center;
            }

            .nk-modal-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $sampleRows = [
            [
                'marketplace' => 'Trendyol',
                'order_number' => 'TR-100245',
                'order_date' => '17 Şub 2026',
                'sale_price' => '1.249,90 ₺',
                'profit_amount' => '218,35 ₺',
                'profit_rate' => '21,17',
                'profit_margin' => '17,47',
                'detail' => [
                    'product_cost' => '720,00 ₺',
                    'commission' => '85,50 ₺',
                    'shipping_fee' => '42,90 ₺',
                    'platform_fee' => '18,10 ₺',
                    'refund_adjustment' => '0,00 ₺',
                    'withholding_tax' => '12,60 ₺',
                    'sales_vat' => '152,45 ₺',
                    'vat_rate' => '20,00 %',
                ],
            ],
            [
                'marketplace' => 'Hepsiburada',
                'order_number' => 'HB-775421',
                'order_date' => '16 Şub 2026',
                'sale_price' => '899,00 ₺',
                'profit_amount' => '-42,10 ₺',
                'profit_rate' => '-4,68',
                'profit_margin' => '-4,92',
                'detail' => [
                    'product_cost' => '680,00 ₺',
                    'commission' => '64,90 ₺',
                    'shipping_fee' => '38,40 ₺',
                    'platform_fee' => '13,20 ₺',
                    'refund_adjustment' => '8,30 ₺',
                    'withholding_tax' => '9,00 ₺',
                    'sales_vat' => '127,20 ₺',
                    'vat_rate' => '20,00 %',
                ],
            ],
            [
                'marketplace' => 'n11',
                'order_number' => 'N11-650712',
                'order_date' => '16 Şub 2026',
                'sale_price' => '1.590,00 ₺',
                'profit_amount' => '324,80 ₺',
                'profit_rate' => '25,67',
                'profit_margin' => '20,43',
                'detail' => [
                    'product_cost' => '910,00 ₺',
                    'commission' => '114,80 ₺',
                    'shipping_fee' => '51,00 ₺',
                    'platform_fee' => '22,90 ₺',
                    'refund_adjustment' => '0,00 ₺',
                    'withholding_tax' => '15,90 ₺',
                    'sales_vat' => '150,60 ₺',
                    'vat_rate' => '10,00 %',
                ],
            ],
        ];
    @endphp

    <div class="nk-page">
        <div class="nk-layout">
            <div class="nk-left">
                <div class="nk-card h-100">
                    <div class="nk-card-header">
                        <span>Tablo / Ürünler</span>
                        <button type="button" class="btn btn-outline btn-sm">Ayarlar</button>
                    </div>
                    <div class="card-body">
                        <div class="nk-table-wrap">
                            <table class="nk-profit-table">
                                <thead>
                                    <tr>
                                        <th class="nk-check-col">
                                            <input type="checkbox" id="nk-select-all">
                                        </th>
                                        <th class="text-left">Pazaryeri</th>
                                        <th class="text-left">Sipariş Numarası</th>
                                        <th class="text-left">Sipariş Tarihi</th>
                                        <th class="nk-num-col">Sipariş Tutarı (₺)</th>
                                        <th class="nk-num-col">Kâr Tutarı (₺)</th>
                                        <th class="nk-num-col">Kâr Oranı (%)</th>
                                        <th class="nk-num-col">Kâr Marjı (%)</th>
                                        <th class="nk-detail-col">Detay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sampleRows as $index => $row)
                                        @php
                                            $negative = str_starts_with($row['profit_amount'], '-');
                                        @endphp
                                        <tr>
                                            <td class="nk-check-col">
                                                <input type="checkbox" class="nk-row-check" name="selected_rows[]" value="{{ $index }}">
                                            </td>
                                            <td>{{ $row['marketplace'] }}</td>
                                            <td>{{ $row['order_number'] }}</td>
                                            <td>{{ $row['order_date'] }}</td>
                                            <td class="nk-num-col">{{ $row['sale_price'] }}</td>
                                            <td class="nk-num-col {{ $negative ? 'nk-negative' : 'nk-positive' }}">{{ $row['profit_amount'] }}</td>
                                            <td class="nk-num-col {{ $negative ? 'nk-negative' : 'nk-positive' }}">{{ $row['profit_rate'] }}</td>
                                            <td class="nk-num-col {{ $negative ? 'nk-negative' : 'nk-positive' }}">{{ $row['profit_margin'] }}</td>
                                            <td class="nk-detail-col">
                                                <button
                                                    type="button"
                                                    class="nk-detail-btn"
                                                    data-nk-detail
                                                    data-order-number="{{ $row['order_number'] }}"
                                                    data-sale-price="{{ $row['sale_price'] }}"
                                                    data-profit-amount="{{ $row['profit_amount'] }}"
                                                    data-product-cost="{{ $row['detail']['product_cost'] }}"
                                                    data-commission="{{ $row['detail']['commission'] }}"
                                                    data-shipping-fee="{{ $row['detail']['shipping_fee'] }}"
                                                    data-platform-fee="{{ $row['detail']['platform_fee'] }}"
                                                    data-refund-adjustment="{{ $row['detail']['refund_adjustment'] }}"
                                                    data-withholding-tax="{{ $row['detail']['withholding_tax'] }}"
                                                    data-sales-vat="{{ $row['detail']['sales_vat'] }}"
                                                    data-vat-rate="{{ $row['detail']['vat_rate'] }}"
                                                >Detayı Gör</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nk-right">
                <div class="d-flex flex-column nk-right-stack">
                    <div class="nk-card">
                        <div class="nk-card-header">
                            <span>Seçimler</span>
                            <i class="fa-regular fa-circle-question nk-gear"></i>
                        </div>
                        <div class="card-body">
                            <label for="product_cost" class="form-label fw-bold">Ürün Maliyeti</label>
                            <input type="number" step="0.01" min="0" class="form-control nk-soft-input" id="product_cost" name="product_cost" placeholder="₺ 0">
                        </div>
                    </div>

                    <div class="nk-card">
                        <div class="nk-card-header">
                            <span>İstenilen Kâr Oranı / Tutarı</span>
                            <i class="fa-regular fa-circle-question nk-gear"></i>
                        </div>
                        <div class="card-body">
                            <div class="nk-profit-row mb-3">
                                <label class="nk-kdv-item">
                                    <input class="nk-kdv-radio" type="radio" name="profit_target_type" value="amount" checked>
                                    <span class="nk-kdv-toggle" aria-hidden="true"></span>
                                    <span>Tutara Göre (₺)</span>
                                </label>
                                <label class="nk-kdv-item">
                                    <input class="nk-kdv-radio" type="radio" name="profit_target_type" value="rate">
                                    <span class="nk-kdv-toggle" aria-hidden="true"></span>
                                    <span>Orana Göre (%)</span>
                                </label>
                            </div>
                            <input type="number" step="0.01" min="0" class="form-control nk-soft-input" id="profit_target_value" name="profit_target_value" placeholder="₺ 0">
                        </div>
                    </div>

                    <div class="nk-card">
                        <div class="nk-card-header">
                            <span>Kargo Ücreti</span>
                            <i class="fa-regular fa-circle-question nk-gear"></i>
                        </div>
                        <div class="card-body">
                            <input type="number" step="0.01" min="0" class="form-control nk-soft-input" id="shipping_fee" name="shipping_fee" placeholder="₺ 0">
                        </div>
                    </div>

                    <div class="nk-card">
                        <div class="nk-card-header">
                            <span>KDV (%)</span>
                            <i class="fa-regular fa-circle-question nk-gear"></i>
                        </div>
                        <div class="card-body">
                            <div class="nk-kdv-row mb-3">
                                <label class="nk-kdv-item">
                                    <input class="nk-kdv-radio" type="radio" name="vat_rate_chip" value="20" checked>
                                    <span class="nk-kdv-toggle" aria-hidden="true"></span>
                                    <span>20 %</span>
                                </label>
                                <label class="nk-kdv-item">
                                    <input class="nk-kdv-radio" type="radio" name="vat_rate_chip" value="10">
                                    <span class="nk-kdv-toggle" aria-hidden="true"></span>
                                    <span>10 %</span>
                                </label>
                                <label class="nk-kdv-item">
                                    <input class="nk-kdv-radio" type="radio" name="vat_rate_chip" value="1">
                                    <span class="nk-kdv-toggle" aria-hidden="true"></span>
                                    <span>1 %</span>
                                </label>
                                <label class="nk-kdv-item">
                                    <input class="nk-kdv-radio" type="radio" name="vat_rate_chip" value="0">
                                    <span class="nk-kdv-toggle" aria-hidden="true"></span>
                                    <span>0 %</span>
                                </label>
                            </div>
                            <input type="number" step="0.01" min="0" class="form-control nk-soft-input" id="vat_rate_custom" name="vat_rate_custom" placeholder="20">
                        </div>
                    </div>

                    <div class="nk-card">
                        <div class="card-body">
                            <div class="nk-result" id="result_amount">₺0</div>
                            <button type="button" class="btn nk-action w-100" id="calculate_button" name="calculate_button">
                                <i class="fa-regular fa-tag me-2"></i>Fiyat Oluştur
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="nk-detail-modal" class="nk-modal">
        <div class="nk-modal-backdrop" id="nk-detail-backdrop"></div>
        <div class="nk-modal-dialog">
            <div class="nk-card">
                <div class="nk-card-header">
                    <div>
                        <div class="fw-bold">Sipariş Kârlılık Detayı</div>
                        <div class="text-muted small" id="nk-modal-order-number">-</div>
                    </div>
                    <button type="button" class="btn btn-outline btn-sm" id="nk-modal-close">Kapat</button>
                </div>
                <div class="card-body">
                    <div class="nk-modal-grid">
                        <div class="nk-modal-box">
                            <div class="text-muted small mb-1">Sipariş Tutarı</div>
                            <div class="fw-bold" id="nk-modal-sale-price">-</div>
                        </div>
                        <div class="nk-modal-box">
                            <div class="text-muted small mb-1">Kâr Tutarı</div>
                            <div class="fw-bold" id="nk-modal-profit-amount">-</div>
                        </div>
                    </div>

                    <div class="nk-modal-line"><span>Ürün Maliyeti</span><span id="nk-modal-product-cost">-</span></div>
                    <div class="nk-modal-line"><span>Komisyon</span><span id="nk-modal-commission">-</span></div>
                    <div class="nk-modal-line"><span>Kargo</span><span id="nk-modal-shipping-fee">-</span></div>
                    <div class="nk-modal-line"><span>Platform Hizmeti</span><span id="nk-modal-platform-fee">-</span></div>
                    <div class="nk-modal-line"><span>İade Kargo Düzeltmesi</span><span id="nk-modal-refund-adjustment">-</span></div>
                    <div class="nk-modal-line"><span>Stopaj</span><span id="nk-modal-withholding-tax">-</span></div>
                    <div class="nk-modal-line"><span>Satış KDV</span><span id="nk-modal-sales-vat">-</span></div>
                    <div class="nk-modal-line"><span>KDV Oranı</span><span id="nk-modal-vat-rate">-</span></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const selectAll = document.getElementById('nk-select-all');
            if (!selectAll) return;
            const rowChecks = Array.from(document.querySelectorAll('.nk-row-check'));

            selectAll.addEventListener('change', function () {
                rowChecks.forEach((checkbox) => {
                    checkbox.checked = selectAll.checked;
                });
            });

            const modal = document.getElementById('nk-detail-modal');
            const backdrop = document.getElementById('nk-detail-backdrop');
            const closeBtn = document.getElementById('nk-modal-close');

            const setText = (id, value) => {
                const el = document.getElementById(id);
                if (el) el.textContent = value || '-';
            };

            const openModal = (data) => {
                setText('nk-modal-order-number', data.orderNumber);
                setText('nk-modal-sale-price', data.salePrice);
                setText('nk-modal-profit-amount', data.profitAmount);
                setText('nk-modal-product-cost', data.productCost);
                setText('nk-modal-commission', data.commission);
                setText('nk-modal-shipping-fee', data.shippingFee);
                setText('nk-modal-platform-fee', data.platformFee);
                setText('nk-modal-refund-adjustment', data.refundAdjustment);
                setText('nk-modal-withholding-tax', data.withholdingTax);
                setText('nk-modal-sales-vat', data.salesVat);
                setText('nk-modal-vat-rate', data.vatRate);
                modal?.classList.add('is-open');
            };

            const closeModal = () => modal?.classList.remove('is-open');

            document.querySelectorAll('[data-nk-detail]').forEach((button) => {
                button.addEventListener('click', () => {
                    openModal({
                        orderNumber: button.getAttribute('data-order-number'),
                        salePrice: button.getAttribute('data-sale-price'),
                        profitAmount: button.getAttribute('data-profit-amount'),
                        productCost: button.getAttribute('data-product-cost'),
                        commission: button.getAttribute('data-commission'),
                        shippingFee: button.getAttribute('data-shipping-fee'),
                        platformFee: button.getAttribute('data-platform-fee'),
                        refundAdjustment: button.getAttribute('data-refund-adjustment'),
                        withholdingTax: button.getAttribute('data-withholding-tax'),
                        salesVat: button.getAttribute('data-sales-vat'),
                        vatRate: button.getAttribute('data-vat-rate')
                    });
                });
            });

            backdrop?.addEventListener('click', closeModal);
            closeBtn?.addEventListener('click', closeModal);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeModal();
            });
        })();
    </script>
@endpush

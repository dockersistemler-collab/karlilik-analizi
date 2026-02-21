@extends('layouts.admin')

@section('header')
    Kategori/Marka Raporu
@endsection

@section('content')
    <style>
        .rp-detail-row > td { padding: 12px 0 0 0; }
        .rp-detail-row.is-hidden { display: none; }
        .rp-detail-panel { margin: 0 8px 10px; border: 1px solid #dbe3ee; border-radius: 18px; background: #f8fafc; padding: 14px; }
        .rp-detail-title { font-size: 19px; font-weight: 800; color: #0f172a; line-height: 1.1; }
        .rp-detail-subtitle { margin-top: 4px; font-size: 11px; color: #64748b; }
        .rp-detail-top { margin-top: 12px; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .rp-detail-box { border: 1px solid #dbe3ee; border-radius: 18px; background: #fff; padding: 12px 14px; }
        .rp-detail-box span { display:block; font-size: 11px; color: #94a3b8; }
        .rp-detail-box strong { display:block; font-size: 16px; line-height: 1.1; color: #0f172a; margin-top: 3px; }
        .rp-detail-box strong.is-positive { color: #059669; }
        .rp-detail-list { margin-top: 14px; display:grid; gap: 6px; }
        .rp-detail-line { display:flex; justify-content:space-between; align-items:center; font-size: 13px; color:#475569; }
        .rp-detail-line strong { color:#ef4444; font-weight:700; }
        .rp-detail-line strong.is-neutral { color:#475569; }
    </style>

    <div class="panel-card p-3 mb-4">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('portal.reports.category-sales', request()->query()) }}" class="report-filter-chip {{ request()->routeIs('portal.reports.category-sales') ? 'is-active' : '' }}">Kategori</a>
            <a href="{{ route('portal.reports.brand-sales', request()->query()) }}" class="report-filter-chip {{ request()->routeIs('portal.reports.brand-sales') ? 'is-active' : '' }}">Marka</a>
        </div>
    </div>

    <div class="panel-card p-6 mb-6 report-filter-panel">
        <form id="category-sales-filters" method="GET" action="{{ route('portal.reports.category-sales') }}" class="flex flex-wrap lg:flex-nowrap items-end gap-3 report-filter-form">
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
                        <button type="button" class="report-filter-chip text-xs {{ ($filters['quick_range'] ?? '') === $key ? 'is-active' : '' }}" data-quick-range="{{ $key }}">{{ $label }}</button>
                    @endforeach
                </div>
                <input type="hidden" name="quick_range" id="category-quick-range-input" value="{{ $filters['quick_range'] ?? '' }}">
            </div>

            <div class="report-filter-actions">
                <button type="submit" class="report-filter-btn report-filter-btn-primary">Filtrele</button>
                <a href="{{ route('portal.reports.category-sales') }}" class="report-filter-btn report-filter-btn-secondary">Temizle</a>
            </div>
        </form>
    </div>

    <div class="panel-card p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-[12px] table-fixed">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-4 w-[38%]">Kategori</th>
                        <th class="text-center py-2 pr-4 w-[18%]">Satis Adedi</th>
                        <th class="text-center py-2 pr-4 w-[18%]">Ciro (TL)</th>
                        <th class="text-center py-2 pr-4 w-[16%]">Ort. Sepet (TL)</th>
                        <th class="text-right py-2 w-[10%]">Detay</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" id="category-sales-table-body">
                    @forelse($rows as $row)
                        @php
                            $revenue = (float) ($row['revenue'] ?? 0);
                            $orders = (int) ($row['orders'] ?? 0);
                            $avg = $orders > 0 ? $revenue / $orders : 0;
                        @endphp
                        <tr>
                            <td class="py-2 pr-2.5 text-slate-700 font-semibold">{{ $row['label'] ?? '-' }}</td>
                            <td class="py-2 pr-2.5 text-center text-slate-700 tabular-nums">{{ number_format($orders, 0, ',', '.') }}</td>
                            <td class="py-2 pr-2.5 text-center text-slate-800 tabular-nums">{{ number_format($revenue, 2, ',', '.') }}</td>
                            <td class="py-2 pr-2.5 text-center text-slate-700 tabular-nums">{{ number_format($avg, 2, ',', '.') }}</td>
                            <td class="py-3 text-right">
                                <button type="button" class="btn btn-solid-accent px-2.5 py-1 text-[10px]" data-category-detail-toggle data-category-name="{{ $row['label'] ?? '-' }}" data-orders="{{ number_format($orders, 0, ',', '.') }}" data-revenue="{{ number_format($revenue, 2, ',', '.') }} TL" data-average="{{ number_format($avg, 2, ',', '.') }} TL">Detayi Gor</button>
                            </td>
                        </tr>
                        <tr class="rp-detail-row is-hidden" data-category-detail-row>
                            <td colspan="5">
                                <div class="rp-detail-panel">
                                    <div class="rp-detail-title">Kategori Satis Detayi</div>
                                    <div class="rp-detail-subtitle" data-detail-field="category-name">-</div>
                                    <div class="rp-detail-top">
                                        <div class="rp-detail-box"><span>Toplam Satis Adedi</span><strong data-detail-field="orders">-</strong></div>
                                        <div class="rp-detail-box"><span>Toplam Ciro</span><strong class="is-positive" data-detail-field="revenue">-</strong></div>
                                    </div>
                                    <div class="rp-detail-list">
                                        <div class="rp-detail-line"><span>Kategori</span><strong class="is-neutral" data-detail-field="category-name-line">-</strong></div>
                                        <div class="rp-detail-line"><span>Ortalama Sepet</span><strong class="is-neutral" data-detail-field="average">-</strong></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-4 text-center text-slate-500">Kayit bulunamadi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('category-sales-filters');
            const quickInput = document.getElementById('category-quick-range-input');
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
                tbody.querySelectorAll('[data-category-detail-row]').forEach((row) => row.classList.add('is-hidden'));
                tbody.querySelectorAll('[data-category-detail-toggle]').forEach((btn) => btn.textContent = 'Detayi Gor');
            };

            document.querySelectorAll('[data-category-detail-toggle]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('tr');
                    const detailRow = row?.nextElementSibling;
                    const tbody = row?.parentElement;
                    if (!detailRow || !detailRow.matches('[data-category-detail-row]') || !tbody) return;

                    const isOpen = !detailRow.classList.contains('is-hidden');
                    closeAll(tbody);
                    if (isOpen) return;

                    const setText = (key, value) => {
                        const el = detailRow.querySelector(`[data-detail-field="${key}"]`);
                        if (el) el.textContent = value || '-';
                    };
                    setText('category-name', btn.getAttribute('data-category-name'));
                    setText('category-name-line', btn.getAttribute('data-category-name'));
                    setText('orders', btn.getAttribute('data-orders'));
                    setText('revenue', btn.getAttribute('data-revenue'));
                    setText('average', btn.getAttribute('data-average'));

                    detailRow.classList.remove('is-hidden');
                    btn.textContent = 'Detayi Gizle';
                });
            });
        })();
    </script>
@endpush



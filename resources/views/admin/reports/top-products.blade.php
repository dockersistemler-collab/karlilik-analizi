@extends('layouts.admin')

@section('header')
    Cok Satan Urunler
@endsection

@section('content')
    @php
        $ownerUser = auth()->user();
        $canExport = $ownerUser ? app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, 'feature.exports') : false;
    @endphp

    <style>
        .tp-thumb-wrap {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: zoom-in;
            overflow: hidden;
        }
        .tp-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 12px;
        }
        .tp-thumb-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
        }
        .tp-image-popover {
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
        .tp-image-popover.is-open {
            display: block;
        }
        .tp-image-popover img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #f8fafc;
        }
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

            <div class="min-w-[260px] report-filter-field">
                <label class="block text-xs font-medium text-slate-500 mb-1">Baslangic</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="report-filter-control">
            </div>

            <div class="min-w-[150px] report-filter-field">
                <label class="block text-xs font-medium text-slate-500 mb-1">Bitis</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="report-filter-control">
            </div>

            <div class="min-w-[150px] report-filter-field">
                <label class="block text-xs font-medium text-slate-500 mb-1">Hizli Secim</label>
                <div class="report-filter-quick">
                    @foreach($quickRanges as $key => $label)
                        <button type="submit" name="quick_range" value="{{ $key }}" class="report-filter-chip text-xs {{ ($filters['quick_range'] ?? '') === $key ? 'is-active' : '' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="report-filter-actions">
                <button type="submit" class="report-filter-btn report-filter-btn-primary">Filtrele</button>
                <a href="{{ route('portal.reports.top-products') }}" class="report-filter-btn report-filter-btn-secondary">Temizle</a>
            </div>

            @if($reportExportsEnabled && $canExport)
                <details class="relative">
                    <summary class="report-filter-btn report-filter-btn-secondary list-none cursor-pointer">Disa Aktar</summary>
                    <div class="absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-lg shadow-lg p-2 z-10">
                        <a href="{{ route('portal.reports.top-products.export', request()->query()) }}" class="block px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-md">CSV</a>
                        <a href="{{ route('portal.reports.top-products.export', request()->query()) }}" class="block px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-md">Excel</a>
                    </div>
                </details>
            @endif
        </form>
    </div>

    <div class="panel-card p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-700">En Cok Satan Urunler</h3>
            <span class="text-xs text-slate-400">Ilk 100 urun listelenir.</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-4">Gorsel</th>
                        <th class="text-left py-2 pr-4">Stok Kodu</th>
                        <th class="text-left py-2 pr-4">Urun Adi</th>
                        <th class="text-right py-2 pr-4">Satis Adedi</th>
                        <th class="text-right py-2">Toplam Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        @php
                            $imageUrl = $row['image_url'] ?? null;
                            $imageFallback = 'https://placehold.co/96x96/e2e8f0/64748b?text=Urun';
                        @endphp
                        <tr>
                            <td class="py-3 pr-4">
                                @if($imageUrl)
                                    <span class="tp-thumb-wrap" tabindex="0" role="button" data-tp-preview-src="{{ $imageUrl }}" data-tp-preview-alt="{{ $row['name'] ?? 'Urun' }}">
                                        <img src="{{ $imageUrl }}" alt="{{ $row['name'] ?? 'Urun' }}" class="tp-thumb" loading="lazy" data-fallback-src="{{ $imageFallback }}" onerror="if(this.dataset.fallbackApplied==='1'){return;} this.dataset.fallbackApplied='1'; this.src=this.dataset.fallbackSrc;">
                                    </span>
                                @else
                                    <span class="tp-thumb-placeholder">-</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-slate-600">{{ $row['stock_code'] ?? '-' }}</td>
                            <td class="py-3 pr-4 text-slate-800 font-semibold">{{ $row['name'] }}</td>
                            <td class="py-3 pr-4 text-right text-slate-700">{{ number_format($row['quantity']) }}</td>
                            <td class="py-3 text-right text-slate-700">{{ number_format($row['total'], 2, ',', '.') }} TL</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-500">Kayit bulunamadi.</td>
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

    <div id="tp-image-popover" class="tp-image-popover" aria-hidden="true">
        <img id="tp-image-popover-img" src="" alt="">
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const imagePopover = document.getElementById('tp-image-popover');
    const imagePopoverImg = document.getElementById('tp-image-popover-img');
    const imageTriggers = Array.from(document.querySelectorAll('[data-tp-preview-src]'));
    if (!imagePopover || !imagePopoverImg || !imageTriggers.length) return;

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
        const src = thumbImg?.currentSrc || thumbImg?.getAttribute('src') || trigger.getAttribute('data-tp-preview-src') || '';
        if (!src) return;
        imagePopoverImg.src = src;
        imagePopoverImg.alt = trigger.getAttribute('data-tp-preview-alt') || 'Urun gorseli';
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
})();
</script>
@endpush

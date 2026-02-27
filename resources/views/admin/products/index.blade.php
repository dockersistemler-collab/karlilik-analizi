@extends('layouts.admin')



@section('header')

    {{ ($isInventoryView ?? false) ? 'Stok Takip' : 'Urunler' }}

@endsection



@section('content')

@php

    $ownerUser = auth()->user();
    $isReadOnlyInventory = (bool) ($isReadOnlyInventory ?? false);

    $canExport = $ownerUser ? app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, 'feature.exports') : false;
    $normalizeMarketplaceKey = static function (?string $marketplaceName): string {
        return \Illuminate\Support\Str::of(trim((string) $marketplaceName))->lower()->ascii()->value();
    };
    $inventoryMarketplaceLogoUrl = static function (?string $marketplaceName) use ($normalizeMarketplaceKey): ?string {
        $normalized = $normalizeMarketplaceKey($marketplaceName);
        $map = [
            'trendyol' => 'images/brands/trendyol.png',
            'hepsiburada' => 'images/brands/hepsiburada.png',
            'n11' => 'images/brands/n11.png',
            'amazon' => 'images/brands/amazon.png',
        ];
        foreach ($map as $key => $path) {
            if (str_contains($normalized, $key)) {
                return asset($path);
            }
        }
        return null;
    };
    $fallbackMarketplaceNames = collect(['Trendyol', 'Hepsiburada', 'N11', 'Amazon TR']);
    $isMarketplaceActive = static function (string $displayName, array $assignedKeys) use ($normalizeMarketplaceKey): bool {
        $displayKey = $normalizeMarketplaceKey($displayName);
        foreach ($assignedKeys as $assignedKey) {
            if ($assignedKey === $displayKey || str_contains($assignedKey, $displayKey) || str_contains($displayKey, $assignedKey)) {
                return true;
            }
        }
        return false;
    };

@endphp
@if($isInventoryView ?? false)
<style>
    .inventory-sticky-shell {
        position: sticky;
        top: 0;
        z-index: 70;
        background: #fff;
        padding-top: 8px;
    }
    .inventory-sticky-shell .inventory-top-card {
        margin-bottom: 12px;
    }
    .inventory-sticky-shell .inventory-search-card {
        margin-bottom: 0;
    }

    @media (max-width: 1024px) {
        .inventory-sticky-shell {
            top: 0;
        }
    }
</style>
@endif
<style>
    .inventory-toolbar {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        padding: 12px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }
    .inventory-search-wrap {
        display: flex;
        align-items: center;
        gap: 0;
        flex: 1 1 auto;
        min-width: 300px;
    }
    .inventory-search-form {
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid #dbe3ee;
        border-radius: 16px;
        background: #fff;
        padding: 6px 8px 6px 14px;
        width: min(640px, 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7), 0 8px 18px rgba(15, 23, 42, 0.05);
        transition: border-color .18s ease, box-shadow .2s ease;
    }
    .inventory-search-form:focus-within {
        border-color: #9ca3af;
        box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.22), 0 12px 24px rgba(15, 23, 42, 0.08);
    }
    .inventory-search-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #334155;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
    }
    .inventory-search-form input[type='text'] {
        border: 0;
        box-shadow: none;
        background: transparent;
        font-size: 15px;
    }
    .inventory-search-submit {
        border: 1px solid #3f3f46;
        border-radius: 12px;
        background: #3f3f46;
        color: #fff;
        font-weight: 700;
        min-height: 38px;
        padding: 8px 18px;
        transition: transform .18s ease, box-shadow .2s ease, filter .2s ease;
    }
    .inventory-search-submit:hover {
        transform: translateY(-1px);
        filter: brightness(1.03);
        box-shadow: 0 10px 18px rgba(63, 63, 70, 0.25);
    }
    .inventory-toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }
    .inventory-action-pill {
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        background: #ffffff;
        color: #334155;
        font-size: 13px;
        font-weight: 600;
        padding: 8px 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        min-height: 36px;
        transition: background-color .16s ease, border-color .16s ease, color .16s ease;
        box-shadow: none;
    }
    .inventory-action-pill:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        color: #1f2937;
    }
    .inventory-action-pill.is-primary {
        background: #111827;
        color: #fff;
        border-color: #111827;
        min-height: 34px;
        padding-top: 6px;
        padding-bottom: 6px;
    }
    .inventory-action-pill.is-primary:hover {
        background: #0f172a;
        border-color: #0f172a;
        color: #fff;
    }
    .inventory-import-form {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        background: #fff;
        padding: 6px 8px;
        min-height: 40px;
    }
    .inventory-import-form .inventory-action-pill {
        appearance: none;
        -webkit-appearance: none;
        border: 1px solid #dbe3ee !important;
        border-radius: 10px !important;
        background: #ffffff !important;
        color: #334155 !important;
        min-height: 34px !important;
        padding: 6px 12px !important;
        line-height: 1.1;
    }
    .inventory-import-form .inventory-action-pill:hover {
        border-color: #cbd5e1 !important;
        background: #f8fafc !important;
        color: #1f2937 !important;
    }
    .inventory-file-input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }
    .inventory-file-trigger {
        border: 1px solid #dbe3ee;
        border-radius: 8px;
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        font-weight: 600;
        min-height: 30px;
        padding: 6px 10px;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        white-space: nowrap;
        transition: background-color .16s ease, border-color .16s ease, color .16s ease;
    }
    .inventory-file-trigger:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        color: #1f2937;
    }
    .inventory-file-name {
        min-width: 130px;
        max-width: 180px;
        font-size: 12px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .list-thumb-wrap {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: zoom-in;
        overflow: hidden;
    }
    .list-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 0.75rem;
    }
    .list-thumb-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .list-image-popover {
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
    .list-image-popover.is-open {
        display: block;
    }
    .list-image-popover img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #f8fafc;
    }
    .inventory-market-cell {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        width: 82px;
        flex: 0 0 82px;
        transition: transform .2s ease, background-color .2s ease, border-color .2s ease, opacity .2s ease;
        cursor: pointer;
        position: relative;
        overflow: visible;
        border: 0;
        border-radius: 10px;
        padding: 4px 2px;
        background: transparent !important;
    }
    .inventory-market-list {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        margin: 0 auto;
        overflow: visible;
    }
    .inventory-market-logo-wrap {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: relative;
        border: 1px solid #dbe7f5;
        background: #ffffff;
        transition: border-color .2s ease, background-color .2s ease, transform .2s ease;
        box-sizing: border-box;
        overflow: visible;
    }
    .inventory-market-logo {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        object-fit: contain;
        background: transparent;
        border: 0;
        padding: 0;
        transition: transform .24s cubic-bezier(0.2, 0.7, 0.2, 1), filter .2s ease, opacity .2s ease, box-shadow .24s ease;
    }
    .inventory-market-logo-fallback {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: 0;
        background: transparent;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: transform .18s ease, opacity .2s ease, box-shadow .2s ease;
    }
    .inventory-market-cell.is-active .inventory-market-logo,
    .inventory-market-cell.is-active .inventory-market-logo-fallback {
        filter: brightness(1.08) saturate(1.18) contrast(1.04);
        opacity: 1;
        box-shadow: none;
    }
    .inventory-market-cell.is-active {
        background: transparent !important;
        border-color: transparent;
    }
    .inventory-market-cell.is-active .inventory-market-logo-wrap {
        border-color: #94a3b8;
        background: #ffffff;
    }
    .inventory-market-cell.is-inactive .inventory-market-logo,
    .inventory-market-cell.is-inactive .inventory-market-logo-fallback {
        filter: grayscale(100%) saturate(0.1);
        opacity: .3;
        box-shadow: none;
    }
    .inventory-market-cell.is-inactive .inventory-market-logo-wrap {
        border-color: #e5e7eb;
        background: #ffffff;
    }
    .inventory-market-sleep {
        position: absolute;
        right: -16px;
        top: -10px;
        z-index: 8;
        font-size: 9px;
        font-weight: 800;
        line-height: 1;
        letter-spacing: 0.03em;
        color: #f8fafc;
        background: #334155;
        border: 1px solid #1e293b;
        border-radius: 999px;
        padding: 2px 5px;
        box-shadow: 0 4px 10px rgba(15, 23, 42, 0.24);
        pointer-events: none;
        animation: inventorySleepBadge 2.8s ease-in-out infinite;
    }
    .inventory-market-name {
        font-size: 11px;
        line-height: 1.1;
        color: #000000 !important;
        font-weight: 500;
        letter-spacing: 0.01em;
        text-align: center;
        white-space: normal;
        overflow: visible;
        text-overflow: clip;
        width: 100%;
        max-width: 100%;
        padding: 0 1px;
        opacity: 1 !important;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        line-clamp: 2;
        min-height: 22px;
    }
    .inventory-market-hint {
        position: absolute;
        bottom: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%) translateY(4px);
        opacity: 0;
        pointer-events: none;
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #f8fafc;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.01em;
        white-space: nowrap;
        border-radius: 999px;
        padding: 6px 10px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.28);
        transition: opacity .16s ease, transform .16s ease;
        z-index: 4;
    }
    .inventory-market-hint::before {
        content: "\f0a9";
        font-family: "Font Awesome 6 Free";
        font-weight: 900;
        margin-right: 6px;
        font-size: 10px;
        opacity: .9;
    }
    .inventory-market-cell.is-inactive .inventory-market-name {
        color: #94a3b8 !important;
        opacity: .85 !important;
    }
    .inventory-market-cell:hover {
        transform: translateY(-2px);
    }
    .inventory-market-cell:hover .inventory-market-logo,
    .inventory-market-cell:hover .inventory-market-logo-fallback {
        transform: scale(1.08);
        box-shadow: none;
    }
    .inventory-market-cell:hover .inventory-market-logo-wrap {
        transform: translateY(-1px) scale(1.06);
        border-color: #94a3b8;
    }
    .inventory-market-cell.is-active:hover .inventory-market-logo,
    .inventory-market-cell.is-active:hover .inventory-market-logo-fallback {
        filter: brightness(1.14) saturate(1.24) contrast(1.06);
        opacity: 1;
    }
    .inventory-market-cell.is-inactive:hover .inventory-market-logo,
    .inventory-market-cell.is-inactive:hover .inventory-market-logo-fallback {
        filter: grayscale(100%) saturate(0.1);
        opacity: .45;
    }
    .inventory-market-cell:hover .inventory-market-name {
        color: #1f2937;
    }
    .inventory-market-cell:hover .inventory-market-hint {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    @keyframes inventorySleepBadge {
        0% {
            opacity: .62;
            transform: translateY(0);
        }
        50% {
            opacity: .98;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.34);
        }
        100% {
            opacity: .62;
            transform: translateY(0);
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .inventory-market-sleep {
            animation: none;
            opacity: .9;
        }
    }
    .inline-edit-box {
        display: flex;
        align-items: center;
        position: relative;
    }
    .inline-edit-box input[type='number'] {
        border: 1px solid #dbe3ee;
        border-radius: 10px;
        background: #ffffff;
        min-height: 36px;
        transition: border-color .15s ease, box-shadow .2s ease;
    }
    .inline-edit-box input[type='number']:focus {
        border-color: #94a3b8;
        box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.2);
    }
    .inline-update-btn {
        appearance: none;
        -webkit-appearance: none;
        display: none;
        align-items: center;
        justify-content: center;
        width: auto !important;
        max-width: 88%;
        flex: 0 0 auto;
        align-self: center;
        border: 1px solid #fdba74;
        border-radius: 12px;
        background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
        color: #9a3412 !important;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .01em;
        line-height: 1;
        min-height: 28px;
        padding: 6px 12px;
        box-shadow: 0 10px 24px rgba(249, 115, 22, 0.22);
        position: absolute;
        top: calc(100% + 6px);
        left: 50%;
        z-index: 5;
        transform: translateX(-50%) translateY(-2px);
        opacity: 0;
        pointer-events: none;
        cursor: pointer;
        transition: opacity .16s ease, transform .16s ease, border-color .16s ease, background-color .16s ease, box-shadow .16s ease, color .16s ease;
    }
    .inline-update-btn.is-visible {
        display: inline-flex;
        opacity: 1;
        pointer-events: auto;
        transform: translateX(-50%) translateY(0);
    }
    .inline-update-btn:hover {
        border-color: #f97316;
        background: linear-gradient(180deg, #ffedd5 0%, #fed7aa 100%);
        box-shadow: 0 12px 26px rgba(249, 115, 22, 0.28);
        color: #7c2d12 !important;
    }
    .inline-update-btn:active {
        transform: translateX(-50%) translateY(0);
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.2);
    }
    .inline-update-btn:disabled {
        opacity: .7;
        pointer-events: none;
    }
    .inline-toast-anchor {
        position: relative;
    }
    .inline-toast-stack {
        position: absolute;
        right: 0;
        top: 6px;
        z-index: 1600;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
        width: min(360px, calc(100vw - 32px));
        pointer-events: none;
    }
    .inline-toast {
        width: 100%;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid transparent;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.4;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.2);
        opacity: 0;
        transform: translateX(14px);
        transition: opacity .2s ease, transform .2s ease;
    }
    .inline-toast.is-visible {
        opacity: 1;
        transform: translateX(0);
    }
    .inline-toast--success {
        background: #ecfdf5;
        border-color: #6ee7b7;
        color: #065f46;
    }
    .inline-toast--error {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #991b1b;
    }
    @media (max-width: 1024px) {
        .inventory-toolbar-actions {
            justify-content: flex-start;
        }
    }
</style>
@if($isInventoryView ?? false)
<div class="inventory-sticky-shell">
<div class="inline-toast-anchor">
    <div id="inline-toast-stack" class="inline-toast-stack" aria-live="polite"></div>
</div>
<div class="panel-card p-3 mb-4 inventory-top-card">
        @include('admin.products.partials.catalog-tabs', [
            'isInventoryView' => ($isInventoryView ?? false),
            'isReadOnlyInventory' => $isReadOnlyInventory,
            'inventoryMarketplaces' => ($inventoryMarketplaces ?? collect()),
            'selectedMarketplaceId' => ($selectedMarketplaceId ?? 0),
        ])
</div>
<div class="panel-card p-4 mb-4 inventory-search-card">
@else
<div class="mb-4 inline-toast-anchor">
    <div id="inline-toast-stack" class="inline-toast-stack" aria-live="polite"></div>
    @include('admin.products.partials.catalog-tabs', [
        'isInventoryView' => ($isInventoryView ?? false),
        'isReadOnlyInventory' => $isReadOnlyInventory,
        'inventoryMarketplaces' => ($inventoryMarketplaces ?? collect()),
        'selectedMarketplaceId' => ($selectedMarketplaceId ?? 0),
    ])
</div>
<div class="panel-card p-4 mb-4">
@endif

    <div class="inventory-toolbar">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">

            <div class="inventory-search-wrap w-full lg:w-auto">

                <form method="GET" id="product-search-form" class="inventory-search-form">

                    <span class="inventory-search-icon"><i class="fa-solid fa-magnifying-glass text-sm"></i></span>

                    <input type="text" id="product-search-input" name="search" placeholder="Barkod, SKU, &Uuml;r&uuml;n ad&#305;, Marka..."

                           class="border-0 focus:ring-0 text-sm w-full"

                           value="{{ request('search') }}">

                    @foreach(request()->except('search', 'page') as $key => $value)

                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">

                    @endforeach

                    <button type="submit" class="inventory-search-submit">Ara</button>

                </form>

            </div>

            <div class="inventory-toolbar-actions">
                @unless($isReadOnlyInventory)

                @if($canExport)

                <a href="{{ route('portal.products.template') }}" class="inventory-action-pill">

                    Excel &#350;ablonu

                </a>

                <a href="{{ route('portal.products.export') }}" class="inventory-action-pill">

                    Excel D&#305;&#351;a Aktar

                </a>

                @endif

                <form method="POST" action="{{ route('portal.products.import') }}" enctype="multipart/form-data" class="inventory-import-form">

                    @csrf

                    <label class="inventory-file-trigger">
                        Dosya Sec
                        <input type="file" name="file" accept=".xlsx" class="inventory-file-input" data-inventory-file-input>
                    </label>

                    <span class="inventory-file-name" data-inventory-file-name>Dosya secilmedi</span>

                    <button type="submit" class="inventory-action-pill">

                        Excel &#304;&ccedil;eri Aktar

                    </button>

                </form>

                <a href="{{ route('portal.products.create') }}" class="inventory-action-pill is-primary">

                    <i class="fas fa-plus mr-2"></i> Yeni &Uuml;r&uuml;n

                </a>
                @else
                <span class="inventory-action-pill">Salt okunur stok g&ouml;r&uuml;n&uuml;m&uuml;</span>
                @endunless

            </div>

        </div>

    </div>

</div>
@if($isInventoryView ?? false)
</div>
@endif



<div id="products-results">

    <div id="products-table-wrap" class="panel-card table-shell overflow-hidden {{ ($isInventoryView ?? false) ? 'rounded-t-none border-t-0' : '' }}">
        
        <table class="min-w-full border-separate border-spacing-y-2">

            <thead>

            @php

                $currentSort = request('sort');

                $currentDir = request('dir', 'asc');

                $nextDir = function ($key) use ($currentSort, $currentDir) {

                    return $currentSort === $key && $currentDir === 'asc' ? 'desc' : 'asc';

                };

                $sortLink = function ($key) use ($nextDir) {

                    return request()->fullUrlWithQuery([

                        'sort' => $key,

                        'dir' => $nextDir($key),

                        'page' => null,

                    ]);

                };

                $sortIcon = function ($key) use ($currentSort, $currentDir) {

                    if ($currentSort !== $key) {

                        return 'fa-sort';

                    }

                    return $currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';

                };

            @endphp

            <tr>

                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">
                    @if(!$isReadOnlyInventory)
                        <input type="checkbox" id="inventory-select-all" class="rounded border-slate-300 text-[#ff4439] focus:ring-[#ff4439]">
                    @endif
                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">G&ouml;rsel</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('sku') }}" class="inline-flex items-center gap-2">

                        SKU

                        <i class="fa-solid {{ $sortIcon('sku') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('name') }}" class="inline-flex items-center gap-2">

                        &Uuml;r&uuml;n

                        <i class="fa-solid {{ $sortIcon('name') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('brand') }}" class="inline-flex items-center gap-2">

                        Marka

                        <i class="fa-solid {{ $sortIcon('brand') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('cost') }}" class="inline-flex items-center gap-2">

                        Maliyet

                        <i class="fa-solid {{ $sortIcon('cost') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('price') }}" class="inline-flex items-center gap-2">

                        Fiyat

                        <i class="fa-solid {{ $sortIcon('price') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('stock') }}" class="inline-flex items-center gap-2">

                        Stok

                        <i class="fa-solid {{ $sortIcon('stock') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('marketplace') }}" class="inline-flex w-full items-center justify-center gap-2">

                        Pazaryeri

                        <i class="fa-solid {{ $sortIcon('marketplace') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('status') }}" class="inline-flex items-center gap-2">

                        Durum

                        <i class="fa-solid {{ $sortIcon('status') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">&#304;&#351;lem</th>

            </tr>

        </thead>

        <tbody class="divide-y divide-transparent">

            @forelse($products as $product)

            <tr class="bg-white shadow-sm">
                <td class="px-4 py-4 whitespace-nowrap">
                    @if(!$isReadOnlyInventory)
                        <input type="checkbox" class="inventory-row-select rounded border-slate-300 text-[#ff4439] focus:ring-[#ff4439]" value="{{ $product->id }}" data-product-id="{{ $product->id }}">
                    @endif
                </td>

                <td class="px-6 py-4 whitespace-nowrap text-center">

                    @if($product->display_image_url)

                        <span class="list-thumb-wrap"
                              tabindex="0"
                              role="button"
                              data-list-preview-src="{{ $product->display_image_url }}"
                              data-list-preview-alt="{{ $product->name }}">
                            <img src="{{ $product->display_image_url }}" alt="{{ $product->name }}" class="list-thumb">
                        </span>

                    @else

                        <div class="list-thumb-placeholder">

                            <i class="fas fa-image text-slate-400"></i>

                        </div>

                    @endif

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <code class="bg-slate-100 px-2 py-1 rounded text-xs">{{ $product->sku }}</code>

                </td>

                <td class="px-6 py-4">

                    <div class="text-sm font-semibold text-slate-900">{{ $product->name }}</div>

                    @if($product->barcode)

                        <div class="text-xs text-slate-500">Barkod: {{ $product->barcode }}</div>

                    @endif

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $product->brand ?? '-' }}</td>

                <td class="px-6 py-4 whitespace-nowrap">
                    @if($isReadOnlyInventory)
                        <span class="text-sm text-slate-700">{{ number_format((float) $product->cost_price, 2, ',', '.') }} {{ $product->currency }}</span>
                    @else
                        <div class="flex items-center gap-2">
                            <div class="inline-edit-box" data-inline-edit-box="{{ $product->id }}">
                                <input type="number" step="0.01" min="0" class="w-24 text-sm" value="{{ $product->cost_price }}" data-product-cost="{{ $product->id }}" data-inline-edit-input="1">
                                <button type="button" class="inline-update-btn" data-inline-update-product="{{ $product->id }}" data-inline-update-button="1">G&uuml;ncelle</button>
                            </div>
                            <span class="text-xs text-slate-500">{{ $product->currency }}</span>
                        </div>
                    @endif

                </td>

                <td class="px-6 py-4 whitespace-nowrap">
                    @if($isReadOnlyInventory)
                        <span class="text-sm text-slate-700">{{ number_format((float) $product->price, 2, ',', '.') }} {{ $product->currency }}</span>
                    @else
                        <div class="flex items-center gap-2">
                            <div class="inline-edit-box" data-inline-edit-box="{{ $product->id }}">
                                <input type="number" step="0.01" min="0" class="w-24 text-sm" value="{{ $product->price }}" data-product-price="{{ $product->id }}" data-inline-edit-input="1">
                                <button type="button" class="inline-update-btn" data-inline-update-product="{{ $product->id }}" data-inline-update-button="1">G&uuml;ncelle</button>
                            </div>
                            <span class="text-xs text-slate-500">{{ $product->currency }}</span>
                        </div>
                    @endif

                </td>

                <td class="px-6 py-4 whitespace-nowrap">
                    @if($isReadOnlyInventory)
                        <span class="text-sm text-slate-700">{{ $product->stock_quantity }} <span class="text-xs text-slate-500">adet</span></span>
                    @else
                        <div class="flex items-center gap-2">
                            <div class="inline-edit-box" data-inline-edit-box="{{ $product->id }}">
                                <input type="number" min="0" class="w-24 text-sm" value="{{ $product->stock_quantity }}" data-product-stock="{{ $product->id }}" data-inline-edit-input="1">
                                <button type="button" class="inline-update-btn" data-inline-update-product="{{ $product->id }}" data-inline-update-button="1">G&uuml;ncelle</button>
                            </div>
                            <span class="text-xs text-slate-500">adet</span>
                        </div>
                    @endif

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    @php

                        $assignedMarketplaceNames = $product->marketplaceProducts
                            ->pluck('marketplace')
                            ->filter()
                            ->unique('id')
                            ->pluck('name')
                            ->filter()
                            ->reject(fn ($name) => str_contains($normalizeMarketplaceKey((string) $name), 'cicek'))
                            ->values();
                        $assignedMarketplaceKeys = $assignedMarketplaceNames
                            ->map(fn ($name) => $normalizeMarketplaceKey((string) $name))
                            ->filter()
                            ->values()
                            ->all();
                        $displayMarketplaceNames = collect($inventoryMarketplaces ?? [])
                            ->pluck('name')
                            ->filter()
                            ->reject(fn ($name) => str_contains($normalizeMarketplaceKey((string) $name), 'cicek'))
                            ->values();
                        if ($displayMarketplaceNames->isEmpty()) {
                            $displayMarketplaceNames = $fallbackMarketplaceNames;
                        }
                        $displayMarketplaceNames = $displayMarketplaceNames
                            ->sortByDesc(fn ($name) => $isMarketplaceActive((string) $name, $assignedMarketplaceKeys))
                            ->values();

                    @endphp

                    <div class="inventory-market-list">
                        @forelse($displayMarketplaceNames as $marketplaceName)
                            @php
                                $marketLogoUrl = $inventoryMarketplaceLogoUrl($marketplaceName);
                                $marketIsActive = $isMarketplaceActive((string) $marketplaceName, $assignedMarketplaceKeys);
                            @endphp
                            <span class="inventory-market-cell {{ $marketIsActive ? 'is-active' : 'is-inactive' }}">
                                <span class="inventory-market-hint">Pazaryerinde açılır</span>
                                <span class="inventory-market-logo-wrap">
                                    @unless($marketIsActive)
                                        <span class="inventory-market-sleep">ZZZ</span>
                                    @endunless
                                    @if($marketLogoUrl)
                                        <img src="{{ $marketLogoUrl }}" alt="{{ $marketplaceName }}" class="inventory-market-logo">
                                    @else
                                        <span class="inventory-market-logo-fallback">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $marketplaceName, 0, 2)) }}</span>
                                    @endif
                                </span>
                                <span class="inventory-market-name">{{ $marketplaceName }}</span>
                            </span>
                        @empty
                            <span class="text-xs text-slate-400">-</span>
                        @endforelse
                    </div>

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <span class="panel-pill text-xs {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-600' }}">

                        {{ $product->is_active ? 'Aktif' : 'Pasif' }}

                    </span>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    @if($isReadOnlyInventory)
                        <span class="text-slate-400">-</span>
                    @else
                        <a href="{{ route('portal.products.show', $product) }}"
                           class="text-blue-600 hover:text-blue-900 mr-3"
                           data-product-edit-popup="1"
                           data-product-name="{{ $product->name }} Detay">
                            <i class="fas fa-eye"></i>
                        </a>

                        <a href="{{ route('portal.products.edit', $product) }}"
                           class="text-amber-600 hover:text-amber-800 mr-3"
                           data-product-edit-popup="1"
                           data-product-name="{{ $product->name }}">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($isInventoryView ?? false)
                        <button type="button"
                                class="text-violet-600 hover:text-violet-800 mr-3"
                                data-toggle-marketplace-row="{{ $product->id }}">
                            <i class="fas fa-store"></i>
                        </button>
                        @endif

                        <form action="{{ route('portal.products.destroy', $product) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-600 hover:text-rose-800" onclick="return confirm('Silmek istedi&#287;inize emin misiniz?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    @endif

                </td>

            </tr>

            @if(($isInventoryView ?? false) && !$isReadOnlyInventory)
            <tr class="hidden bg-slate-50" data-marketplace-row="{{ $product->id }}">
                <td colspan="{{ ($isInventoryView ?? false) ? 11 : 10 }}" class="px-6 py-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="text-sm font-semibold text-slate-800 mb-3">Pazaryerine Ac</div>
                        <form method="POST"
                              action="{{ route('portal.marketplace-products.assign') }}"
                              class="flex flex-col md:flex-row md:items-end gap-3"
                              data-marketplace-assign-form="{{ $product->id }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="price" value="{{ $product->price }}" data-hidden-price="{{ $product->id }}">
                            <input type="hidden" name="stock_quantity" value="{{ $product->stock_quantity }}" data-hidden-stock="{{ $product->id }}">

                            <div class="w-full md:w-72">
                                <label class="block text-xs text-slate-600 mb-1">Pazaryeri</label>
                                <select name="marketplace_id" class="w-full" required>
                                    <option value="">Seciniz</option>
                                    @foreach(($inventoryMarketplaces ?? collect()) as $inventoryMarketplace)
                                        <option value="{{ $inventoryMarketplace->id }}">{{ $inventoryMarketplace->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-solid-accent">
                                Gonder
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endif

            @empty

            <tr>

                <td colspan="11" class="px-6 py-4 text-center text-slate-500">Hen&uuml;z &uuml;r&uuml;n bulunmuyor</td>

            </tr>

            @endforelse

        </tbody>

        </table>

    </div>



    <div class="mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <form method="GET" class="flex items-center gap-2 text-sm">

            <label for="per-page" class="text-slate-500">Sayfa ba&#351;&#305;na</label>

            <select id="per-page" name="per_page" class="w-24" onchange="this.form.submit()">

                @php
                    $perPageOptions = ($isInventoryView ?? false) ? [25, 50, 100] : [10, 20, 50, 100];
                    $defaultPerPage = ($isInventoryView ?? false) ? 25 : 20;
                @endphp
                @foreach($perPageOptions as $size)

                    <option value="{{ $size }}" @selected((int) request('per_page', $defaultPerPage) === $size)>{{ $size }}</option>

                @endforeach

            </select>

            @foreach(request()->except('per_page', 'page') as $key => $value)

                <input type="hidden" name="{{ $key }}" value="{{ $value }}">

            @endforeach

        </form>

        {{ $products->links() }}

    </div>

</div>

<div id="list-image-popover" class="list-image-popover" aria-hidden="true">
    <img id="list-image-popover-img" src="" alt="">
</div>

<div id="product-edit-modal" class="fixed inset-0 z-[150] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/55" data-product-edit-close></div>
    <div class="relative mx-auto mt-6 w-[96%] max-w-6xl h-[90vh] rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h3 class="text-sm font-semibold text-slate-800">&Uuml;r&uuml;n D&uuml;zenle: <span id="product-edit-modal-title" class="text-slate-600">-</span></h3>
            <button type="button" class="btn btn-outline text-xs" data-product-edit-close>Kapat</button>
        </div>
        <iframe id="product-edit-modal-frame" class="w-full h-[calc(90vh-56px)] border-0" src="about:blank" loading="lazy"></iframe>
    </div>
</div>

@endsection



@push('scripts')

<script>

    const searchForm = document.getElementById('product-search-form');

    const searchInput = document.getElementById('product-search-input');

    const resultsWrap = document.getElementById('products-results');
    const productEditModal = document.getElementById('product-edit-modal');
    const productEditModalFrame = document.getElementById('product-edit-modal-frame');
    const productEditModalTitle = document.getElementById('product-edit-modal-title');

    let searchTimer;

    let searchAbortController;
    const inventoryFlashMessage = @json(session('error') ?? (session('success') ?? ($errors->any() ? $errors->first() : null)));
    const inventoryFlashType = @json(session('error') || $errors->any() ? 'error' : (session('success') ? 'success' : null));
    const isReadOnlyInventory = @json($isReadOnlyInventory);

    const inlineLastSavedState = {};

    function closeProductEditModal() {
        if (!productEditModal || !productEditModalFrame) return;
        productEditModal.classList.add('hidden');
        productEditModal.setAttribute('aria-hidden', 'true');
        productEditModalFrame.src = 'about:blank';
        document.body.style.overflow = '';
    }

    function openProductEditModal(url, titleText) {
        if (!productEditModal || !productEditModalFrame) return;
        if (productEditModalTitle) {
            productEditModalTitle.textContent = titleText || '-';
        }
        const iframeUrl = new URL(url, window.location.origin);
        iframeUrl.searchParams.set('embed', '1');
        productEditModalFrame.src = iframeUrl.toString();
        productEditModal.classList.remove('hidden');
        productEditModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function ensureInlineToastStack() {
        let stack = document.getElementById('inline-toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.id = 'inline-toast-stack';
            stack.className = 'inline-toast-stack';
            document.body.appendChild(stack);
        }
        return stack;
    }

    function showInlineToast(message, type = 'success') {
        if (!message) return;
        const stack = ensureInlineToastStack();
        const toast = document.createElement('div');
        toast.className = `inline-toast inline-toast--${type === 'error' ? 'error' : 'success'}`;
        toast.textContent = message;
        stack.appendChild(toast);
        window.requestAnimationFrame(() => {
            toast.classList.add('is-visible');
        });
        window.setTimeout(() => {
            toast.classList.remove('is-visible');
            window.setTimeout(() => toast.remove(), 220);
        }, 2600);
    }

    function resolveInlineFieldLabel(inputEl) {
        if (!inputEl) return 'Alan';
        if (inputEl.hasAttribute('data-product-cost')) return 'Maliyet fiyatı';
        if (inputEl.hasAttribute('data-product-price')) return 'Satış fiyatı';
        if (inputEl.hasAttribute('data-product-stock')) return 'Stok';
        return 'Alan';
    }

    async function submitInlineUpdate(productId, triggerButton = null, preferredFieldLabel = null) {
        const costInput = document.querySelector(`[data-product-cost="${productId}"]`);
        const priceInput = document.querySelector(`[data-product-price="${productId}"]`);
        const stockInput = document.querySelector(`[data-product-stock="${productId}"]`);

        if (!priceInput || !stockInput) {
            return;
        }

        const costValue = costInput ? costInput.value : '';
        const priceValue = priceInput.value;
        const stockValue = stockInput.value;
        const stateKey = `${costValue}|${priceValue}|${stockValue}`;
        if (inlineLastSavedState[productId] === stateKey) {
            return;
        }

        const previousParts = String(inlineLastSavedState[productId] ?? '').split('|');
        const changedFields = [];
        if ((previousParts[0] ?? '') !== costValue) changedFields.push('Maliyet fiyatı');
        if ((previousParts[1] ?? '') !== priceValue) changedFields.push('Satış fiyatı');
        if ((previousParts[2] ?? '') !== stockValue) changedFields.push('Stok');

        const originalButtonText = triggerButton ? triggerButton.textContent : null;
        if (triggerButton) {
            triggerButton.disabled = true;
            triggerButton.textContent = 'Kaydediliyor';
        }

        const response = await fetch(`{{ url('/products') }}/${productId}/quick-update`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cost_price: costValue,
                price: priceValue,
                stock_quantity: stockValue,
            }),
        });

        if (triggerButton) {
            triggerButton.disabled = false;
            triggerButton.textContent = originalButtonText || 'Guncelle';
        }

        if (!response.ok) {
            showInlineToast('Kaydedilemedi. Lütfen değerleri kontrol edin.', 'error');
            return;
        }

        inlineLastSavedState[productId] = stateKey;
        const label = changedFields.length > 0
            ? changedFields.join(', ')
            : (preferredFieldLabel || 'Alan');
        showInlineToast(`${label} güncellendi.`, 'success');
    }


    function bindQuickSave() {

        const costInputs = Array.from(document.querySelectorAll('[data-product-cost]'));
        const priceInputs = Array.from(document.querySelectorAll('[data-product-price]'));
        const stockInputs = Array.from(document.querySelectorAll('[data-product-stock]'));
        const inlineEditBoxes = Array.from(document.querySelectorAll('[data-inline-edit-box]'));

        const hasInputValue = (inputEl) => String(inputEl?.value ?? '').trim().length > 0;
        const refreshInlineButtonState = (boxEl) => {
            const inputEl = boxEl?.querySelector('[data-inline-edit-input]');
            const buttonEl = boxEl?.querySelector('[data-inline-update-button]');
            if (!inputEl || !buttonEl) {
                return;
            }

            const isFocused = boxEl.classList.contains('is-focused');
            const shouldShow = isFocused && hasInputValue(inputEl);
            buttonEl.classList.toggle('is-visible', shouldShow);
        };

        inlineEditBoxes.forEach((boxEl) => {
            const inputEl = boxEl.querySelector('[data-inline-edit-input]');
            const buttonEl = boxEl.querySelector('[data-inline-update-button]');
            const productId = buttonEl?.getAttribute('data-inline-update-product')
                || inputEl?.getAttribute('data-product-cost')
                || inputEl?.getAttribute('data-product-price')
                || inputEl?.getAttribute('data-product-stock');

            if (!inputEl || !buttonEl || !productId) {
                return;
            }

            const submitFromInline = async () => {
                await submitInlineUpdate(productId, buttonEl, resolveInlineFieldLabel(inputEl));
                refreshInlineButtonState(boxEl);
            };

            buttonEl.addEventListener('click', async () => {
                await submitFromInline();
            });

            boxEl.addEventListener('focusin', () => {
                boxEl.classList.add('is-focused');
                refreshInlineButtonState(boxEl);
            });

            boxEl.addEventListener('focusout', () => {
                window.setTimeout(() => {
                    if (!boxEl.contains(document.activeElement)) {
                        boxEl.classList.remove('is-focused');
                    }
                    refreshInlineButtonState(boxEl);
                }, 0);
            });

            inputEl.addEventListener('input', () => refreshInlineButtonState(boxEl));
            inputEl.addEventListener('change', () => refreshInlineButtonState(boxEl));
            inputEl.addEventListener('keydown', async (event) => {
                if (event.key !== 'Enter') {
                    return;
                }
                event.preventDefault();
                await submitFromInline();
            });

            refreshInlineButtonState(boxEl);
        });

        const seenProductIds = new Set();
        [...costInputs, ...priceInputs, ...stockInputs].forEach((inputEl) => {
            const productId = inputEl.getAttribute('data-product-cost') || inputEl.getAttribute('data-product-price') || inputEl.getAttribute('data-product-stock');
            if (!productId || seenProductIds.has(productId)) {
                return;
            }

            seenProductIds.add(productId);
            const costInput = document.querySelector(`[data-product-cost="${productId}"]`);
            const priceInput = document.querySelector(`[data-product-price="${productId}"]`);
            const stockInput = document.querySelector(`[data-product-stock="${productId}"]`);
            if (priceInput && stockInput) {
                inlineLastSavedState[productId] = `${costInput ? costInput.value : ''}|${priceInput.value}|${stockInput.value}`;
            }
        });

    }
    function bindInventoryMarketplaceActions() {

        const toggleButtons = document.querySelectorAll('[data-toggle-marketplace-row]');

        toggleButtons.forEach((btn) => {

            btn.addEventListener('click', () => {

                const productId = btn.getAttribute('data-toggle-marketplace-row');

                const row = document.querySelector(`[data-marketplace-row="${productId}"]`);

                if (!row) return;

                row.classList.toggle('hidden');

            });

        });

        const assignForms = document.querySelectorAll('[data-marketplace-assign-form]');

        assignForms.forEach((form) => {

            form.addEventListener('submit', () => {

                const productId = form.getAttribute('data-marketplace-assign-form');

                const currentPrice = document.querySelector(`[data-product-price="${productId}"]`);

                const currentStock = document.querySelector(`[data-product-stock="${productId}"]`);

                const hiddenPrice = form.querySelector(`[data-hidden-price="${productId}"]`);

                const hiddenStock = form.querySelector(`[data-hidden-stock="${productId}"]`);

                if (currentPrice && hiddenPrice) {

                    hiddenPrice.value = currentPrice.value;

                }

                if (currentStock && hiddenStock) {

                    hiddenStock.value = currentStock.value;

                }

            });

        });

    }



    function bindInventorySelection() {

        const selectAll = document.getElementById('inventory-select-all');

        const rowCheckboxes = Array.from(document.querySelectorAll('.inventory-row-select'));
        const selectedSyncForm = document.querySelector('[data-inventory-sync-selected-form]');
        const selectedSyncCsvInput = selectedSyncForm ? selectedSyncForm.querySelector('input[name="selected_product_ids_csv"]') : null;
        const selectedSyncSubmit = selectedSyncForm ? selectedSyncForm.querySelector('[data-inventory-sync-selected-submit]') : null;
        const selectedSyncMarketplaceInput = selectedSyncForm ? selectedSyncForm.querySelector('input[name="marketplace_id"]') : null;
        const openForm = document.querySelector('[data-inventory-open-form]');
        const openToggle = openForm ? openForm.querySelector('[data-inventory-open-toggle]') : null;
        const openPanel = openForm ? openForm.querySelector('[data-inventory-open-panel]') : null;
        const openMarketplaceSelect = openForm ? openForm.querySelector('select[name="marketplace_id"]') : null;
        const openSelectedCsvInput = openForm ? openForm.querySelector('input[name="selected_product_ids_csv"]') : null;
        const openSubmit = openForm ? openForm.querySelector('[data-inventory-open-submit]') : null;
        try {
            window.sessionStorage.removeItem('inventory_selected_product_ids_v1');
        } catch (error) {
            // no-op
        }

        const updateOpenSubmitState = (selectedCount) => {
            if (!openSubmit) {
                return;
            }

            const hasMarketplace = !!(openMarketplaceSelect && openMarketplaceSelect.value);
            openSubmit.disabled = !(selectedCount > 0 && hasMarketplace);
        };

        const updateSelectedSyncSubmitState = (selectedCount) => {
            if (!selectedSyncSubmit) {
                return;
            }

            selectedSyncSubmit.disabled = selectedCount === 0;
        };

        const syncBulkFormState = () => {
            const selectedIds = rowCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value);
            const selectedCount = selectedIds.length;

            if (selectedSyncCsvInput) {
                selectedSyncCsvInput.value = selectedIds.join(',');
            }
            if (selectedSyncMarketplaceInput) {
                const marketplaceFromQuery = new URLSearchParams(window.location.search).get('marketplace_id') || '';
                selectedSyncMarketplaceInput.value = (marketplaceFromQuery && marketplaceFromQuery !== '0') ? marketplaceFromQuery : '';
            }
            if (openSelectedCsvInput) {
                openSelectedCsvInput.value = selectedIds.join(',');
            }
            updateSelectedSyncSubmitState(selectedCount);

            updateOpenSubmitState(selectedCount);
            if (selectedCount === 0 && openPanel) {
                openPanel.classList.add('hidden');
            }
        };

        if (openToggle && openPanel) {
            openToggle.addEventListener('click', () => {
                openPanel.classList.toggle('hidden');
            });
        }

        if (openMarketplaceSelect) {
            openMarketplaceSelect.addEventListener('change', () => {
                const selectedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
                updateOpenSubmitState(selectedCount);
            });
        }

        if (!selectAll || rowCheckboxes.length === 0) {
            syncBulkFormState();
            return;
        }

        const syncSelectAllState = () => {
            const checkedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
            selectAll.checked = checkedCount > 0 && checkedCount === rowCheckboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
            syncBulkFormState();
        };

        selectAll.addEventListener('change', () => {
            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            syncSelectAllState();
        });

        rowCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', syncSelectAllState);
        });

        if (selectedSyncForm) {
            selectedSyncForm.addEventListener('submit', () => {
                syncBulkFormState();
            });
        }

        if (openForm) {
            openForm.addEventListener('submit', () => {
                syncBulkFormState();
                if (openPanel) {
                    openPanel.classList.add('hidden');
                }
            });
        }

        syncSelectAllState();
    }

    function bindListImagePreview() {
        const popover = document.getElementById('list-image-popover');
        const popoverImg = document.getElementById('list-image-popover-img');
        const triggers = Array.from(document.querySelectorAll('[data-list-preview-src]'));

        if (!popover || !popoverImg) {
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

        triggers.forEach((trigger) => {
            const hidePopover = () => {
                popover.classList.remove('is-open');
                popoverImg.removeAttribute('src');
            };

            const openPopover = (event) => {
                const src = trigger.getAttribute('data-list-preview-src');
                if (!src) {
                    return;
                }
                popoverImg.src = src;
                popoverImg.alt = trigger.getAttribute('data-list-preview-alt') || 'Urun gorseli';
                popover.classList.add('is-open');
                placePopover(event);
            };

            trigger.addEventListener('mouseenter', (event) => {
                openPopover(event);
            });

            trigger.addEventListener('mousemove', placePopover);

            trigger.addEventListener('mouseleave', () => {
                hidePopover();
            });

            trigger.addEventListener('focus', () => {
                const rect = trigger.getBoundingClientRect();
                openPopover({
                    clientX: rect.left + (rect.width / 2),
                    clientY: rect.top + (rect.height / 2),
                });
            });

            trigger.addEventListener('blur', () => {
                hidePopover();
            });

            trigger.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    hidePopover();
                }
            });
        });
    }

    function bindInventoryImportForm() {
        const fileInput = document.querySelector('[data-inventory-file-input]');
        const fileName = document.querySelector('[data-inventory-file-name]');
        if (!fileInput || !fileName) {
            return;
        }

        const renderName = () => {
            const selected = fileInput.files && fileInput.files.length ? fileInput.files[0].name : '';
            fileName.textContent = selected || 'Dosya secilmedi';
        };

        fileInput.addEventListener('change', renderName);
        renderName();
    }


    async function fetchResults(url) {

        if (!resultsWrap) return;

        if (searchAbortController) {

            searchAbortController.abort();

        }

        searchAbortController = new AbortController();



        try {

            const response = await fetch(url, {

                headers: {

                    'X-Requested-With': 'XMLHttpRequest',

                },

                signal: searchAbortController.signal,

            });



            if (!response.ok) {

                return;

            }



            const html = await response.text();

            const parser = new DOMParser();

            const doc = parser.parseFromString(html, 'text/html');

            const nextResults = doc.getElementById('products-results');



            if (nextResults) {

                resultsWrap.innerHTML = nextResults.innerHTML;

                if (!isReadOnlyInventory) {
                    bindQuickSave();
                    bindInventoryMarketplaceActions();
                    bindInventorySelection();
                }
                bindListImagePreview();

                if (searchInput) {

                    searchInput.focus();

                    searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);

                }

                window.history.replaceState({}, '', url);

            }

        } catch (error) {

            if (error.name !== 'AbortError') {

                console.error(error);

            }

        }

    }



    async function runSearch(value) {

        if (!searchForm) return;



        const url = new URL(window.location.href);

        if (value) {

            url.searchParams.set('search', value);

        } else {

            url.searchParams.delete('search');

        }

        url.searchParams.delete('page');

        await fetchResults(url.toString());

    }



    if (!isReadOnlyInventory) {
        bindQuickSave();
        bindInventoryMarketplaceActions();
        bindInventorySelection();
    }
    bindListImagePreview();
    bindInventoryImportForm();
    if (inventoryFlashMessage) {
        showInlineToast(inventoryFlashMessage, inventoryFlashType === 'error' ? 'error' : 'success');
    }



    if (resultsWrap) {

        resultsWrap.addEventListener('click', (event) => {
            const editTrigger = event.target.closest('[data-product-edit-popup]');
            if (!editTrigger || !resultsWrap.contains(editTrigger)) return;
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            const href = editTrigger.getAttribute('href');
            if (!href) return;
            event.preventDefault();
            openProductEditModal(href, editTrigger.getAttribute('data-product-name'));
        });

        resultsWrap.addEventListener('click', (event) => {

            const link = event.target.closest('a');

            if (!link || !resultsWrap.contains(link)) return;

            const href = link.getAttribute('href');

            if (!href || href.startsWith('#')) return;



            const isPagination = href.includes('page=');

            if (!isPagination) return;



            event.preventDefault();

            fetchResults(href);

        });



        resultsWrap.addEventListener('change', (event) => {

            const target = event.target;

            if (!(target instanceof HTMLSelectElement)) return;

            if (target.id !== 'per-page') return;



            const url = new URL(window.location.href);

            url.searchParams.set('per_page', target.value);

            url.searchParams.delete('page');

            fetchResults(url.toString());

        });

    }



    if (searchForm && searchInput) {

        searchInput.addEventListener('input', () => {

            window.clearTimeout(searchTimer);

            searchTimer = window.setTimeout(() => {

                runSearch(searchInput.value.trim());

            }, 350);

        });

    }

    document.querySelectorAll('[data-product-edit-close]').forEach((btn) => {
        btn.addEventListener('click', closeProductEditModal);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeProductEditModal();
        }
    });

</script>

@endpush

















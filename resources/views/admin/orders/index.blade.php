@extends('layouts.admin')



@section('header')

    Siparişler

@endsection



@section('content')

@php

    $tabs = [

        'all' => 'Tüm Siparişler',

        'pending' => 'Onay Bekleyen',

        'approved' => 'Onaylanan',

        'shipped' => 'Kargolanan',

        'delivered' => 'Teslim',

        'cancelled' => 'İptal',

        'returned' => 'İade',

    ];

    $activeTab = request('status') ?: 'all';

    $ownerUser = \App\Support\SupportUser::currentUser();

    $canExport = $ownerUser ? app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, 'feature.exports') : false;
    $canBulkCargoLabelPrint = $canBulkCargoLabelPrint ?? false;
    $selectedMarketplaceId = (string) request('marketplace_id', '');
    $marketplaceCounts = $marketplaceCounts ?? collect();
    $allOrdersCount = (int) ($allOrdersCount ?? 0);
    $statusLabelMap = [
        'pending' => 'Beklemede',
        'approved' => 'Onaylandı',
        'shipped' => 'Kargoda',
        'delivered' => 'Teslim',
        'cancelled' => 'İptal',
        'returned' => 'İade',
    ];
    $statusPillClassMap = [
        'pending' => 'orders-status-pill--pending',
        'approved' => 'orders-status-pill--approved',
        'shipped' => 'orders-status-pill--shipped',
        'delivered' => 'orders-status-pill--delivered',
        'cancelled' => 'orders-status-pill--cancelled',
        'returned' => 'orders-status-pill--returned',
    ];
    $statusIconMap = [
        'pending' => 'fa-regular fa-hourglass-half',
        'approved' => 'fa-regular fa-circle-check',
        'shipped' => 'fa-solid fa-truck-fast',
        'delivered' => 'fa-solid fa-box-open',
        'cancelled' => 'fa-solid fa-ban',
        'returned' => 'fa-solid fa-rotate-left',
    ];

    $supportViewEnabled = \App\Support\SupportUser::isEnabled();
    $tabStatusCounts = $tabStatusCounts ?? collect();
    $approvalTabCounters = [
        'pending' => (int) ($tabStatusCounts['pending'] ?? 0),
        'approved' => (int) ($tabStatusCounts['approved'] ?? 0),
    ];
    $tabCounterColorMap = [
        'pending' => 'danger',
        'approved' => 'danger',
        'shipped' => 'neutral',
        'delivered' => 'success',
        'cancelled' => 'danger',
        'returned' => 'danger',
    ];
    $marketplaceLogoMap = [
        'amazon tr' => 'images/brands/amazon.png',
        'amazon' => 'images/brands/amazon.png',
        'hepsiburada' => 'images/brands/hepsiburada.png',
        'n11' => 'images/brands/n11.png',
        'trendyol' => 'images/brands/trendyol.png',
        'cicek sepeti' => 'images/brands/ciceksepeti.png',
    ];

@endphp

<style>
    html {
        scrollbar-gutter: stable;
    }
    body {
        overflow-y: scroll;
    }
    .orders-thumb-wrap {
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
    .orders-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 0.75rem;
    }
    .orders-thumb-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .orders-image-popover {
        position: fixed;
        z-index: 1400;
        pointer-events: none;
        width: 220px;
        height: 220px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.28);
        overflow: hidden;
        display: none;
    }
    .orders-image-popover.is-open {
        display: block;
    }
    .orders-image-popover img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #f8fafc;
    }
    .orders-market-logo-wrap {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .orders-market-cell {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        width: 96px;
        margin: 0 auto;
    }
    .orders-market-name {
        font-size: 10px;
        line-height: 1.1;
        color: #64748b;
        font-weight: 600;
        text-align: center;
        max-width: 84px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .orders-market-logo {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        object-fit: contain;
        background: #fff;
        border: 1px solid #dbe7f5;
        padding: 1px;
    }
    .orders-market-fallback {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
        display: inline-block;
        width: 96px;
        text-align: center;
    }
    :root {
        --orders-market-strip-width: 654px;
    }
    .orders-topbar {
        display: grid;
        grid-template-columns: minmax(0, 1fr) var(--orders-market-strip-width);
        align-items: center;
        gap: 12px;
    }
    .orders-tabs-row {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 10px;
        padding: 4px 14px 4px 4px;
        border: 1px solid #dbe7f5;
        border-radius: 12px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        overflow-x: auto;
        scrollbar-width: none;
        width: max-content;
        max-width: 100%;
        justify-self: start;
    }
    .orders-tabs-row::-webkit-scrollbar {
        display: none;
        height: 0;
    }
    .orders-tab-link {
        position: relative;
        display: inline-flex;
        align-items: center;
        font-size: 14px;
        font-weight: 600;
        color: #8a94a6;
        padding: 9px 18px 9px 12px;
        border-radius: 10px;
        transition: all .2s ease;
        white-space: nowrap;
        flex: 0 0 auto;
    }
    .orders-tab-link:hover {
        color: #475569;
        background: #f2f7ff;
    }
    .orders-tab-link.is-active {
        color: #0f172a;
        background: linear-gradient(135deg, rgba(186, 230, 253, 0.28) 0%, rgba(147, 197, 253, 0.18) 100%);
        box-shadow: inset 0 0 0 1px rgba(147, 197, 253, 0.55), 0 8px 16px rgba(59, 130, 246, 0.1);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .orders-tab-link.is-active::after {
        content: "";
        position: absolute;
        left: 10px;
        right: 10px;
        bottom: 3px;
        height: 2px;
        border-radius: 999px;
        background: #ef4444;
    }
    .orders-tab-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        top: -1px;
        right: -8px;
        min-width: 20px;
        height: 20px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        padding: 0 5px;
        line-height: 1;
        border: 2px solid #fff;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.28);
        vertical-align: middle;
        z-index: 2;
    }
    .orders-tab-count--neutral {
        background: #64748b;
        box-shadow: 0 4px 10px rgba(100, 116, 139, 0.24);
    }
    .orders-tab-count--danger {
        background: #ef4444;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.28);
    }
    .orders-tab-count--success {
        background: #16a34a;
        box-shadow: 0 4px 10px rgba(22, 163, 74, 0.26);
    }
    .orders-tab-count--warning {
        background: #eab308;
        color: #1f2937;
        box-shadow: 0 4px 10px rgba(234, 179, 8, 0.26);
    }
    .orders-tab-count--orange {
        background: #f97316;
        box-shadow: 0 4px 10px rgba(249, 115, 22, 0.26);
    }
    .orders-tab-count.is-empty {
        visibility: hidden;
        opacity: 0;
        box-shadow: none;
        border-color: transparent;
        background: transparent;
    }
    .orders-market-filter-row {
        display: flex;
        align-items: center;
        gap: 11px;
        overflow-x: auto;
        width: var(--orders-market-strip-width);
        min-width: var(--orders-market-strip-width);
        max-width: var(--orders-market-strip-width);
        padding: 8px 8px 2px 0;
        margin-right: 0;
        justify-self: end;
        transform: none;
        scrollbar-width: none;
    }
    .orders-market-filter-card {
        position: relative;
        width: 84px;
        min-width: 84px;
        height: 46px;
        border-radius: 10px;
        border: 1px solid #d8e4f4;
        background: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all .2s ease;
    }
    .orders-market-filter-card:hover {
        border-color: #9dc3ff;
        box-shadow: 0 7px 14px rgba(59, 130, 246, 0.13);
    }
    .orders-market-filter-card.is-active {
        border-color: rgba(96, 165, 250, 0.65);
        background: linear-gradient(135deg, rgba(186, 230, 253, 0.32) 0%, rgba(147, 197, 253, 0.2) 100%);
        box-shadow: 0 10px 22px rgba(59, 130, 246, 0.16), inset 0 0 0 1px rgba(255, 255, 255, 0.45);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .orders-market-filter-logo {
        max-width: 72px;
        max-height: 32px;
        object-fit: contain;
    }
    .orders-market-filter-name {
        font-size: 11px;
        font-weight: 700;
        color: #334155;
    }
    .orders-market-filter-count {
        position: absolute;
        top: -7px;
        right: -2px;
        width: 20px;
        height: 20px;
        border-radius: 9999px;
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25);
        z-index: 2;
    }
    .orders-market-filter-row::-webkit-scrollbar {
        height: 0;
        display: none;
    }
    .orders-market-filter-row::-webkit-scrollbar-thumb {
        display: none;
    }
    @media (max-width: 1100px) {
        .orders-topbar {
            grid-template-columns: 1fr;
            align-items: stretch;
        }
        .orders-market-filter-row {
            justify-self: start;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            padding-top: 4px;
            transform: none;
        }
    }
    .orders-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-width: 1px !important;
        border-style: solid !important;
        font-weight: 600 !important;
    }
    .orders-status-pill i {
        font-size: 11px;
        line-height: 1;
    }
    .orders-status-pill--pending {
        background: #ffedd5 !important;
        color: #9a3412 !important;
        border-color: #fdba74 !important;
    }
    .orders-status-pill--approved {
        background: #dcfce7 !important;
        color: #166534 !important;
        border-color: #86efac !important;
    }
    .orders-status-pill--shipped {
        background: #e0f2fe !important;
        color: #075985 !important;
        border-color: #7dd3fc !important;
    }
    .orders-status-pill--delivered {
        background: #dbeafe !important;
        color: #1d4ed8 !important;
        border-color: #93c5fd !important;
    }
    .orders-status-pill--cancelled {
        background: #ffe4e6 !important;
        color: #9f1239 !important;
        border-color: #fda4af !important;
    }
    .orders-status-pill--returned {
        background: #f3e8ff !important;
        color: #6b21a8 !important;
        border-color: #d8b4fe !important;
    }
    .orders-filter-panel,
    .orders-action-panel {
        border: 1px solid #dbe7f5;
        background: linear-gradient(165deg, #ffffff 0%, #f8fbff 100%);
        border-radius: 18px;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }
    .orders-control-label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 6px;
    }
    .orders-input {
        width: 100%;
        border: 1px solid #cfe0f6 !important;
        background: rgba(255, 255, 255, 0.9) !important;
        border-radius: 12px !important;
        padding: 10px 12px !important;
        color: #0f172a !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .orders-input:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.22) !important;
        outline: none;
    }
    .orders-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        border-radius: 12px;
        padding: 10px 16px;
        font-weight: 700;
        font-size: 15px;
        line-height: 1;
        transition: all .2s ease;
        border: 1px solid transparent;
        white-space: nowrap;
        text-decoration: none;
        appearance: none;
    }
    .orders-btn i {
        font-size: 13px;
    }
    .orders-btn-primary {
        color: #fff;
        background: #111111;
        box-shadow: 0 8px 14px rgba(15, 23, 42, 0.18);
    }
    .orders-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 20px rgba(15, 23, 42, 0.22);
    }
    .orders-btn-outline {
        color: #1f2937;
        border-color: #cbd5e1;
        background: #ffffff;
    }
    .orders-btn-outline:hover {
        border-color: #9ca3af;
        background: #f8fafc;
    }
    .orders-btn-soft {
        color: #475569 !important;
        border-color: #d8e4f4 !important;
        background: rgba(248, 251, 255, 0.9) !important;
    }
    .orders-btn-soft:hover {
        color: #1e293b !important;
        border-color: #b9d2f0 !important;
        background: #ffffff !important;
    }
    .orders-btn-uniform {
        height: 44px;
        min-width: 122px;
        flex: 1 1 0%;
    }
    main .orders-filter-panel button.orders-btn.orders-btn-soft.orders-btn-uniform {
        border: 1px solid #d8e4f4 !important;
        background: rgba(248, 251, 255, 0.9) !important;
        color: #475569 !important;
        border-radius: 12px !important;
        min-height: 44px !important;
        height: 44px !important;
        box-shadow: none !important;
    }
    main .orders-filter-panel button.orders-btn.orders-btn-soft.orders-btn-uniform:hover {
        color: #1e293b !important;
        border-color: #b9d2f0 !important;
        background: #ffffff !important;
    }
    .orders-btn-hover-orange:hover {
        color: #9a3412 !important;
        border-color: #fb923c !important;
        background: linear-gradient(135deg, rgba(255, 237, 213, 0.92) 0%, rgba(254, 215, 170, 0.88) 100%) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72), 0 0 0 3px rgba(251, 146, 60, 0.34), 0 8px 18px rgba(249, 115, 22, 0.18) !important;
        transform: translateY(1px);
    }
    main .orders-action-panel .orders-btn-hover-orange:hover {
        color: #9a3412 !important;
        border-color: #fb923c !important;
        background: linear-gradient(135deg, rgba(255, 237, 213, 0.92) 0%, rgba(254, 215, 170, 0.88) 100%) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72), 0 0 0 3px rgba(251, 146, 60, 0.34), 0 10px 22px rgba(249, 115, 22, 0.2) !important;
        transform: translateY(1px);
    }
    .orders-btn-hover-orange:focus-visible {
        outline: none;
        color: #9a3412 !important;
        border-color: #fb923c !important;
        background: linear-gradient(135deg, rgba(255, 237, 213, 0.94) 0%, rgba(254, 215, 170, 0.9) 100%) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72), 0 0 0 3px rgba(251, 146, 60, 0.38) !important;
    }
    .orders-bulk-btn {
        min-height: 44px !important;
        height: 44px !important;
        min-width: 260px;
    }
    .orders-bulk-btn-compact {
        min-width: 160px !important;
        width: 160px;
    }
    .orders-bulk-select-wrap {
        min-width: 160px;
        width: 160px;
    }
    .orders-bulk-select-field {
        position: relative;
    }
    .orders-bulk-select-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        font-size: 14px;
        pointer-events: none;
    }
    .orders-bulk-selected-count {
        position: absolute;
        top: 50%;
        right: 26px;
        transform: translateY(-50%);
        min-width: 18px;
        height: 18px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
        border: 2px solid #fff;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.25);
        opacity: 0;
        visibility: hidden;
        transition: opacity .15s ease;
    }
    .orders-bulk-selected-count.is-visible {
        opacity: 1;
        visibility: visible;
    }
    main .orders-action-panel button.orders-btn.orders-btn-soft.orders-bulk-btn {
        border: 1px solid #d8e4f4 !important;
        background: rgba(248, 251, 255, 0.9) !important;
        color: #334155 !important;
        border-radius: 12px !important;
        box-shadow: none !important;
    }
    main .orders-action-panel button.orders-btn.orders-btn-soft.orders-bulk-btn:not(.orders-btn-hover-orange):hover {
        color: #1e293b !important;
        border-color: #b9d2f0 !important;
        background: #ffffff !important;
    }
    main .orders-action-panel button.orders-btn.orders-btn-soft.orders-bulk-btn.orders-btn-hover-orange:hover {
        color: #7c2d12 !important;
        border-color: #f97316 !important;
        background: rgba(254, 215, 170, 0.62) !important;
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.38), 0 8px 16px rgba(249, 115, 22, 0.16) !important;
        transform: translateY(1px);
    }
    main .orders-action-panel button.orders-btn.orders-btn-soft.orders-bulk-btn.orders-btn-hover-orange:focus-visible {
        outline: none !important;
        color: #7c2d12 !important;
        border-color: #f97316 !important;
        background: rgba(254, 215, 170, 0.64) !important;
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.42) !important;
    }
    @media (max-width: 767px) {
        .orders-bulk-btn {
            width: 100%;
            min-width: 0;
        }
    }
    .orders-inline-search-wrap {
        flex: 1;
        min-width: 280px;
    }
    .orders-inline-search-field {
        position: relative;
    }
    .orders-inline-search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
        pointer-events: none;
    }
    .orders-inline-search {
        width: 100%;
        border: 1px solid #cfe0f6 !important;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 251, 255, 0.92) 100%) !important;
        border-radius: 12px !important;
        padding: 10px 12px 10px 38px !important;
        color: #0f172a !important;
        font-size: 14px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85), 0 4px 10px rgba(15, 23, 42, 0.05);
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .orders-inline-search:focus {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.22), 0 8px 16px rgba(59, 130, 246, 0.12) !important;
        outline: none;
    }
    .orders-action-right {
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .orders-bulk-warning {
        position: fixed;
        z-index: 2200;
        display: none;
        align-items: center;
        gap: 10px;
        border: 1px solid #d1d5db;
        background: #ffffff;
        color: #111827;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 14px;
        font-weight: 500;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.22);
        pointer-events: none;
    }
    .orders-bulk-warning.is-visible {
        display: inline-flex;
    }
    .orders-bulk-warning-icon {
        width: 20px;
        height: 20px;
        border-radius: 4px;
        background: #f59e0b;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
    }
    .orders-ciro-modal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 2300;
        background: rgba(15, 23, 42, 0.42);
        backdrop-filter: blur(2px);
    }
    .orders-ciro-modal.is-open {
        display: flex;
    }
    .orders-ciro-modal-card {
        width: min(92vw, 460px);
        border-radius: 18px;
        border: 1px solid #dbe7f5;
        background: linear-gradient(165deg, #ffffff 0%, #f8fbff 60%, #f1f8ff 100%);
        box-shadow: 0 30px 60px rgba(15, 23, 42, 0.28);
        padding: 18px;
    }
    .orders-ciro-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .orders-ciro-title {
        font-size: 18px;
        font-weight: 800;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .orders-ciro-close {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: 1px solid #dbe7f5;
        background: #fff;
        color: #475569;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .orders-ciro-close:hover {
        background: #f8fafc;
    }
    .orders-ciro-controls {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 10px;
        margin-bottom: 12px;
    }
    .orders-ciro-result {
        border: 1px solid #dbe7f5;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.88);
        padding: 14px;
    }
    .orders-ciro-meta {
        font-size: 12px;
        color: #64748b;
    }
    .orders-ciro-date {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-align: center;
    }
    .orders-ciro-split {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        align-items: end;
    }
    .orders-ciro-orders {
        font-size: 14px;
        color: #000000;
        font-weight: 700;
        border: 2px solid #000;
        border-radius: 10px;
        min-height: 62px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        padding: 0 14px;
        background: #fff;
    }
    .orders-ciro-orders-value {
        font-size: 30px;
        line-height: 1;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .orders-ciro-orders-text {
        font-size: 16px;
        color: #0f172a;
        font-weight: 700;
    }
    .orders-ciro-total-card {
        min-height: 62px;
        border: 2px solid #000;
        border-radius: 10px;
        background: #fff;
        padding: 10px 14px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .orders-ciro-total-label {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }
    .orders-ciro-total {
        font-size: 32px;
        line-height: 1;
        font-weight: 800;
        color: #16a34a;
        text-align: right;
        letter-spacing: -0.02em;
    }
    .orders-ciro-calc-btn {
        min-width: 160px;
        height: 44px !important;
        border: 2px solid #b9cbe2 !important;
        background: rgba(248, 251, 255, 0.9) !important;
        color: #334155 !important;
        font-weight: 800 !important;
        font-size: 16px !important;
        padding: 0 18px !important;
        letter-spacing: 0.01em;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 2px 6px rgba(15, 23, 42, 0.06) !important;
        border-radius: 12px !important;
    }
    .orders-ciro-calc-btn:hover {
        color: #7c2d12 !important;
        border-color: #f97316 !important;
        background: rgba(254, 215, 170, 0.62) !important;
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.38), 0 8px 16px rgba(249, 115, 22, 0.16) !important;
        transform: translateY(1px);
    }
    .orders-ciro-calc-btn:focus-visible {
        outline: none;
        color: #7c2d12 !important;
        border-color: #f97316 !important;
        background: rgba(254, 215, 170, 0.64) !important;
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.42) !important;
    }
    .orders-ciro-error {
        margin-top: 10px;
        font-size: 13px;
        color: #dc2626;
        display: none;
    }
    .orders-ciro-error.is-visible {
        display: block;
    }
    .orders-label-centered {
        width: 100%;
        justify-content: center;
        text-align: center;
    }
    .orders-bulk-select-wrap .orders-input {
        text-align: left;
        text-align-last: left;
        padding-left: 36px !important;
        padding-right: 46px !important;
    }
    .orders-bulk-select-wrap .orders-input option {
        text-align: center;
    }
    @media (max-width: 767px) {
        .orders-bulk-select-wrap {
            width: 100%;
            min-width: 0;
        }
    }
    .orders-inline-row > td {
        padding: 6px 0 0 0;
    }
    .orders-inline-row.is-hidden {
        display: none;
    }
    .orders-inline-panel {
        margin: 0 8px 8px;
        border: 1px solid #cfe0f5;
        border-radius: 12px;
        background: linear-gradient(145deg, #fafdff 0%, #f4f8ff 100%);
        padding: 8px;
    }
    .orders-inline-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
    }
    .orders-inline-title {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.2;
    }
    .orders-inline-subtitle {
        font-size: 12px;
        color: #1e293b;
        font-weight: 400;
        font-family: "Manrope", "Segoe UI", "Inter", sans-serif;
    }
    .orders-inline-subtitle-label {
        color: #0f172a;
        font-weight: 800;
    }
    .orders-inline-subtitle-value {
        color: #1e293b;
        font-weight: 400;
    }
    .orders-inline-grid {
        margin-top: 6px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 6px;
    }
    .orders-inline-box {
        border: 1px solid #d4deea;
        border-radius: 10px;
        background: #fff;
        padding: 6px 8px;
        min-height: 38px;
    }
    .orders-inline-line {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }
    .orders-inline-icon {
        width: 24px;
        height: 24px;
        border-radius: 999px;
        border: 1px solid #d7e3f4;
        background: #f7faff;
        color: #5b6e8b;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 11px;
    }
    .orders-inline-label {
        font-size: 12px;
        font-weight: 800;
        color: #0f172a;
        white-space: nowrap;
    }
    .orders-inline-value {
        font-size: 12px;
        line-height: 1.2;
        color: #1e293b;
        font-weight: 400;
        word-break: break-word;
        min-width: 0;
        font-family: "Manrope", "Segoe UI", "Inter", sans-serif;
    }
    .orders-inline-value.is-positive {
        color: #059669;
        font-weight: 400;
    }
    .orders-inline-box.is-full {
        grid-column: span 4;
    }
    .orders-inline-collapse-wrap {
        display: flex;
        justify-content: center;
        margin-top: 6px;
    }
    .orders-inline-collapse {
        width: 28px;
        height: 20px;
        border: none;
        background: transparent;
        color: #f97316 !important;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: color .2s ease, transform .2s ease;
    }
    .orders-inline-collapse:hover {
        color: #ea580c !important;
        transform: translateY(-1px);
    }
    .orders-inline-collapse i {
        font-size: 13px;
        line-height: 1;
        color: #f97316 !important;
    }
    .orders-inline-collapse:hover i {
        color: #ea580c !important;
    }
    .orders-detail-btn {
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #1d4ed8;
        font-size: 11px;
        font-weight: 700;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
    }
    .orders-detail-btn .orders-detail-chevron {
        font-size: 12px;
        font-weight: 600;
    }
    .orders-eye-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid #fecaca;
        background: #fff5f5;
        color: #ef4444;
        font-size: 12px;
        transition: all .2s ease;
    }
    .orders-eye-link:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fca5a5;
    }
    .orders-action-tools {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .orders-print-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid #bbf7d0;
        background: #ecfdf5;
        color: #059669;
        font-size: 12px;
        transition: all .2s ease;
    }
    .orders-print-link:hover {
        border-color: #86efac;
        background: #dcfce7;
        color: #047857;
    }
    @media (max-width: 1100px) {
        .orders-inline-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .orders-inline-box.is-full { grid-column: span 2; }
        .orders-inline-label { font-size: 12px; }
        .orders-inline-value { font-size: 13px; }
    }
    @media (max-width: 640px) {
        .orders-inline-grid { grid-template-columns: 1fr; }
        .orders-inline-box.is-full,
        .orders-inline-box.is-wide { grid-column: span 1; }
    }
</style>



<div class="panel-card p-4 mb-6">

    <div class="orders-topbar border-b border-slate-100 pb-3">
        <div class="orders-tabs-row">
            @foreach($tabs as $key => $label)
                <a href="{{ route('portal.orders.index', array_filter(array_merge(request()->query(), ['status' => $key === 'all' ? null : $key]))) }}"
                   class="orders-tab-link {{ $activeTab === $key ? 'is-active' : '' }}">
                    {{ $label }}
                    @php
                        $tabCount = (int) ($tabStatusCounts[$key] ?? 0);
                        $tabCounterTone = $tabCounterColorMap[$key] ?? null;
                        $showCount = $tabCounterTone && $tabCount > 0;
                    @endphp
                    <span class="orders-tab-count {{ $tabCounterTone ? 'orders-tab-count--' . $tabCounterTone : '' }} {{ $showCount ? '' : 'is-empty' }}">
                        {{ $showCount ? $tabCount : 0 }}
                    </span>
                </a>
            @endforeach
        </div>
        <div class="orders-market-filter-row" id="orders-market-filter-row">
            <a href="{{ route('portal.orders.index', array_filter(array_merge(request()->query(), ['marketplace_id' => null]))) }}"
               class="orders-market-filter-card {{ $selectedMarketplaceId === '' ? 'is-active' : '' }}"
               title="Tümü">
                <span class="orders-market-filter-name">TÜMÜ</span>
                @if($allOrdersCount > 0)
                    <span class="orders-market-filter-count">{{ $allOrdersCount }}</span>
                @endif
            </a>
            @foreach($marketplaces as $marketplace)
                @php
                    $mpName = (string) ($marketplace->name ?? '');
                    $mpKey = \Illuminate\Support\Str::of(trim($mpName))->lower()->ascii()->value();
                    $mpLogo = $marketplaceLogoMap[$mpKey] ?? null;
                    $mpCount = (int) ($marketplaceCounts[$marketplace->id] ?? 0);
                @endphp
                <a href="{{ route('portal.orders.index', array_filter(array_merge(request()->query(), ['marketplace_id' => $marketplace->id]))) }}"
                   class="orders-market-filter-card {{ $selectedMarketplaceId === (string) $marketplace->id ? 'is-active' : '' }}"
                   title="{{ $mpName }}">
                    @if($mpLogo)
                        <img src="{{ asset($mpLogo) }}" alt="{{ $mpName }}" class="orders-market-filter-logo">
                    @else
                        <span class="orders-market-filter-name">{{ \Illuminate\Support\Str::limit($mpName, 8, '') }}</span>
                    @endif
                    @if($mpCount > 0)
                        <span class="orders-market-filter-count">{{ $mpCount }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    </div>



    <form method="GET" action="{{ route('portal.orders.index') }}" class="orders-filter-panel grid grid-cols-1 md:grid-cols-12 gap-4 mt-4 p-4">
        <input type="hidden" name="marketplace_id" value="{{ $selectedMarketplaceId }}">

        <div class="md:col-span-3">

            <label class="block orders-control-label">Durum</label>

            <select name="status" class="orders-input">

                <option value="">Tümü</option>

                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Beklemede</option>

                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Onaylandı</option>

                <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Kargoda</option>

                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Teslim</option>

                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>İptal</option>

                <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>İade</option>

            </select>

        </div>



        <div class="md:col-span-3">

            <label class="block orders-control-label">Başlangıç</label>

            <input type="date" name="date_from" value="{{ request('date_from') }}"

                class="orders-input">

        </div>



        <div class="md:col-span-3">

            <label class="block orders-control-label">Bitiş</label>

            <input type="date" name="date_to" value="{{ request('date_to') }}"

                class="orders-input">

        </div>



        <div class="md:col-span-3 flex items-end gap-2">

            <button type="submit" class="orders-btn orders-btn-soft orders-btn-uniform">

                Filtrele

            </button>

            <a href="{{ route('portal.orders.index') }}" class="orders-btn orders-btn-soft orders-btn-uniform">

                Temizle

            </a>

        </div>

    </form>

</div>



<form id="orders-bulk-update-form" method="POST" action="{{ route('portal.orders.bulk-update') }}">

    @csrf

    <div class="orders-action-panel p-4 mb-4 flex flex-col md:flex-row md:items-end gap-3">
        <div id="orders-bulk-warning" class="orders-bulk-warning" role="alert">
            <span class="orders-bulk-warning-icon">!</span>
            <span>Lütfen tablodan seçim yapın</span>
        </div>

        <div class="orders-bulk-select-wrap">
            <div class="orders-bulk-select-field">
                <i class="fa-solid fa-list-check orders-bulk-select-icon" aria-hidden="true"></i>
                <select
                    id="orders-bulk-status"
                    name="status"
                    class="orders-input"
                    required
                    oninvalid="this.setCustomValidity('Lütfen listeden bir öğe seçin')"
                    onchange="this.setCustomValidity('')"
                >

                    <option value="">Seçiniz</option>

                    <option value="pending">Beklemede</option>

                    <option value="approved">Onaylandı</option>

                    <option value="shipped">Kargoda</option>

                    <option value="delivered">Teslim</option>

                    <option value="cancelled">İptal</option>

                    <option value="returned">İade</option>

                </select>
                <span id="orders-bulk-selected-count" class="orders-bulk-selected-count" aria-live="polite">0</span>
            </div>

        </div>

        <button id="orders-process-btn" type="submit" class="orders-btn orders-btn-soft orders-bulk-btn orders-bulk-btn-compact orders-btn-hover-orange">
            <i class="fa-solid fa-bolt"></i>
            <span>İşleme Al</span>
        </button>

        <div class="orders-inline-search-wrap">
            <div class="orders-inline-search-field">
                <input
                    id="orders-inline-search"
                    type="search"
                    class="orders-inline-search"
                    placeholder="Sipariş no, müşteri, pazaryeri, durum..."
                    autocomplete="off"
                >
            </div>
        </div>

        <div class="orders-action-right">
            @if($canBulkCargoLabelPrint)
            <button
                type="submit"
                class="orders-btn orders-btn-soft orders-bulk-btn orders-btn-hover-orange"
                formaction="{{ route('portal.orders.labels.print') }}"
                formmethod="POST"
                formnovalidate
            >
                <i class="fa-solid fa-print"></i>
                <span>Seçili Siparişler için Etiket Yazdır</span>
            </button>
            @endif

            @if($canExport)
            <button
                id="orders-daily-revenue-btn"
                type="button"
                class="orders-btn orders-btn-soft orders-bulk-btn orders-bulk-btn-compact orders-btn-hover-orange inline-flex items-center justify-center"
                data-url="{{ route('portal.orders.daily-revenue', [], false) }}"
            >
                <i class="fa-solid fa-chart-line"></i>
                <span>Ciro</span>
            </button>
            @endif
        </div>


    </div>

    <div id="orders-ciro-modal" class="orders-ciro-modal" aria-hidden="true">
        <div class="orders-ciro-modal-card" role="dialog" aria-modal="true" aria-labelledby="orders-ciro-title">
            <div class="orders-ciro-head">
                <h3 id="orders-ciro-title" class="orders-ciro-title">
                    <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                    <span>Günlük Ciro</span>
                </h3>
                <button id="orders-ciro-close" type="button" class="orders-ciro-close" aria-label="Kapat">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="orders-ciro-controls">
                <input id="orders-ciro-date" type="date" class="orders-input" />
                <button id="orders-ciro-calc-btn" type="button" class="orders-btn orders-btn-soft orders-btn-hover-orange orders-ciro-calc-btn">
                    <i class="fa-solid fa-calculator" aria-hidden="true"></i>
                    <span>Hesapla</span>
                </button>
            </div>
            <div class="orders-ciro-result">
                <div class="orders-ciro-date" id="orders-ciro-date-label">
                    <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                    <span id="orders-ciro-date-text">Tarih:</span>
                </div>
                <div class="orders-ciro-split">
                    <div class="orders-ciro-orders" id="orders-ciro-count">
                        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                        <span class="orders-ciro-orders-value" id="orders-ciro-count-value">0</span>
                        <span class="orders-ciro-orders-text">sipariş</span>
                    </div>
                    <div class="orders-ciro-total-card">
                        <span class="orders-ciro-total-label">
                            <i class="fa-solid fa-coins" aria-hidden="true"></i>
                            <span>Tutar</span>
                        </span>
                        <span class="orders-ciro-total" id="orders-ciro-total">-</span>
                    </div>
                </div>
                <div class="orders-ciro-error" id="orders-ciro-error"></div>
            </div>
        </div>
    </div>



    <div class="panel-card overflow-hidden">

        <table class="min-w-full" id="orders-table">

        <thead class="bg-slate-50">

            <tr>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <input type="checkbox" id="orders-select-all" class="rounded border-slate-300 text-[#ff4439] focus:ring-[#ff4439]">

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Görsel</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Sipariş No</th>

                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase">Pazaryeri</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Müşteri</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tutar</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Durum</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Sipariş Tarihi</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">İşlem</th>

            </tr>

        </thead>

        <tbody class="bg-white divide-y divide-slate-100">

            @forelse($orders as $order)

            @php
                $firstItem = is_array($order->items) ? ($order->items[0] ?? null) : null;
                $itemsCount = is_array($order->items) ? count($order->items) : 0;
                $itemNames = is_array($order->items)
                    ? collect($order->items)->pluck('name')->filter()->take(3)->implode(', ')
                    : '-';
                $rawOrderImageUrl = data_get($firstItem, 'image_url')
                    ?: data_get($firstItem, 'image')
                    ?: data_get($firstItem, 'product_image')
                    ?: data_get($firstItem, 'productImage');
                $orderImageUrl = null;
                if (is_string($rawOrderImageUrl) && trim($rawOrderImageUrl) !== '') {
                    $rawOrderImageUrl = trim($rawOrderImageUrl);
                    if (
                        \Illuminate\Support\Str::startsWith($rawOrderImageUrl, ['http://', 'https://', '//', 'data:image', '/'])
                    ) {
                        $orderImageUrl = $rawOrderImageUrl;
                    } elseif (\Illuminate\Support\Str::startsWith($rawOrderImageUrl, 'storage/')) {
                        $orderImageUrl = asset($rawOrderImageUrl);
                    } else {
                        $orderImageUrl = asset('storage/' . ltrim($rawOrderImageUrl, '/'));
                    }
                }
                $marketplaceName = (string) ($order->marketplace->name ?? '');
                $marketplaceKey = \Illuminate\Support\Str::of(trim($marketplaceName))->lower()->ascii()->value();
                $marketplaceLogo = $marketplaceLogoMap[$marketplaceKey] ?? null;
                $shippingAddressText = $order->shipping_address;
                if (is_string($shippingAddressText)) {
                    $decoded = json_decode($shippingAddressText, true);
                    if (is_array($decoded)) {
                        $shippingAddressText = trim(implode(' ', array_filter([
                            $decoded['address'] ?? $decoded['line1'] ?? null,
                            $decoded['district'] ?? null,
                            $decoded['city'] ?? null,
                        ])));
                    }
                } elseif (is_array($shippingAddressText)) {
                    $shippingAddressText = trim(implode(' ', array_filter([
                        $shippingAddressText['address'] ?? $shippingAddressText['line1'] ?? null,
                        $shippingAddressText['district'] ?? null,
                        $shippingAddressText['city'] ?? null,
                    ])));
                }
                $billingAddressText = $order->billing_address;
                if (is_string($billingAddressText)) {
                    $decodedBilling = json_decode($billingAddressText, true);
                    if (is_array($decodedBilling)) {
                        $billingAddressText = trim(implode(' ', array_filter([
                            $decodedBilling['address'] ?? $decodedBilling['line1'] ?? null,
                            $decodedBilling['district'] ?? null,
                            $decodedBilling['city'] ?? null,
                        ])));
                    }
                } elseif (is_array($billingAddressText)) {
                    $billingAddressText = trim(implode(' ', array_filter([
                        $billingAddressText['address'] ?? $billingAddressText['line1'] ?? null,
                        $billingAddressText['district'] ?? null,
                        $billingAddressText['city'] ?? null,
                    ])));
                }
            @endphp

            <tr>

                <td class="px-6 py-4 whitespace-nowrap">

                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="orders-row-check rounded border-slate-300 text-[#ff4439] focus:ring-[#ff4439]">

                    <input type="checkbox" class="hidden orders-bulk-ship-check" name="bulk_ship_ids[]" value="{{ $order->id }}">

                </td>

                <td class="px-6 py-4 whitespace-nowrap">
                    @if($orderImageUrl)
                        <span class="orders-thumb-wrap"
                              data-order-preview-src="{{ $orderImageUrl }}"
                              data-order-preview-alt="{{ $order->marketplace_order_id }}">
                            <img src="{{ $orderImageUrl }}" alt="{{ $order->marketplace_order_id }}" class="orders-thumb" onerror="this.onerror=null;const w=this.closest('.orders-thumb-wrap');if(!w){return;}w.outerHTML='&lt;span class=&quot;orders-thumb-placeholder&quot;&gt;&lt;i class=&quot;fas fa-image text-slate-400&quot;&gt;&lt;/i&gt;&lt;/span&gt;';">
                        </span>
                    @else
                        <span class="orders-thumb-placeholder">
                            <i class="fas fa-image text-slate-400"></i>
                        </span>
                    @endif
                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <code class="bg-slate-100 px-2 py-1 rounded text-xs">{{ $order->marketplace_order_id }}</code>

                </td>

                <td class="px-6 py-4 whitespace-nowrap font-semibold text-center align-middle">
                    @if($marketplaceLogo)
                        <span class="orders-market-cell">
                            <span class="orders-market-logo-wrap">
                                <img src="{{ asset($marketplaceLogo) }}" alt="{{ $marketplaceName }}" class="orders-market-logo" title="{{ $marketplaceName }}">
                            </span>
                            <span class="orders-market-name">{{ $marketplaceName }}</span>
                        </span>
                    @else
                        <span class="orders-market-fallback">{{ $marketplaceName ?: '-' }}</span>
                    @endif
                </td>

                <td class="px-6 py-4">

                    <div class="text-sm font-medium text-slate-900">{{ $order->customer_name }}</div>

                    @if($order->customer_phone)

                        <div class="text-xs text-slate-500">

                            {{ $supportViewEnabled ? \App\Support\SupportUser::maskPhone($order->customer_phone) : $order->customer_phone }}

                        </div>

                    @endif

                </td>

                <td class="px-6 py-4 whitespace-nowrap font-semibold">

                    {{ number_format($order->total_amount, 2) }} {{ $order->currency }}

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <span class="panel-pill text-xs orders-status-pill {{ $statusPillClassMap[$order->status] ?? '' }}">

                        <i class="{{ $statusIconMap[$order->status] ?? 'fa-regular fa-circle' }}"></i>
                        <span>{{ $statusLabelMap[$order->status] ?? ucfirst($order->status) }}</span>

                    </span>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $order->order_date->format('d.m.Y H:i') }}</td>

                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="orders-action-tools">
                        @if($canBulkCargoLabelPrint)
                            <a
                                href="{{ route('portal.orders.labels.single', $order) }}"
                                class="orders-print-link"
                                title="Kargo etiketi yazdir"
                                target="_blank"
                                rel="noopener"
                            >
                                <i class="fa-solid fa-print"></i>
                            </a>
                        @endif
                        <a href="{{ route('portal.orders.show', ['order' => $order, 'popup' => 1]) }}" class="orders-eye-link js-order-popup" title="Sipariş detay sayfasını aç" aria-label="Sipariş detay sayfasını aç">
                            <i class="fa-regular fa-eye" aria-hidden="true"></i>
                        </a>
                        <button type="button" class="orders-detail-btn" data-orders-toggle-detail title="Satır detayını aç" aria-label="Satır detayını aç" aria-expanded="false">
                            <i class="fa-solid fa-chevron-down orders-detail-chevron" aria-hidden="true"></i>
                        </button>
                    </div>

                </td>

            </tr>
            <tr class="orders-inline-row is-hidden" data-orders-inline-row>
                <td colspan="9">
                    <div class="orders-inline-panel">
                        <div class="orders-inline-head">
                            <div class="orders-inline-title"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Sipariş Detayı</div>
                            <div class="orders-inline-subtitle"><span class="orders-inline-subtitle-label">Sipariş Numarası :</span> <span class="orders-inline-subtitle-value">{{ $order->marketplace_order_id }}</span></div>
                        </div>
                        <div class="orders-inline-grid">
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-regular fa-user" aria-hidden="true"></i></span><span class="orders-inline-label">Müşteri :</span><span class="orders-inline-value">{{ $order->customer_name ?: '-' }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-solid fa-store" aria-hidden="true"></i></span><span class="orders-inline-label">Pazaryeri :</span><span class="orders-inline-value">{{ $marketplaceName ?: '-' }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-regular fa-circle-check" aria-hidden="true"></i></span><span class="orders-inline-label">Durum :</span><span class="orders-inline-value">{{ $statusLabelMap[$order->status] ?? ucfirst($order->status) }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-solid fa-wallet" aria-hidden="true"></i></span><span class="orders-inline-label">Toplam Tutar :</span><span class="orders-inline-value is-positive">{{ number_format((float) $order->total_amount, 2) }} {{ $order->currency }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-solid fa-percent" aria-hidden="true"></i></span><span class="orders-inline-label">Komisyon :</span><span class="orders-inline-value">{{ number_format((float) ($order->commission_amount ?? 0), 2) }} {{ $order->currency }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-solid fa-coins" aria-hidden="true"></i></span><span class="orders-inline-label">Net Tutar :</span><span class="orders-inline-value">{{ number_format((float) ($order->net_amount ?? 0), 2) }} {{ $order->currency }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></span><span class="orders-inline-label">Ürün Kalemi :</span><span class="orders-inline-value">{{ $itemsCount }} ürün</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-solid fa-box" aria-hidden="true"></i></span><span class="orders-inline-label">Ürünler :</span><span class="orders-inline-value">{{ $itemNames ?: '-' }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i></span><span class="orders-inline-label">Teslimat Adresi :</span><span class="orders-inline-value">{{ $shippingAddressText ?: '-' }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-regular fa-file-lines" aria-hidden="true"></i></span><span class="orders-inline-label">Fatura Adresi :</span><span class="orders-inline-value">{{ $billingAddressText ?: '-' }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span><span class="orders-inline-label">Sipariş Tarihi :</span><span class="orders-inline-value">{{ optional($order->order_date)->format('d.m.Y H:i') ?? '-' }}</span></div>
                            </div>
                            <div class="orders-inline-box">
                                <div class="orders-inline-line"><span class="orders-inline-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></span><span class="orders-inline-label">Telefon :</span><span class="orders-inline-value">{{ $order->customer_phone ?: '-' }}</span></div>
                            </div>
                        </div>
                        <div class="orders-inline-collapse-wrap">
                            <button type="button" class="orders-inline-collapse" data-orders-inline-close title="Detayı kapat" aria-label="Detayı kapat">
                                <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </td>
            </tr>

            @empty

            <tr>

                <td colspan="9" class="px-6 py-4 text-center text-slate-500">Henüz sipariş bulunmuyor</td>

            </tr>

            @endforelse

        </tbody>

        </table>

    </div>

</form>



<div class="mt-6">

    {{ $orders->links() }}

</div>

<div id="orders-image-popover" class="orders-image-popover" aria-hidden="true">
    <img id="orders-image-popover-img" src="" alt="">
</div>

<script>

    const ordersSelectAll = document.getElementById('orders-select-all');
    const orderRowChecks = Array.from(document.querySelectorAll('.orders-row-check'));
    const ordersBulkSelectedCount = document.getElementById('orders-bulk-selected-count');

    const syncOrdersSelectAllState = () => {
        if (!ordersSelectAll) return;
        const checkedCount = orderRowChecks.filter((cb) => cb.checked).length;
        ordersSelectAll.checked = checkedCount > 0 && checkedCount === orderRowChecks.length;
        ordersSelectAll.indeterminate = checkedCount > 0 && checkedCount < orderRowChecks.length;
        if (ordersBulkSelectedCount) {
            ordersBulkSelectedCount.textContent = String(checkedCount);
            ordersBulkSelectedCount.classList.toggle('is-visible', checkedCount > 0);
        }
    };

    ordersSelectAll?.addEventListener('change', () => {
        orderRowChecks.forEach((cb) => {
            cb.checked = ordersSelectAll.checked;
            const hidden = cb.closest('td')?.querySelector('.orders-bulk-ship-check');
            if (hidden) {
                hidden.checked = cb.checked;
            }
        });
        syncOrdersSelectAllState();
    });

    document.querySelectorAll('input[name="order_ids[]"]').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var hidden = cb.parentElement.querySelector('input[name="bulk_ship_ids[]"]');
            if (hidden) {
                hidden.checked = cb.checked;
            }
            if (orderRowChecks.some((x) => x.checked)) {
                ordersBulkWarning?.classList.remove('is-visible');
            }
            syncOrdersSelectAllState();
        });
    });

    syncOrdersSelectAllState();

    const ordersImagePopover = document.getElementById('orders-image-popover');
    const ordersImagePopoverImg = document.getElementById('orders-image-popover-img');
    const ordersImageTriggers = Array.from(document.querySelectorAll('[data-order-preview-src]'));
    const orderPopupLinks = Array.from(document.querySelectorAll('.js-order-popup'));
    const ordersDetailToggleButtons = Array.from(document.querySelectorAll('[data-orders-toggle-detail]'));
    const ordersInlineCloseButtons = Array.from(document.querySelectorAll('[data-orders-inline-close]'));
    const ordersInlineSearch = document.getElementById('orders-inline-search');
    const ordersTableRows = Array.from(document.querySelectorAll('#orders-table tbody tr'));
    const ordersBulkUpdateForm = document.getElementById('orders-bulk-update-form');
    const ordersBulkStatus = document.getElementById('orders-bulk-status');
    const ordersBulkWarning = document.getElementById('orders-bulk-warning');
    const ordersProcessBtn = document.getElementById('orders-process-btn');
    const ordersDailyRevenueBtn = document.getElementById('orders-daily-revenue-btn');
    const ordersCiroModal = document.getElementById('orders-ciro-modal');
    const ordersCiroClose = document.getElementById('orders-ciro-close');
    const ordersCiroCalcBtn = document.getElementById('orders-ciro-calc-btn');
    const ordersCiroDate = document.getElementById('orders-ciro-date');
    const ordersCiroTotal = document.getElementById('orders-ciro-total');
    const ordersCiroCount = document.getElementById('orders-ciro-count');
    const ordersCiroCountValue = document.getElementById('orders-ciro-count-value');
    const ordersCiroDateLabel = document.getElementById('orders-ciro-date-label');
    const ordersCiroDateText = document.getElementById('orders-ciro-date-text');
    const ordersCiroError = document.getElementById('orders-ciro-error');

    const getCiroDefaultDate = () => {
        const dateTo = document.querySelector('input[name="date_to"]');
        if (dateTo?.value) return dateTo.value;
        return new Date().toISOString().slice(0, 10);
    };

    const formatCiroCurrency = (amount, currency) => {
        try {
            return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: currency || 'TRY' }).format(amount || 0);
        } catch (_) {
            return `${Number(amount || 0).toFixed(2)} ${currency || 'TRY'}`;
        }
    };

    const formatCiroDate = (isoDate) => {
        if (!isoDate || typeof isoDate !== 'string') return '-';
        const parts = isoDate.split('-');
        if (parts.length !== 3) return isoDate;
        return `${parts[2]}.${parts[1]}.${parts[0]}`;
    };

    const setCiroError = (message) => {
        if (!ordersCiroError) return;
        if (!message) {
            ordersCiroError.textContent = '';
            ordersCiroError.classList.remove('is-visible');
            return;
        }
        ordersCiroError.textContent = message;
        ordersCiroError.classList.add('is-visible');
    };

    const setCiroLoading = (loading) => {
        if (ordersCiroCalcBtn) {
            ordersCiroCalcBtn.disabled = loading;
            ordersCiroCalcBtn.innerHTML = loading
                ? '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Hesaplanıyor...</span>'
                : '<i class="fa-solid fa-calculator" aria-hidden="true"></i><span>Hesapla</span>';
        }
        if (ordersCiroTotal && loading) {
            ordersCiroTotal.textContent = '...';
        }
    };

    const openCiroModal = () => {
        if (!ordersCiroModal) return;
        if (ordersCiroDate && !ordersCiroDate.value) {
            ordersCiroDate.value = getCiroDefaultDate();
        }
        ordersCiroModal.classList.add('is-open');
        ordersCiroModal.setAttribute('aria-hidden', 'false');
        setCiroError('');
    };

    const closeCiroModal = () => {
        if (!ordersCiroModal) return;
        ordersCiroModal.classList.remove('is-open');
        ordersCiroModal.setAttribute('aria-hidden', 'true');
    };

    const fetchDailyRevenue = async () => {
        if (!ordersDailyRevenueBtn || !ordersCiroDate?.value) {
            setCiroError('Lutfen tarih secin.');
            return;
        }

        setCiroError('');
        setCiroLoading(true);

        try {
            const params = new URLSearchParams();
            params.set('date', ordersCiroDate.value);
            const marketplaceInput = document.querySelector('input[name="marketplace_id"]');
            if (marketplaceInput?.value) {
                params.set('marketplace_id', marketplaceInput.value);
            }

            const requestUrl = `${ordersDailyRevenueBtn.dataset.url}?${params.toString()}`;
            const response = await fetch(requestUrl, {
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                let message = 'Ciro hesaplanamadi.';
                try {
                    const payload = await response.json();
                    if (payload?.message) {
                        message = payload.message;
                    } else if (payload?.errors) {
                        const firstError = Object.values(payload.errors).flat()[0];
                        if (firstError) message = String(firstError);
                    }
                } catch (_) {
                    // keep default message
                }
                throw new Error(message);
            }

            const payload = await response.json();
            if (ordersCiroDateLabel && ordersCiroDateText) {
                ordersCiroDateText.textContent = `Tarih: ${formatCiroDate(payload.date)}`;
            }
            if (ordersCiroTotal) {
                ordersCiroTotal.textContent = formatCiroCurrency(payload.total_amount, payload.currency);
            }
            if (ordersCiroCount && ordersCiroCountValue) {
                ordersCiroCountValue.textContent = String(payload.order_count ?? 0);
            }
        } catch (error) {
            setCiroError(error instanceof Error ? error.message : 'Ciro hesaplanamadi.');
            if (ordersCiroTotal) ordersCiroTotal.textContent = '-';
            if (ordersCiroCount && ordersCiroCountValue) ordersCiroCountValue.textContent = '0';
        } finally {
            setCiroLoading(false);
        }
    };

    const filterOrdersTable = (value) => {
        const query = (value || '').trim().toLocaleLowerCase('tr');
        const shouldFilter = query.length >= 2;

        ordersTableRows.forEach((row) => {
            if (row.matches('[data-orders-inline-row]')) {
                row.classList.add('is-hidden');
                row.style.display = '';
                return;
            }
            const isEmptyState = row.querySelector('td[colspan]');
            if (isEmptyState) {
                row.style.display = shouldFilter ? 'none' : '';
                return;
            }

            if (!shouldFilter) {
                row.style.display = '';
                return;
            }

            const text = (row.innerText || '').toLocaleLowerCase('tr');
            row.style.display = text.includes(query) ? '' : 'none';
        });
    };

    ordersInlineSearch?.addEventListener('input', (event) => {
        filterOrdersTable(event.target.value);
    });

    ordersDailyRevenueBtn?.addEventListener('click', async () => {
        if (ordersCiroDate) {
            ordersCiroDate.value = getCiroDefaultDate();
        }
        openCiroModal();
        await fetchDailyRevenue();
    });

    ordersCiroCalcBtn?.addEventListener('click', async () => {
        await fetchDailyRevenue();
    });

    ordersCiroDate?.addEventListener('change', async () => {
        await fetchDailyRevenue();
    });

    ordersCiroClose?.addEventListener('click', () => {
        closeCiroModal();
    });

    orderPopupLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const href = link.getAttribute('href');
            if (!href) return;
            const popupWidth = 1280;
            const initialPopupHeight = Math.min(700, Math.max(420, window.screen.availHeight - 200));
            const popupHeight = initialPopupHeight;
            const left = Math.max(0, Math.floor((window.screen.width - popupWidth) / 2));
            const top = Math.max(0, Math.floor((window.screen.height - popupHeight) / 2));
            const features = `width=${popupWidth},height=${popupHeight},left=${left},top=${top},resizable=yes,scrollbars=yes`;
            const popupWindow = window.open(href, 'orderDetailPopup', features);
            if (!popupWindow) return;

            const fitPopupToContent = () => {
                try {
                    if (popupWindow.closed) return true;
                    const popupDoc = popupWindow.document;
                    if (!popupDoc || popupDoc.readyState === 'loading') return false;

                    const chromeHeight = Math.max(0, popupWindow.outerHeight - popupWindow.innerHeight);
                    const contentHeight = Math.ceil(popupDoc.documentElement.scrollHeight + chromeHeight + 6);
                    const maxHeight = Math.max(420, window.screen.availHeight - 24);
                    const finalHeight = Math.min(Math.max(300, contentHeight), maxHeight);
                    popupWindow.resizeTo(popupWidth, finalHeight);
                    return true;
                } catch (e) {
                    return true;
                }
            };

            if (!fitPopupToContent()) {
                const timer = window.setInterval(() => {
                    if (fitPopupToContent()) {
                        window.clearInterval(timer);
                    }
                }, 120);
                window.setTimeout(() => window.clearInterval(timer), 5000);
            }
        });
    });

    const closeOrderInlineDetails = () => {
        document.querySelectorAll('[data-orders-inline-row]').forEach((row) => row.classList.add('is-hidden'));
        ordersDetailToggleButtons.forEach((btn) => {
            btn.setAttribute('aria-expanded', 'false');
            btn.setAttribute('title', 'Satır detayını aç');
            btn.setAttribute('aria-label', 'Satır detayını aç');
            const chevron = btn.querySelector('.orders-detail-chevron');
            if (chevron) {
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            }
        });
    };

    ordersDetailToggleButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const row = btn.closest('tr');
            const detailRow = row?.nextElementSibling;
            const tbody = row?.parentElement;
            if (!detailRow || !detailRow.matches('[data-orders-inline-row]') || !tbody) return;

            const isOpen = !detailRow.classList.contains('is-hidden');
            closeOrderInlineDetails();
            if (isOpen) return;

            detailRow.classList.remove('is-hidden');
            btn.setAttribute('aria-expanded', 'true');
            btn.setAttribute('title', 'Satır detayını gizle');
            btn.setAttribute('aria-label', 'Satır detayını gizle');
            const chevron = btn.querySelector('.orders-detail-chevron');
            if (chevron) {
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
            }
        });
    });

    ordersInlineCloseButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            closeOrderInlineDetails();
        });
    });

    ordersBulkStatus?.addEventListener('change', () => {
        ordersBulkStatus.setCustomValidity('');
        ordersBulkWarning?.classList.remove('is-visible');
    });

    ordersBulkUpdateForm?.addEventListener('submit', (event) => {
        const hasSelectedOrder = orderRowChecks.some((cb) => cb.checked);
        const hasStatus = Boolean(ordersBulkStatus?.value);

        if (!hasStatus || !hasSelectedOrder) {
            event.preventDefault();
            if (ordersBulkStatus && !hasStatus) {
                ordersBulkStatus.setCustomValidity('Lütfen listeden bir öğe seçin');
                ordersBulkStatus.reportValidity();
                ordersBulkStatus.focus();
            } else {
                if (ordersBulkWarning && ordersProcessBtn) {
                    const rect = ordersProcessBtn.getBoundingClientRect();
                    ordersBulkWarning.style.left = `${Math.round(rect.left + (rect.width / 2))}px`;
                    ordersBulkWarning.style.top = `${Math.max(12, Math.round(rect.top - 52))}px`;
                    ordersBulkWarning.style.transform = 'translateX(-50%)';
                }
                ordersBulkWarning?.classList.add('is-visible');
                window.clearTimeout(window.__ordersBulkWarnTimer);
                window.__ordersBulkWarnTimer = window.setTimeout(() => {
                    ordersBulkWarning?.classList.remove('is-visible');
                }, 2400);
            }
            return;
        }

        ordersBulkWarning?.classList.remove('is-visible');
        ordersBulkStatus?.setCustomValidity('');
    });

    const placeOrdersPopover = (event) => {
        if (!ordersImagePopover || !ordersImagePopoverImg) return;
        const offset = 16;
        const width = 220;
        const height = 220;
        let left = event.clientX + offset;
        let top = event.clientY + offset;

        if (left + width > window.innerWidth - 8) {
            left = event.clientX - width - offset;
        }
        if (top + height > window.innerHeight - 8) {
            top = event.clientY - height - offset;
        }

        ordersImagePopover.style.left = `${Math.max(8, left)}px`;
        ordersImagePopover.style.top = `${Math.max(8, top)}px`;
    };

    ordersImageTriggers.forEach((trigger) => {
        trigger.addEventListener('mouseenter', (event) => {
            const src = trigger.getAttribute('data-order-preview-src');
            if (!src || !ordersImagePopover || !ordersImagePopoverImg) return;
            ordersImagePopoverImg.src = src;
            ordersImagePopoverImg.alt = trigger.getAttribute('data-order-preview-alt') || 'Siparis gorseli';
            ordersImagePopover.classList.add('is-open');
            placeOrdersPopover(event);
        });

        trigger.addEventListener('mousemove', placeOrdersPopover);

        trigger.addEventListener('mouseleave', () => {
            ordersImagePopover?.classList.remove('is-open');
            if (ordersImagePopoverImg) {
                ordersImagePopoverImg.removeAttribute('src');
            }
        });
    });

</script>

@endsection


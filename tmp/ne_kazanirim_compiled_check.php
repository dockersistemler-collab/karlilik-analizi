

<?php $__env->startSection('header'); ?>
    Ne Kazanirim
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
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
            max-width: 100%;
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

        .nk-soft-input.nk-field-invalid,
        .nk-category-select-trigger.nk-field-invalid {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.14) !important;
            color: #b91c1c;
        }

        .nk-right-stack .nk-soft-input,
        .nk-right-stack .nk-soft-select,
        .nk-right-stack .nk-category-select-trigger {
            width: 100% !important;
            max-width: 100%;
            display: block;
            box-sizing: border-box;
        }

        .nk-category-select-wrap {
            position: relative;
            z-index: 20;
        }

        .nk-category-select-trigger {
            width: 100%;
            min-height: 48px;
            border: 1px solid #b6dff6;
            border-radius: 12px;
            background: #f8fafc;
            padding: 0 14px;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
            -webkit-tap-highlight-color: transparent;
            box-shadow: 0 0 0 0.2rem rgba(182, 223, 246, 0.4);
        }

        .nk-category-select-trigger:focus,
        .nk-category-select-trigger:focus-visible,
        .nk-category-select-trigger:active,
        .nk-category-select-wrap:focus-within .nk-category-select-trigger {
            outline: none;
            border-color: #b6dff6;
            box-shadow: 0 0 0 0.2rem rgba(182, 223, 246, 0.4);
            background: #f8fafc;
        }

        .nk-category-dropdown {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 8px);
            border: 1px solid #d1d5db;
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.14);
            z-index: 9999;
            isolation: isolate;
            overflow: hidden;
            display: none;
        }

        .nk-category-dropdown.is-open {
            display: block;
        }

        .nk-card.nk-card-elevated {
            position: relative;
            z-index: 120;
        }

        .nk-category-search {
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
            background: #ffffff;
        }

        .nk-category-search .nk-soft-input {
            min-height: 40px;
            background: #fff;
            width: 100% !important;
            max-width: 100%;
            display: block;
            box-sizing: border-box;
        }

        .nk-category-options {
            max-height: 260px;
            overflow-y: auto;
        }

        .nk-category-option {
            width: 100%;
            border: 0;
            background: #fff;
            text-align: left;
            padding: 10px 12px;
            cursor: pointer;
        }

        .nk-category-option:hover,
        .nk-category-option.is-active {
            background: #eff6ff;
        }

        .nk-category-option-title {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.25;
        }

        .nk-category-option-path {
            margin-top: 2px;
            font-size: 13px;
            color: #6b7280;
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

        .nk-result-label {
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            text-align: right;
            margin-bottom: 8px;
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
            width: 100%;
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

        /* Keep row/header checkboxes visible even with aggressive global checkbox styles. */
        .nk-check-col input[type="checkbox"] {
            appearance: auto !important;
            -webkit-appearance: checkbox !important;
            opacity: 1 !important;
            display: inline-block !important;
            visibility: visible !important;
            position: static !important;
            pointer-events: auto !important;
        }

        .nk-num-col {
            text-align: center;
            font-variant-numeric: tabular-nums;
        }

        .nk-detail-col {
            text-align: right;
        }

        .nk-table-wrap .nk-detail-col .nk-detail-btn,
        .nk-table-wrap .nk-detail-col button.nk-detail-btn,
        .nk-table-wrap .nk-detail-col a.nk-detail-btn {
            appearance: none !important;
            -webkit-appearance: none !important;
            border: 0 !important;
            border-radius: 10px !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            width: 90px !important;
            height: 28px !important;
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 1 !important;
            color: #ffffff !important;
            background-color: #f68b2e !important;
            background-image: none !important;
            box-shadow: none !important;
            white-space: nowrap !important;
            text-decoration: none !important;
            cursor: pointer !important;
            transition: filter .15s ease !important;
        }

        .nk-table-wrap .nk-detail-col .nk-detail-btn:hover {
            color: #ffffff !important;
            background-color: #eb7f22 !important;
            filter: none !important;
        }

        .nk-inline-detail-row > td {
            padding: 0 8px 12px;
            border-bottom: 1px solid #e2e8f0 !important;
            background: #f8fafc;
        }

        .nk-inline-detail-row.is-hidden {
            display: none;
        }

        .nk-inline-detail-panel {
            margin-top: 8px;
            border: 1px solid #dbe7ff;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 12px;
            opacity: 0;
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            transition: opacity .24s ease, transform .24s ease;
        }

        .nk-inline-detail-panel.is-open {
            opacity: 1;
            transform: translateY(0);
        }

        .nk-inline-detail-title {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
        }

        .nk-inline-detail-subtitle {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .nk-inline-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .nk-inline-summary-box {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #ffffff;
            padding: 10px 12px;
        }

        .nk-inline-summary-label {
            display: inline;
            margin-right: 4px;
            font-size: 11px;
            color: #64748b;
        }

        .nk-inline-summary-value {
            display: inline;
            margin-top: 0;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
        }

        .nk-inline-metrics {
            margin-top: 10px;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
        }

        .nk-inline-metric {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #ffffff;
            padding: 8px 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .nk-inline-metric:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
        }

        .nk-inline-metric-icon {
            width: 24px;
            height: 24px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: #0f172a;
            background: linear-gradient(135deg, #e2e8f0, #f8fafc);
            border: 1px solid #dbe2ea;
            flex: 0 0 24px;
        }

        .nk-inline-metric-content {
            min-width: 0;
        }

        .nk-inline-metric-content > span {
            display: inline;
            margin-bottom: 0;
            margin-right: 4px;
            font-size: 11px;
            color: #64748b;
        }

        .nk-inline-metric > span {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-bottom: 2px;
        }

        .nk-inline-metric > strong {
            font-size: 13px;
            color: #0f172a;
            font-weight: 700;
        }

        @media (max-width: 1100px) {
            .nk-inline-summary-grid {
                grid-template-columns: 1fr;
            }

            .nk-inline-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .nk-table-panel.is-hidden {
            display: none;
        }

        .nk-table-switch {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            margin-right: 10px;
            margin-bottom: 0;
        }

        .nk-table-switch input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .nk-table-switch-track {
            width: 56px;
            height: 28px;
            border-radius: 999px;
            border: 1px solid #f97316;
            background: #fff7ed;
            position: relative;
            cursor: pointer;
        }

        .nk-table-switch-track::after {
            content: "";
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #fb923c;
            transition: transform .18s ease;
        }

        .nk-table-switch input:checked + .nk-table-switch-track::after {
            transform: translateX(28px);
        }

        .nk-stock-table {
            width: 100% !important;
            min-width: 100% !important;
            table-layout: fixed;
            font-size: 13px;
        }

        .nk-stock-table thead th {
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .03em;
            font-size: 11px;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 8px;
            font-weight: 600;
            white-space: normal;
        }

        .nk-stock-table tbody td {
            border-bottom: 1px solid #f1f5f9;
            padding: 10px 8px;
            color: #475569;
            vertical-align: middle;
        }

        .nk-stock-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .nk-stock-alert-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #fecdd3;
            background: #fff1f2;
            color: #be123c;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
        }

        .nk-stock-thumb-wrap {
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

        .nk-stock-thumb {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 0.75rem;
        }

        .nk-stock-thumb-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background: #f1f5f9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .nk-stock-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            margin: 2px 4px 2px 0;
        }

        .nk-image-popover {
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

        .nk-image-popover.is-open {
            display: block;
        }

        .nk-image-popover img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #f8fafc;
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

        .nk-modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .nk-modal-close {
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 20px;
            line-height: 1;
            padding: 0;
            cursor: pointer;
        }

        .nk-modal-box strong {
            display: block;
            margin-top: 4px;
            font-size: 16px;
            color: #0f172a;
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
    <?php
        $formatCurrency = static function ($amount, $currency = null): string {
            if ($amount === null || $amount === '') {
                return '-';
            }

            $code = strtoupper(trim((string) ($currency ?: 'TRY')));
            $symbol = in_array($code, ['TRY', 'TL'], true) ? 'TL' : $code;

            return number_format((float) $amount, 2, ',', '.').' '.$symbol;
        };

        $formatPercent = static function ($value): string {
            if ($value === null || $value === '') {
                return '-';
            }

            return number_format((float) $value, 2, ',', '.');
        };

        $ramBatchImages = collect(glob(storage_path('app/public/products/ram-batch/*')) ?: [])
            ->filter(static fn (string $path): bool => is_file($path))
            ->map(static fn (string $path): string => '/storage/products/ram-batch/' . basename($path))
            ->values();

        $pickRamBatchImage = static function ($seed) use ($ramBatchImages): ?string {
            if ($ramBatchImages->isEmpty()) {
                return null;
            }

            $index = abs((int) crc32((string) $seed)) % $ramBatchImages->count();

            return $ramBatchImages[$index];
        };

        $normalizeSku = static function ($value): string {
            $raw = trim((string) ($value ?? ''));
            if ($raw === '') {
                return '';
            }

            $normalized = preg_replace('/\s+/', '', $raw) ?: $raw;

            return strtoupper($normalized);
        };

        $stockImageBySku = collect($stockProducts ?? [])
            ->filter(static fn ($product): bool => filled($product->display_image_url ?? null))
            ->mapWithKeys(static function ($product) use ($normalizeSku): array {
                $skuKey = $normalizeSku($product->sku ?? null);
                if ($skuKey === '') {
                    return [];
                }

                return [$skuKey => $product->display_image_url];
            });

        $platformServiceMap = [
            'trendyol' => (float) ($neKazanirimSettings['platform_service_amount_trendyol'] ?? 0),
            'hepsiburada' => (float) ($neKazanirimSettings['platform_service_amount_hepsiburada'] ?? 0),
            'n11' => (float) ($neKazanirimSettings['platform_service_amount_n11'] ?? 0),
            'amazon' => (float) ($neKazanirimSettings['platform_service_amount_amazon'] ?? 0),
            'ciceksepeti' => (float) ($neKazanirimSettings['platform_service_amount_ciceksepeti'] ?? 0),
        ];
        $withholdingRatePercentSetting = (float) ($neKazanirimSettings['withholding_rate_percent'] ?? 0);

        $resolvePlatformServiceAmount = static function ($marketplaceName = null) use ($platformServiceMap): float {
            $raw = trim((string) ($marketplaceName ?? ''));
            if ($raw === '') {
                return 0.0;
            }

            $normalized = function_exists('mb_strtolower')
                ? mb_strtolower($raw, 'UTF-8')
                : strtolower($raw);

            if (function_exists('iconv')) {
                $asciiNormalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $normalized);
                if (is_string($asciiNormalized) && $asciiNormalized !== '') {
                    $normalized = strtolower($asciiNormalized);
                }
            }

            $normalized = str_replace([' ', '-', '_'], '', $normalized);
            if (str_contains($normalized, 'hepsiburada')) {
                return (float) ($platformServiceMap['hepsiburada'] ?? 0);
            }
            if (str_contains($normalized, 'trendyol')) {
                return (float) ($platformServiceMap['trendyol'] ?? 0);
            }
            if (str_contains($normalized, 'n11')) {
                return (float) ($platformServiceMap['n11'] ?? 0);
            }
            if (str_contains($normalized, 'amazon')) {
                return (float) ($platformServiceMap['amazon'] ?? 0);
            }
            if (str_contains($normalized, 'ciceksepeti')) {
                return (float) ($platformServiceMap['ciceksepeti'] ?? 0);
            }

            return 0.0;
        };

    ?>

    <div class="nk-page">
        <div class="nk-layout">
            <div class="nk-left">
                <div class="nk-card h-100">
                    <div class="nk-card-header">
                        <div class="d-flex align-items-center">
                            <label class="nk-table-switch">
                                <span id="nk-stock-view-label">Sipari&#351; Hesaplama</span>
                                <input type="checkbox" id="nk-stock-view-toggle">
                                <span class="nk-table-switch-track" aria-hidden="true"></span>
                            </label>
                        </div>
                        <span>Tablo / Urunler</span>
                    </div>
                    <div class="card-body">
                        <div class="nk-table-wrap nk-table-panel" id="nk-profit-table-panel">
                            <table class="nk-profit-table">
                                <thead>
                                    <tr>
                                        <th class="nk-check-col">
                                            <input type="checkbox" id="nk-select-all">
                                        </th>
                                        <th class="text-left">Gorsel</th>
                                        <th class="text-left">Pazaryeri</th>
                                        <th class="text-left">Siparis Numarasi</th>
                                        <th class="text-left">TAR&#304;H</th>
                                        <th class="nk-num-col">Siparis Tutari</th>
                                        <th class="nk-num-col">Kar Tutari</th>
                                        <th class="nk-num-col">Kar Orani</th>
                                        <th class="nk-num-col">Kar Marji</th>
                                        <th class="nk-detail-col">Detay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $orderRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <?php
                                            $reportRow = is_array($row->_profitability_report ?? null) ? $row->_profitability_report : [];
                                            $breakdown = json_decode((string) ($reportRow['breakdown'] ?? null), true) ?: [];
                                            $marketplaceName = $reportRow['marketplace_name'] ?? ($row->marketplace?->name ?: '-');
                                            $platformFeeAmount = $resolvePlatformServiceAmount((string) $marketplaceName);
                                            $orderNo = $reportRow['order_number'] ?? ($row->marketplace_order_id ?: ($row->order_number ?: ('ORD-'.$row->id)));
                                            $orderDate = $row->order_date?->isoFormat('D MMM YYYY') ?: '-';
                                            $salePriceRaw = (float) (array_key_exists('sale_price', $reportRow)
                                                ? $reportRow['sale_price']
                                                : ($row->total_amount ?? 0));
                                            $withholdingTaxBreakdown = (float) ($breakdown['withholding_tax_amount'] ?? 0);
                                            $withholdingTaxAmount = $withholdingTaxBreakdown > 0
                                                ? $withholdingTaxBreakdown
                                                : ($salePriceRaw * $withholdingRatePercentSetting / 100);
                                            $salePrice = number_format($salePriceRaw, 2, ',', '.') . ' ₺';
                                            $profitAmountRaw = array_key_exists('profit_amount', $reportRow)
                                                ? $reportRow['profit_amount']
                                                : ($row->net_amount !== null ? $row->net_amount : null);
                                            $profitAmount = $formatCurrency($profitAmountRaw, $row->currency);
                                            $profitRateRaw = array_key_exists('profit_markup_percent', $reportRow)
                                                ? $reportRow['profit_markup_percent']
                                                : (((float) ($row->total_amount ?? 0)) > 0 && $profitAmountRaw !== null
                                                    ? ((float) $profitAmountRaw / (float) $row->total_amount) * 100
                                                    : null);
                                            $profitMarginRaw = array_key_exists('profit_margin_percent', $reportRow)
                                                ? $reportRow['profit_margin_percent']
                                                : $profitRateRaw;
                                            $profitRate = $formatPercent($profitRateRaw);
                                            $profitMargin = $formatPercent($profitMarginRaw);
                                            $negative = $profitAmountRaw !== null && (float) $profitAmountRaw < 0;
                                            $orderItems = is_array($row->items)
                                                ? $row->items
                                                : (is_string($row->items) ? (json_decode($row->items, true) ?: []) : []);
                                            $firstItem = [];
                                            if (is_array($orderItems)) {
                                                $firstItem = array_is_list($orderItems)
                                                    ? ($orderItems[0] ?? [])
                                                    : $orderItems;
                                            }
                                            $itemSku = $reportRow['sku']
                                                ?? data_get($firstItem, 'sku')
                                                ?? data_get($firstItem, 'merchant_sku')
                                                ?? data_get($firstItem, 'barcode')
                                                ?? '';
                                            $mappedOrderImageUrl = $stockImageBySku->get($normalizeSku($itemSku));
                                            $orderImageUrl = ($reportRow['image_url'] ?? null)
                                                ?: ($reportRow['product_image_url'] ?? null)
                                                ?: ($reportRow['thumbnail_url'] ?? null)
                                                ?: data_get($firstItem, 'image_url')
                                                ?: data_get($firstItem, 'image')
                                                ?: data_get($firstItem, 'product_image')
                                                ?: $mappedOrderImageUrl
                                                ?: $pickRamBatchImage($orderNo ?: $row->id);
                                            $orderImageFallbackUrl = $pickRamBatchImage('fallback|'.($orderNo ?: $row->id));
                                        ?>
                                        <tr>
                                            <td class="nk-check-col">
                                                <input type="checkbox" class="nk-row-check" name="selected_rows[]" value="<?php echo e($index); ?>">
                                            </td>
                                            <td>
                                                <?php if($orderImageUrl): ?>
                                                    <span class="nk-stock-thumb-wrap" data-nk-preview-src="<?php echo e($orderImageUrl); ?>" data-nk-preview-alt="<?php echo e($orderNo); ?>">
                                                        <img
                                                            src="<?php echo e($orderImageUrl); ?>"
                                                            alt="<?php echo e($orderNo); ?>"
                                                            class="nk-stock-thumb"
                                                            data-fallback-src="<?php echo e($orderImageFallbackUrl); ?>"
                                                            onerror="if(this.dataset.fallbackApplied==='1'){return;}const fb=this.getAttribute('data-fallback-src');if(fb){this.dataset.fallbackApplied='1';this.src=fb;}"
                                                        >
                                                    </span>
                                                <?php else: ?>
                                                    <span class="nk-stock-thumb-placeholder"><i class="fas fa-image text-slate-400"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($marketplaceName); ?></td>
                                            <td><?php echo e($orderNo); ?></td>
                                            <td><?php echo e($orderDate); ?></td>
                                            <td class="nk-num-col"><?php echo e($salePrice); ?></td>
                                            <td class="nk-num-col <?php echo e($negative ? 'nk-negative' : 'nk-positive'); ?>"><?php echo e($profitAmount); ?></td>
                                            <td class="nk-num-col <?php echo e($negative ? 'nk-negative' : 'nk-positive'); ?>"><?php echo e($profitRate); ?></td>
                                            <td class="nk-num-col <?php echo e($negative ? 'nk-negative' : 'nk-positive'); ?>"><?php echo e($profitMargin); ?></td>
                                            <td class="nk-detail-col">
                                                <button
                                                    type="button"
                                                    class="nk-detail-btn"
                                                    data-nk-detail
                                                    data-nk-detail-type="profit"
                                                    data-order-number="<?php echo e($orderNo); ?>"
                                                    data-sale-price="<?php echo e($salePrice); ?>"
                                                    data-profit-amount="<?php echo e($profitAmount); ?>"
                                                    data-product-cost="<?php echo e(number_format((float) ($breakdown['product_cost'] ?? 0), 2, ',', '.')); ?> ₺"
                                                    data-commission="<?php echo e(number_format((float) ($breakdown['commission_amount'] ?? 0), 2, ',', '.')); ?> ₺"
                                                    data-shipping-fee="<?php echo e(number_format((float) ($breakdown['shipping_fee'] ?? 0), 2, ',', '.')); ?> ₺"
                                                    data-platform-fee="<?php echo e(number_format($platformFeeAmount, 2, ',', '.')); ?> ₺"
                                                    data-refund-adjustment="<?php echo e(number_format((float) ($breakdown['refunds_shipping_adjustment'] ?? 0), 2, ',', '.')); ?> ₺"
                                                    data-withholding-tax="<?php echo e(number_format($withholdingTaxAmount, 2, ',', '.')); ?> ₺"
                                                    data-sales-vat="<?php echo e(number_format((float) ($breakdown['sales_vat_amount'] ?? 0), 2, ',', '.')); ?> ₺"
                                                    data-vat-rate="<?php echo e(number_format((float) ($breakdown['vat_rate_percent'] ?? 0), 2, ',', '.')); ?> %"
                                                >Detayi Gor</button>
                                            </td>
                                        </tr>
                                        <tr class="nk-inline-detail-row is-hidden" data-nk-inline-row>
                                            <td colspan="11">
                                                <div class="nk-inline-detail-panel" data-nk-inline-panel>
                                                    <div class="nk-inline-detail-title" data-nk-inline-title>Siparis Karlilik Detayi</div>
                                                    <div class="nk-inline-detail-subtitle" data-nk-inline-subtitle>-</div>

                                                    <div class="nk-inline-summary-grid">
                                                        <div class="nk-inline-summary-box">
                                                            <div class="nk-inline-summary-label" data-nk-inline-box1-label>Siparis Tutari</div>
                                                            <div class="nk-inline-summary-value" data-nk-inline-box1>-</div>
                                                        </div>
                                                        <div class="nk-inline-summary-box">
                                                            <div class="nk-inline-summary-label" data-nk-inline-box2-label>Kar Tutari</div>
                                                            <div class="nk-inline-summary-value" data-nk-inline-box2>-</div>
                                                        </div>
                                                    </div>

                                                    <div class="nk-inline-metrics">
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-box nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-1>Urun Maliyeti:</span><strong data-nk-inline-line-1>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-percent nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-2>Komisyon:</span><strong data-nk-inline-line-2>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-truck nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-3>Kargo:</span><strong data-nk-inline-line-3>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-gears nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-4>Platform Hizmeti:</span><strong data-nk-inline-line-4>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-rotate-left nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-5>Iade Kargo Duzeltmesi:</span><strong data-nk-inline-line-5>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-file-invoice-dollar nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-6>Stopaj:</span><strong data-nk-inline-line-6>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-receipt nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-7>Satis KDV:</span><strong data-nk-inline-line-7>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-chart-line nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-8>KDV Orani:</span><strong data-nk-inline-line-8>-</strong></div></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">Siparis bulunamadi.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="nk-table-wrap nk-table-panel is-hidden" id="nk-stock-table-panel">
                            <table class="nk-stock-table">
                                <thead>
                                    <tr>
                                        <th class="nk-check-col">
                                            <input type="checkbox" id="nk-stock-select-all">
                                        </th>
                                        <th class="text-left">Gorsel</th>
                                        <th class="text-left">Marka</th>
                                        <th class="text-left">SKU</th>
                                        <th class="text-left">Urun</th>
                                        <th class="text-left">Maliyet</th>
                                        <th class="text-left">Fiyat</th>
                                        <th class="text-left">Stok</th>
                                        <th class="text-left">Kritik Seviye</th>
                                        <th class="text-left">Uyari</th>
                                        <th class="nk-detail-col">Detay</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__empty_1 = true; $__currentLoopData = $stockProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stockProduct): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                        <?php
                                            $isCritical = (int) $stockProduct->stock_quantity <= (int) $stockProduct->critical_stock_level;
                                            $hasAlert = isset($activeAlertProductIds[$stockProduct->id]);
                                            $marketplaceNames = $stockProduct->marketplaceProducts
                                                ->pluck('marketplace')
                                                ->filter()
                                                ->unique('id')
                                                ->pluck('name')
                                                ->filter()
                                                ->values();
                                            $marketplaceText = $marketplaceNames->isNotEmpty() ? $marketplaceNames->join(', ') : '-';
                                            $alertText = $hasAlert ? 'Kritik Uyari' : '-';
                                            $stockStatus = $isCritical ? 'Kritik' : 'Normal';
                                            $stockImageUrl = $stockProduct->display_image_url
                                                ?: $pickRamBatchImage(($stockProduct->sku ?: $stockProduct->id).'|'.$stockProduct->name);
                                        ?>
                                        <tr>
                                            <td class="nk-check-col">
                                                <input type="checkbox" class="nk-stock-row-check" name="selected_stock_rows[]" value="<?php echo e($stockProduct->id); ?>">
                                            </td>
                                            <td>
                                                <?php if($stockImageUrl): ?>
                                                    <span class="nk-stock-thumb-wrap" data-nk-preview-src="<?php echo e($stockImageUrl); ?>" data-nk-preview-alt="<?php echo e($stockProduct->name); ?>">
                                                        <img src="<?php echo e($stockImageUrl); ?>" alt="<?php echo e($stockProduct->name); ?>" class="nk-stock-thumb">
                                                    </span>
                                                <?php else: ?>
                                                    <span class="nk-stock-thumb-placeholder"><i class="fas fa-image text-slate-400"></i></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($stockProduct->brand ?: '-'); ?></td>
                                            <td><?php echo e($stockProduct->sku ?: '-'); ?></td>
                                            <td class="fw-semibold"><?php echo e($stockProduct->name); ?></td>
                                            <td><?php echo e(number_format((float) ($stockProduct->cost_price ?? 0), 2, ',', '.')); ?> <?php echo e($stockProduct->currency ?: 'TRY'); ?></td>
                                            <td><?php echo e(number_format((float) $stockProduct->price, 2, ',', '.')); ?> <?php echo e($stockProduct->currency ?: 'TRY'); ?></td>
                                            <td class="<?php echo e($isCritical ? 'nk-negative fw-semibold' : ''); ?>"><?php echo e($stockProduct->stock_quantity); ?></td>
                                            <td><?php echo e($stockProduct->critical_stock_level); ?></td>
                                            <td>
                                                <?php if($hasAlert): ?>
                                                    <span class="nk-stock-alert-badge">Kritik Uyari</span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="nk-detail-col">
                                                <button
                                                    type="button"
                                                    class="nk-detail-btn"
                                                    data-nk-detail
                                                    data-nk-detail-type="stock"
                                                    data-product-name="<?php echo e($stockProduct->name); ?>"
                                                    data-stock-quantity="<?php echo e($stockProduct->stock_quantity); ?>"
                                                    data-brand="<?php echo e($stockProduct->brand ?: '-'); ?>"
                                                    data-sku="<?php echo e($stockProduct->sku ?: '-'); ?>"
                                                    data-price="<?php echo e(number_format((float) $stockProduct->price, 2, ',', '.')); ?> <?php echo e($stockProduct->currency ?: 'TRY'); ?>"
                                                    data-marketplaces="<?php echo e($marketplaceText); ?>"
                                                    data-critical-level="<?php echo e($stockProduct->critical_stock_level); ?>"
                                                    data-alert="<?php echo e($alertText); ?>"
                                                    data-status="<?php echo e($stockStatus); ?>"
                                                >Detayi Gor</button>
                                            </td>
                                        </tr>
                                        <tr class="nk-inline-detail-row is-hidden" data-nk-inline-row>
                                            <td colspan="11">
                                                <div class="nk-inline-detail-panel" data-nk-inline-panel>
                                                    <div class="nk-inline-detail-title" data-nk-inline-title>Urun Stok Detayi</div>
                                                    <div class="nk-inline-detail-subtitle" data-nk-inline-subtitle>-</div>

                                                    <div class="nk-inline-summary-grid">
                                                        <div class="nk-inline-summary-box">
                                                            <div class="nk-inline-summary-label" data-nk-inline-box1-label>Urun</div>
                                                            <div class="nk-inline-summary-value" data-nk-inline-box1>-</div>
                                                        </div>
                                                        <div class="nk-inline-summary-box">
                                                            <div class="nk-inline-summary-label" data-nk-inline-box2-label>Stok</div>
                                                            <div class="nk-inline-summary-value" data-nk-inline-box2>-</div>
                                                        </div>
                                                    </div>

                                                    <div class="nk-inline-metrics">
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-tag nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-1>Marka:</span><strong data-nk-inline-line-1>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-barcode nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-2>SKU:</span><strong data-nk-inline-line-2>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-coins nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-3>Fiyat:</span><strong data-nk-inline-line-3>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-store nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-4>Pazaryeri:</span><strong data-nk-inline-line-4>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-gauge-high nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-5>Kritik Seviye:</span><strong data-nk-inline-line-5>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-triangle-exclamation nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-6>Uyari:</span><strong data-nk-inline-line-6>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-circle-check nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-7>Durum:</span><strong data-nk-inline-line-7>-</strong></div></div>
                                                        <div class="nk-inline-metric"><i class="fa-solid fa-minus nk-inline-metric-icon"></i><div class="nk-inline-metric-content"><span data-nk-inline-label-8>-</span><strong data-nk-inline-line-8>-</strong></div></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                        <tr>
                                            <td colspan="11" class="text-center text-muted py-4">Stok menusunde gosterilecek urun bulunamadi.</td>
                                        </tr>
                                    <?php endif; ?>
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
                            <span>Maliyet</span>
                            <i class="fa-regular fa-circle-question nk-gear"></i>
                        </div>
                        <div class="card-body">
                            <input type="number" step="0.01" min="0" class="form-control nk-soft-input" id="product_cost" name="product_cost" placeholder="Urun Maliyeti - TL 0">
                        </div>
                    </div>

                    <div class="nk-card">
                        <div class="nk-card-header">
                            <span>Ongorulen Kar/Tutar Oranı</span>
                            <i class="fa-regular fa-circle-question nk-gear"></i>
                        </div>
                        <div class="card-body">
                            <div class="nk-profit-row mb-3">
                                <label class="nk-kdv-item">
                                    <input class="nk-kdv-radio" type="radio" name="profit_target_type" value="amount" checked>
                                    <span class="nk-kdv-toggle" aria-hidden="true"></span>
                                    <span>Tutara Gore (TL)</span>
                                </label>
                                <label class="nk-kdv-item">
                                    <input class="nk-kdv-radio" type="radio" name="profit_target_type" value="rate">
                                    <span class="nk-kdv-toggle" aria-hidden="true"></span>
                                    <span>Orana Gore (%)</span>
                                </label>
                            </div>
                            <input type="number" step="0.01" min="0" class="form-control nk-soft-input" id="profit_target_value" name="profit_target_value" placeholder="Ongorulen Kar - TL 0">
                        </div>
                    </div>

                    <div class="nk-card">
                        <div class="nk-card-header">
                            <span>Kargo Ucreti</span>
                            <i class="fa-regular fa-circle-question nk-gear"></i>
                        </div>
                        <div class="card-body">
                            <input type="number" step="0.01" min="0" class="form-control nk-soft-input" id="shipping_fee" name="shipping_fee" placeholder="Kargo Ucreti - TL 0">
                        </div>
                    </div>

                    <div class="nk-card">
                        <div class="nk-card-header">
                            <span>Pazaryeri</span>
                            <i class="fa-regular fa-circle-question nk-gear"></i>
                        </div>
                        <div class="card-body">
                            <select class="form-control nk-soft-input" id="marketplace_platform" name="marketplace_platform">
                                <option value="trendyol" selected>Trendyol</option>
                                <option value="hepsiburada">Hepsiburada</option>
                                <option value="n11">N11</option>
                                <option value="amazon">Amazon</option>
                                <option value="ciceksepeti">&Ccedil;i&ccedil;ek Sepeti</option>
                            </select>
                        </div>
                    </div>

                    <div class="nk-card">
                        <div class="nk-card-header">
                            <span>Kategori Oranlari</span>
                            <i class="fa-regular fa-circle-question nk-gear"></i>
                        </div>
                        <div class="card-body">
                            <div class="nk-category-select-wrap mb-3">
                                <input type="hidden" id="category_name" name="category_name" value="">
                                <button type="button" id="category_select_trigger" class="nk-category-select-trigger" aria-expanded="false">
                                    <span id="category_select_label">Kategori Secin</span>
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </button>
                                <div id="category_dropdown" class="nk-category-dropdown" role="listbox" aria-label="Kategori listesi">
                                    <div class="nk-category-search">
                                        <input type="text" id="category_search_input" class="form-control nk-soft-input" placeholder="Kategori ara...">
                                    </div>
                                    <div id="category_options" class="nk-category-options"></div>
                                </div>
                            </div>

                            <input type="number" step="0.01" min="0" max="99.99" class="form-control nk-soft-input" id="commission_rate" name="commission_rate" placeholder="Komisyon Orani (%)">
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
                            <input type="number" step="0.01" min="0" class="form-control nk-soft-input" id="vat_rate_custom" name="vat_rate_custom" placeholder="KDV Orani (%)">
                        </div>
                    </div>

                    <div class="nk-card">
                        <div class="card-body">
                            <div class="nk-result-label" id="result_label" hidden>On Gorulen Satis Fiyati</div>
                            <div class="nk-result" id="result_amount">&#8378; 0</div>
                            <button type="button" class="btn nk-action w-100" id="calculate_button" name="calculate_button">
                                <i class="fa-regular fa-tag me-2"></i>Satis Fiyati Olustur
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="nk-image-popover" class="nk-image-popover" aria-hidden="true">
        <img id="nk-image-popover-img" src="" alt="">
    </div>

    <div id="nk-stock-calc-modal" class="nk-modal" aria-hidden="true">
        <div class="nk-modal-backdrop" data-nk-modal-close></div>
        <div class="nk-modal-dialog">
            <div class="nk-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <p class="nk-modal-title">Stok Hesaplama Ozeti</p>
                        <button type="button" class="nk-modal-close" data-nk-modal-close aria-label="Kapat">&times;</button>
                    </div>

                    <div class="nk-modal-grid">
                        <div class="nk-modal-box">
                            <div>Komisyon</div>
                            <strong id="nk-modal-commission">&#8378; 0</strong>
                        </div>
                        <div class="nk-modal-box">
                            <div>Hizmet Bedeli</div>
                            <strong id="nk-modal-service-fee">&#8378; 0</strong>
                        </div>
                        <div class="nk-modal-box">
                            <div>Kargo Ucreti</div>
                            <strong id="nk-modal-shipping-fee">&#8378; 0</strong>
                        </div>
                        <div class="nk-modal-box">
                            <div>Stopaj Kesintisi</div>
                            <strong id="nk-modal-withholding-tax">&#8378; 0</strong>
                        </div>
                    </div>

                    <div class="nk-modal-line">
                        <span>Ek Hizmet Bedeli</span>
                        <strong id="nk-modal-extra-service-fee">&#8378; 0</strong>
                    </div>
                    <div class="nk-modal-line">
                        <span>Platform Hizmeti</span>
                        <strong id="nk-modal-platform-service-fee">&#8378; 0</strong>
                    </div>
                    <div class="nk-modal-line">
                        <span>Platform</span>
                        <strong id="nk-modal-platform-name">Trendyol</strong>
                    </div>

                    <div class="nk-modal-line">
                        <span>Urun Maliyeti</span>
                        <strong id="nk-modal-product-cost">&#8378; 0</strong>
                    </div>
                    <div class="nk-modal-line">
                        <span>Hedef Kar</span>
                        <strong id="nk-modal-target-profit">&#8378; 0</strong>
                    </div>
                    <div class="nk-modal-line">
                        <span>KDV Orani</span>
                        <strong id="nk-modal-vat-rate">0 %</strong>
                    </div>
                    <div class="nk-modal-line">
                        <span>Hizmet Bedeli Kademesi</span>
                        <strong id="nk-modal-service-bracket">-</strong>
                    </div>
                    <div class="nk-modal-line">
                        <span>Stopaj Orani</span>
                        <strong id="nk-modal-withholding-rate">1 %</strong>
                    </div>
                    <div class="nk-modal-line border-top pt-2 mt-2">
                        <span>Onerilen Satis Fiyati</span>
                        <strong id="nk-modal-gross-amount">&#8378; 0</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <?php
        $categoryJsonPath = resource_path('data/trendyol_kategori_komisyon_oranlari.json');
        $categoryJson = file_exists($categoryJsonPath) ? file_get_contents($categoryJsonPath) : '[]';
        $categoryOptions = json_decode($categoryJson, true);
        if (!is_array($categoryOptions)) {
            $categoryOptions = [];
        }
    ?>
    <script>
        (function () {
            const neKazanirimSettings = <?php echo json_encode($neKazanirimSettings ?? [], 15, 512) ?>;
            const serviceFeeBrackets = Array.isArray(neKazanirimSettings.service_fee_brackets)
                ? neKazanirimSettings.service_fee_brackets
                : [];
            const withholdingRatePercent = Number.isFinite(Number.parseFloat(neKazanirimSettings.withholding_rate_percent))
                ? Number.parseFloat(neKazanirimSettings.withholding_rate_percent)
                : 1;
            const extraServiceFeeAmount = Number.isFinite(Number.parseFloat(neKazanirimSettings.extra_service_fee_amount))
                ? Number.parseFloat(neKazanirimSettings.extra_service_fee_amount)
                : 0;
            const platformServiceAmounts = {
                trendyol: Number.isFinite(Number.parseFloat(neKazanirimSettings.platform_service_amount_trendyol))
                    ? Number.parseFloat(neKazanirimSettings.platform_service_amount_trendyol)
                    : 0,
                hepsiburada: Number.isFinite(Number.parseFloat(neKazanirimSettings.platform_service_amount_hepsiburada))
                    ? Number.parseFloat(neKazanirimSettings.platform_service_amount_hepsiburada)
                    : 0,
                n11: Number.isFinite(Number.parseFloat(neKazanirimSettings.platform_service_amount_n11))
                    ? Number.parseFloat(neKazanirimSettings.platform_service_amount_n11)
                    : 0,
                amazon: Number.isFinite(Number.parseFloat(neKazanirimSettings.platform_service_amount_amazon))
                    ? Number.parseFloat(neKazanirimSettings.platform_service_amount_amazon)
                    : 0,
                ciceksepeti: Number.isFinite(Number.parseFloat(neKazanirimSettings.platform_service_amount_ciceksepeti))
                    ? Number.parseFloat(neKazanirimSettings.platform_service_amount_ciceksepeti)
                    : 0,
            };

            const selectAll = document.getElementById('nk-select-all');
            const stockSelectAll = document.getElementById('nk-stock-select-all');
            const bindSelectAll = () => {
                if (!selectAll) {
                    return;
                }

                selectAll.onchange = () => {
                    const rowChecks = Array.from(document.querySelectorAll('.nk-row-check'));
                    rowChecks.forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                };
            };

            const bindStockSelectAll = () => {
                if (!stockSelectAll) {
                    return;
                }

                stockSelectAll.onchange = () => {
                    const rowChecks = Array.from(document.querySelectorAll('.nk-stock-row-check'));
                    rowChecks.forEach((checkbox) => {
                        checkbox.checked = stockSelectAll.checked;
                    });
                };
            };

            bindSelectAll();
            bindStockSelectAll();

            const productCostInput = document.getElementById('product_cost');
            const profitTargetValueInput = document.getElementById('profit_target_value');
            const shippingFeeInput = document.getElementById('shipping_fee');
            const commissionRateInput = document.getElementById('commission_rate');
            const vatRateCustomInput = document.getElementById('vat_rate_custom');
            const vatRateChipInputs = Array.from(document.querySelectorAll('input[name="vat_rate_chip"]'));
            const profitTypeInputs = Array.from(document.querySelectorAll('input[name="profit_target_type"]'));
            const calculateButton = document.getElementById('calculate_button');
            const resultLabelEl = document.getElementById('result_label');
            const resultAmountEl = document.getElementById('result_amount');
            const categoryNameInput = document.getElementById('category_name');
            const categorySelectTrigger = document.getElementById('category_select_trigger');
            const categorySelectLabel = document.getElementById('category_select_label');
            const categoryDropdown = document.getElementById('category_dropdown');
            const categorySearchInput = document.getElementById('category_search_input');
            const categoryOptionsWrap = document.getElementById('category_options');
            const categoryCard = categorySelectTrigger?? ;
            const stockCalcModal = document.getElementById('nk-stock-calc-modal');
            const marketplacePlatformSelect = document.getElementById('marketplace_platform');

            const categoryCatalog = <?php echo json_encode($categoryOptions, 15, 512) ?>;
            const sortedCategoryCatalog = [...categoryCatalog].sort((a, b) => {
                const aText = `${a?? ;
                const bText = `${b?? ;
                return aText.localeCompare(bText, 'tr', { sensitivity: 'base' });
            });

            let selectedCategoryId = '';
            const defaultCategoryLabelText = 'Kategori Secin';

            const markInputInvalid = (input) => {
                if (!input) return;
                input.classList.add('nk-field-invalid');
            };

            const clearInputInvalid = (input) => {
                if (!input) return;
                input.classList.remove('nk-field-invalid');
                const defaultPlaceholder = input.dataset.defaultPlaceholder;
                if (defaultPlaceholder) {
                    input.placeholder = defaultPlaceholder;
                }
            };

            const markCategoryInvalid = () => {
                if (!categorySelectTrigger) return;
                categorySelectTrigger.classList.add('nk-field-invalid');
            };

            const clearCategoryInvalid = () => {
                if (!categorySelectTrigger || !categorySelectLabel) return;
                categorySelectTrigger.classList.remove('nk-field-invalid');
                if (!String(categoryNameInput?? ).trim()) {
                    categorySelectLabel.textContent = defaultCategoryLabelText;
                }
            };

            const openCategoryDropdown = () => {
                if (!categoryDropdown || !categorySelectTrigger) return;
                categoryDropdown.classList.add('is-open');
                categorySelectTrigger.setAttribute('aria-expanded', 'true');
                categoryCard?? ;
                categorySearchInput?? ;
            };

            const closeCategoryDropdown = () => {
                if (!categoryDropdown || !categorySelectTrigger) return;
                categoryDropdown.classList.remove('is-open');
                categorySelectTrigger.setAttribute('aria-expanded', 'false');
                categoryCard?? ;
            };

            const renderCategoryOptions = (query = '') => {
                if (!categoryOptionsWrap) return;
                const q = query.trim().toLocaleLowerCase('tr');
                const filtered = sortedCategoryCatalog.filter((item) => {
                    const haystack = `${item.name || ''} ${item.path || ''}`.toLocaleLowerCase('tr');
                    return q === '' || haystack.includes(q);
                });

                const resetActiveClass = !String(selectedCategoryId || '').trim() ? 'is-active' : '';
                const resetOptionHtml = `
                    <button type="button" class="nk-category-option ${resetActiveClass}" data-category-id="">
                        <div class="nk-category-option-title">${defaultCategoryLabelText}</div>
                        <div class="nk-category-option-path">Kategori secimini temizle</div>
                    </button>
                `;

                const listHtml = filtered.map((item) => {
                    const activeClass = item.id === selectedCategoryId ? 'is-active' : '';
                    const title = item.name || '-';
                    const path = item.path || '';
                    return `
                        <button type="button" class="nk-category-option ${activeClass}" data-category-id="${item.id}">
                            <div class="nk-category-option-title">${title}</div>
                            <div class="nk-category-option-path">${path}</div>
                        </button>
                    `;
                }).join('');

                if (filtered.length === 0) {
                    categoryOptionsWrap.innerHTML = `
                        ${resetOptionHtml}
                        <div class="px-3 py-3 text-sm text-slate-500">Kategori bulunamadi.</div>
                    `;
                } else {
                    categoryOptionsWrap.innerHTML = `${resetOptionHtml}${listHtml}`;
                }

                categoryOptionsWrap.querySelectorAll('.nk-category-option').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const categoryId = btn.getAttribute('data-category-id') || '';
                        if (!String(categoryId).trim()) {
                            selectedCategoryId = '';
                            if (categoryNameInput) {
                                categoryNameInput.value = '';
                            }
                            if (categorySelectLabel) {
                                categorySelectLabel.textContent = defaultCategoryLabelText;
                            }
                            clearCategoryInvalid();
                            closeCategoryDropdown();
                            renderCategoryOptions(categorySearchInput?? ;
                            return;
                        }

                        const selected = sortedCategoryCatalog.find((item) => String(item.id) === String(categoryId));
                        if (!selected) return;

                        selectedCategoryId = selected.id;
                        if (categoryNameInput) {
                            categoryNameInput.value = selected.name || '';
                        }
                        if (categorySelectLabel) {
                            categorySelectLabel.textContent = selected.path || selected.name || defaultCategoryLabelText;
                        }
                        if (commissionRateInput && selected.commissionRate !== null && selected.commissionRate !== undefined) {
                            commissionRateInput.value = String(selected.commissionRate);
                        }
                        clearCategoryInvalid();

                        closeCategoryDropdown();
                        renderCategoryOptions(categorySearchInput?? ;
                    });
                });
            };

            const parseDecimal = (value) => {
                if (value === null || value === undefined) return 0;
                const normalized = String(value).replace(',', '.').trim();
                const parsed = Number.parseFloat(normalized);
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const formatTl = (value) => {
                const safe = Number.isFinite(value) ? value : 0;
                return `₺ ${safe.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            };

            const formatPercent = (value) => {
                const safe = Number.isFinite(value) ? value : 0;
                return `${safe.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} %`;
            };

            const resolveServiceFee = (grossAmount) => {
                if (!Number.isFinite(grossAmount) || grossAmount < 0 || !serviceFeeBrackets.length) {
                    return { fee: 0, label: '-' };
                }

                const found = serviceFeeBrackets.find((row) => {
                    const min = Math.max(0, parseDecimal(row?? ;
                    const max = row?.max === null || row?.max === '' || row?.max === undefined
                        ? null
                        : parseDecimal(row.max);
                    if (grossAmount < min) {
                        return false;
                    }

                    return max === null || grossAmount <= max;
                });

                if (!found) {
                    return { fee: 0, label: '-' };
                }

                const min = Math.max(0, parseDecimal(found.min));
                const max = found.max === null || found.max === '' || found.max === undefined
                    ? null
                    : parseDecimal(found.max);
                const fee = Math.max(0, parseDecimal(found.fee));
                const minText = min.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                const maxText = max === null
                    ? 've uzeri'
                    : max.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                return {
                    fee,
                    label: max === null ? `${minText} TL ve uzeri` : `${minText} - ${maxText} TL`,
                };
            };

            const getSelectedProfitType = () => {
                const selected = profitTypeInputs.find((input) => input.checked);
                return selected?.value === 'rate' ? 'rate' : 'amount';
            };

            const syncProfitTargetPlaceholder = () => {
                if (!profitTargetValueInput) return;
                const nextPlaceholder = getSelectedProfitType() === 'rate'
                    ?? )'
                    : 'Ongorulen Kar - TL 0';
                profitTargetValueInput.dataset.defaultPlaceholder = nextPlaceholder;
                if (!profitTargetValueInput.classList.contains('nk-field-invalid')) {
                    profitTargetValueInput.placeholder = nextPlaceholder;
                }
            };

            const calculateSalePrice = () => {
                const requiredInputs = [
                    productCostInput,
                    profitTargetValueInput,
                    shippingFeeInput,
                    commissionRateInput,
                    vatRateCustomInput,
                ];

                requiredInputs.forEach(clearInputInvalid);
                clearCategoryInvalid();

                let hasMissingField = false;
                requiredInputs.forEach((input) => {
                    if (!String(input?? ).trim()) {
                        markInputInvalid(input);
                        hasMissingField = true;
                    }
                });

                const hasManualCommissionRate = String(commissionRateInput?? ;
                if (!hasManualCommissionRate && !String(categoryNameInput?? ).trim()) {
                    markCategoryInvalid();
                    hasMissingField = true;
                }

                if (hasMissingField) {
                    if (resultLabelEl) {
                        resultLabelEl.hidden = true;
                    }
                    return;
                }

                const productCost = Math.max(0, parseDecimal(productCostInput?? ;
                const shippingFee = Math.max(0, parseDecimal(shippingFeeInput?? ;
                const targetValue = Math.max(0, parseDecimal(profitTargetValueInput?? ;
                const vatRatePercent = Math.max(0, parseDecimal(vatRateCustomInput?? ;
                const commissionRatePercent = Math.min(99.99, Math.max(0, parseDecimal(commissionRateInput?? ;

                const desiredProfit = getSelectedProfitType() === 'rate'
                    ?? )
                    : targetValue;

                const baseAmount = productCost + shippingFee + desiredProfit;
                const commissionFactor = 1 - (commissionRatePercent / 100);
                const netRequired = commissionFactor > 0 ?? ) : baseAmount;
                const grossAmount = netRequired * (1 + (vatRatePercent / 100));
                const commissionAmount = Math.max(0, netRequired - baseAmount);
                const serviceFee = resolveServiceFee(grossAmount);
                const selectedMarketplaceKey = String(marketplacePlatformSelect?? ;
                const selectedMarketplaceLabel = marketplacePlatformSelect?? ;
                const platformServiceAmount = Number(platformServiceAmounts[selectedMarketplaceKey] ?? ;
                const serviceFeeAmount = serviceFee.fee + extraServiceFeeAmount + platformServiceAmount;
                const withholdingTaxAmount = grossAmount * (withholdingRatePercent / 100);

                const calculation = {
                    productCost,
                    shippingFee,
                    desiredProfit,
                    vatRatePercent,
                    withholdingRatePercent,
                    serviceFeeBracketLabel: serviceFee.label,
                    extraServiceFeeAmount,
                    platformServiceAmount,
                    selectedMarketplaceLabel,
                    commissionAmount,
                    serviceFeeAmount,
                    withholdingTaxAmount,
                    grossAmount,
                };

                if (resultAmountEl) {
                    resultAmountEl.textContent = formatTl(grossAmount);
                }
                if (resultLabelEl) {
                    resultLabelEl.hidden = false;
                }

                if (stockViewToggle?? ) {
                    openStockCalcModal(calculation);
                }

                return calculation;
            };

            if (vatRateCustomInput && !vatRateCustomInput.value.trim()) {
                const checkedChip = vatRateChipInputs.find((chip) => chip.checked);
                if (checkedChip) {
                    vatRateCustomInput.value = checkedChip.value;
                }
            }

            profitTypeInputs.forEach((input) => {
                input.addEventListener('change', syncProfitTargetPlaceholder);
            });
            syncProfitTargetPlaceholder();

            [productCostInput, profitTargetValueInput, shippingFeeInput, commissionRateInput, vatRateCustomInput].forEach((input) => {
                if (!input) return;
                if (!input.dataset.defaultPlaceholder && input.placeholder) {
                    input.dataset.defaultPlaceholder = input.placeholder;
                }
                input.addEventListener('input', () => {
                    clearInputInvalid(input);
                    if (input === commissionRateInput && String(input.value ?? ).trim().length > 0) {
                        clearCategoryInvalid();
                    }
                });
            });

            vatRateChipInputs.forEach((chip) => {
                chip.addEventListener('change', () => {
                    if (chip.checked && vatRateCustomInput) {
                        vatRateCustomInput.value = chip.value;
                    }
                });
            });

            vatRateCustomInput?? ) => {
                const current = parseDecimal(vatRateCustomInput.value);
                let hasExactMatch = false;
                vatRateChipInputs.forEach((chip) => {
                    const chipValue = parseDecimal(chip.value);
                    const isMatch = Math.abs(chipValue - current) < 0.0001;
                    chip.checked = isMatch;
                    hasExactMatch = hasExactMatch || isMatch;
                });
                if (!hasExactMatch) {
                    vatRateChipInputs.forEach((chip) => (chip.checked = false));
                }
            });

            calculateButton?? ;

            renderCategoryOptions('');
            categorySelectTrigger?? ) => {
                const isOpen = categoryDropdown?? ;
                if (isOpen) {
                    closeCategoryDropdown();
                } else {
                    openCategoryDropdown();
                }
            });

            categorySearchInput?? ) => {
                renderCategoryOptions(categorySearchInput.value || '');
            });

            document.addEventListener('click', (event) => {
                if (!categoryDropdown || !categorySelectTrigger) return;
                const target = event.target;
                if (!(target instanceof Element)) return;
                const isInside = target.closest('.nk-category-select-wrap');
                if (!isInside) {
                    closeCategoryDropdown();
                }
            });

            const stockViewToggle = document.getElementById('nk-stock-view-toggle');
            const profitTablePanel = document.getElementById('nk-profit-table-panel');
            const stockTablePanel = document.getElementById('nk-stock-table-panel');
            const stockViewStorageKey = 'ne-kazanirim-stock-table-enabled';

            const closeStockCalcModal = () => {
                if (!stockCalcModal) return;
                stockCalcModal.classList.remove('is-open');
                stockCalcModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            };

            const openStockCalcModal = (calculation) => {
                if (!stockCalcModal || !calculation) return;

                const setValue = (selector, value) => {
                    const el = stockCalcModal.querySelector(selector);
                    if (el) {
                        el.textContent = value;
                    }
                };

                setValue('#nk-modal-commission', formatTl(calculation.commissionAmount));
                setValue('#nk-modal-service-fee', formatTl(calculation.serviceFeeAmount));
                setValue('#nk-modal-extra-service-fee', formatTl(calculation.extraServiceFeeAmount));
                setValue('#nk-modal-platform-service-fee', formatTl(calculation.platformServiceAmount));
                setValue('#nk-modal-platform-name', calculation.selectedMarketplaceLabel || 'Trendyol');
                setValue('#nk-modal-shipping-fee', formatTl(calculation.shippingFee));
                setValue('#nk-modal-withholding-tax', formatTl(calculation.withholdingTaxAmount));
                setValue('#nk-modal-product-cost', formatTl(calculation.productCost));
                setValue('#nk-modal-target-profit', formatTl(calculation.desiredProfit));
                setValue('#nk-modal-vat-rate', formatPercent(calculation.vatRatePercent));
                setValue('#nk-modal-service-bracket', calculation.serviceFeeBracketLabel || '-');
                setValue('#nk-modal-withholding-rate', formatPercent(calculation.withholdingRatePercent));
                setValue('#nk-modal-gross-amount', formatTl(calculation.grossAmount));

                stockCalcModal.classList.add('is-open');
                stockCalcModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            };

            const syncTableView = () => {
                const showStockTable = Boolean(stockViewToggle?? ;
                profitTablePanel?? ;
                stockTablePanel?? ;
                updateStockViewLabel(showStockTable);
                syncLeftTableHeight();
            };

            const stockViewLabel = document.getElementById('nk-stock-view-label');
            const updateStockViewLabel = (showStockTable) => {
                if (!stockViewLabel) return;
                stockViewLabel.textContent = showStockTable ? 'Stok Hesaplama' : 'Sipari\u015F Hesaplama';
            };

            const syncLeftTableHeight = () => {
                const panels = Array.from(document.querySelectorAll('.nk-table-panel'));
                const rightColumn = document.querySelector('.nk-right');
                const leftCard = document.querySelector('.nk-left > .nk-card');
                const leftHeader = leftCard?? ;
                const leftBody = leftCard?? ;

                if (!panels.length || !rightColumn || !leftCard || !leftBody) {
                    return;
                }

                if (window.innerWidth < 992) {
                    panels.forEach((panel) => {
                        panel.style.height = '';
                        panel.style.maxHeight = '';
                    });
                    return;
                }

                const rightHeight = rightColumn.getBoundingClientRect().height;
                const headerHeight = leftHeader ?? ).height : 0;
                const bodyStyle = window.getComputedStyle(leftBody);
                const bodyPaddingTop = parseFloat(bodyStyle.paddingTop || '0');
                const bodyPaddingBottom = parseFloat(bodyStyle.paddingBottom || '0');

                const targetHeight = Math.max(
                    280,
                    Math.floor(rightHeight - headerHeight - bodyPaddingTop - bodyPaddingBottom - 2)
                );

                panels.forEach((panel) => {
                    panel.style.height = `${targetHeight}px`;
                    panel.style.maxHeight = `${targetHeight}px`;
                });
            };

            const savedStockView = localStorage.getItem(stockViewStorageKey);
            if (stockViewToggle && savedStockView !== null) {
                stockViewToggle.checked = savedStockView === '1';
            }

            stockViewToggle?? ) => {
                localStorage.setItem(stockViewStorageKey, stockViewToggle.checked ? '1' : '0');
                syncTableView();
            });
            syncTableView();
            window.addEventListener('resize', syncLeftTableHeight);

            stockCalcModal?? ).forEach((btn) => {
                btn.addEventListener('click', closeStockCalcModal);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeStockCalcModal();
                }
            });

            const imagePopover = document.getElementById('nk-image-popover');
            const imagePopoverImg = document.getElementById('nk-image-popover-img');
            const imageTriggers = Array.from(document.querySelectorAll('[data-nk-preview-src]'));

            const placePopover = (event) => {
                if (!imagePopover?? ;
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

                imagePopover.style.left = `${Math.max(8, left)}px`;
                imagePopover.style.top = `${Math.max(8, top)}px`;
            };

            imageTriggers.forEach((trigger) => {
                trigger.addEventListener('mouseenter', (event) => {
                    const src = trigger.getAttribute('data-nk-preview-src');
                    if (!src || !imagePopover || !imagePopoverImg) return;
                    imagePopoverImg.src = src;
                    imagePopoverImg.alt = trigger.getAttribute('data-nk-preview-alt') || 'Urun gorseli';
                    imagePopover.classList.add('is-open');
                    placePopover(event);
                });

                trigger.addEventListener('mousemove', placePopover);

                trigger.addEventListener('mouseleave', () => {
                    imagePopover?? ;
                    if (imagePopoverImg) {
                        imagePopoverImg.removeAttribute('src');
                    }
                });
            });

            const setInlineText = (panel, selector, value) => {
                const el = panel.querySelector(selector);
                if (el) {
                    el.textContent = value || '-';
                }
            };

            const setInlineValueTone = (panel, selector, rawValue) => {
                const el = panel.querySelector(selector);
                if (!el) {
                    return;
                }

                const normalized = (rawValue || '')
                    .toString()
                    .replace(/\s/g, '')
                    .replace('TL', '')
                    .replace('%', '')
                    .replace('.', '')
                    .replace(',', '.');
                const num = parseFloat(normalized);

                el.classList.remove('nk-negative', 'nk-positive');
                if (!Number.isNaN(num)) {
                    el.classList.add(num < 0 ? 'nk-negative' : 'nk-positive');
                }
            };

            const setInlineTypeLabels = (panel, type) => {
                if (type === 'stock') {
                    setInlineText(panel, '[data-nk-inline-title]', 'Urun Stok Detayi');
                    setInlineText(panel, '[data-nk-inline-box1-label]', 'Urun:');
                    setInlineText(panel, '[data-nk-inline-box2-label]', 'Stok:');
                    setInlineText(panel, '[data-nk-inline-label-1]', 'Marka:');
                    setInlineText(panel, '[data-nk-inline-label-2]', 'SKU:');
                    setInlineText(panel, '[data-nk-inline-label-3]', 'Fiyat:');
                    setInlineText(panel, '[data-nk-inline-label-4]', 'Pazaryeri:');
                    setInlineText(panel, '[data-nk-inline-label-5]', 'Kritik Seviye:');
                    setInlineText(panel, '[data-nk-inline-label-6]', 'Uyari:');
                    setInlineText(panel, '[data-nk-inline-label-7]', 'Durum:');
                    setInlineText(panel, '[data-nk-inline-label-8]', '-');
                    return;
                }

                setInlineText(panel, '[data-nk-inline-title]', 'Siparis Karlilik Detayi');
                setInlineText(panel, '[data-nk-inline-box1-label]', 'Siparis Tutari:');
                setInlineText(panel, '[data-nk-inline-box2-label]', 'Kar Tutari:');
                setInlineText(panel, '[data-nk-inline-label-1]', 'Urun Maliyeti:');
                setInlineText(panel, '[data-nk-inline-label-2]', 'Komisyon:');
                setInlineText(panel, '[data-nk-inline-label-3]', 'Kargo:');
                setInlineText(panel, '[data-nk-inline-label-4]', 'Platform Hizmeti:');
                setInlineText(panel, '[data-nk-inline-label-5]', 'Iade Kargo Duzeltmesi:');
                setInlineText(panel, '[data-nk-inline-label-6]', 'Stopaj:');
                setInlineText(panel, '[data-nk-inline-label-7]', 'Satis KDV:');
                setInlineText(panel, '[data-nk-inline-label-8]', 'KDV Orani:');
            };

            const closeInlineRows = (scope) => {
                const root = scope || document;
                root.querySelectorAll('[data-nk-inline-row]').forEach((detailRow) => {
                    detailRow.classList.add('is-hidden');
                    detailRow.querySelector('[data-nk-inline-panel]')?? ;

                    const btn = detailRow.previousElementSibling?? ;
                    if (btn) {
                        btn.textContent = 'Detayi Gor';
                        btn.setAttribute('aria-expanded', 'false');
                    }
                });
            };

            const bindDetailButtons = () => {
                document.querySelectorAll('[data-nk-detail]').forEach((button) => {
                    if (button.dataset.nkDetailBound === '1') {
                        return;
                    }

                    button.dataset.nkDetailBound = '1';
                    button.addEventListener('click', () => {
                        const dataRow = button.closest('tr');
                        const detailRow = dataRow?? ;
                        if (!detailRow || !detailRow.matches('[data-nk-inline-row]')) {
                            return;
                        }

                        const tbody = dataRow.parentElement;
                        const isOpen = !detailRow.classList.contains('is-hidden');
                        closeInlineRows(tbody);
                        if (isOpen) {
                            return;
                        }

                        const detailType = button.getAttribute('data-nk-detail-type') || 'profit';
                        const panel = detailRow.querySelector('[data-nk-inline-panel]');
                        if (!panel) {
                            return;
                        }

                        setInlineTypeLabels(panel, detailType);

                        if (detailType === 'stock') {
                            setInlineText(panel, '[data-nk-inline-subtitle]', button.getAttribute('data-product-name'));
                            setInlineText(panel, '[data-nk-inline-box1]', button.getAttribute('data-product-name'));
                            setInlineText(panel, '[data-nk-inline-box2]', button.getAttribute('data-stock-quantity'));
                            setInlineText(panel, '[data-nk-inline-line-1]', button.getAttribute('data-brand'));
                            setInlineText(panel, '[data-nk-inline-line-2]', button.getAttribute('data-sku'));
                            setInlineText(panel, '[data-nk-inline-line-3]', button.getAttribute('data-price'));
                            setInlineText(panel, '[data-nk-inline-line-4]', button.getAttribute('data-marketplaces'));
                            setInlineText(panel, '[data-nk-inline-line-5]', button.getAttribute('data-critical-level'));
                            setInlineText(panel, '[data-nk-inline-line-6]', button.getAttribute('data-alert'));
                            setInlineText(panel, '[data-nk-inline-line-7]', button.getAttribute('data-status'));
                            setInlineText(panel, '[data-nk-inline-line-8]', '-');
                        } else {
                            setInlineText(panel, '[data-nk-inline-subtitle]', button.getAttribute('data-order-number'));
                            setInlineText(panel, '[data-nk-inline-box1]', button.getAttribute('data-sale-price'));
                            setInlineText(panel, '[data-nk-inline-box2]', button.getAttribute('data-profit-amount'));
                            setInlineText(panel, '[data-nk-inline-line-1]', button.getAttribute('data-product-cost'));
                            setInlineText(panel, '[data-nk-inline-line-2]', button.getAttribute('data-commission'));
                            setInlineText(panel, '[data-nk-inline-line-3]', button.getAttribute('data-shipping-fee'));
                            setInlineText(panel, '[data-nk-inline-line-4]', button.getAttribute('data-platform-fee'));
                            setInlineText(panel, '[data-nk-inline-line-5]', button.getAttribute('data-refund-adjustment'));
                            setInlineText(panel, '[data-nk-inline-line-6]', button.getAttribute('data-withholding-tax'));
                            setInlineText(panel, '[data-nk-inline-line-7]', button.getAttribute('data-sales-vat'));
                            setInlineText(panel, '[data-nk-inline-line-8]', button.getAttribute('data-vat-rate'));
                            setInlineValueTone(panel, '[data-nk-inline-box2]', button.getAttribute('data-profit-amount'));
                        }

                        detailRow.classList.remove('is-hidden');
                        requestAnimationFrame(() => {
                            panel.classList.add('is-open');
                        });

                        button.textContent = 'Detayi Gizle';
                        button.setAttribute('aria-expanded', 'true');
                    });
                });
            };

            bindDetailButtons();

            const refreshIntervalMs = 15000;
            let isRefreshingOrders = false;

            const captureOpenProfitDetailState = () => {
                const openDetailRow = document.querySelector('#nk-profit-table-panel [data-nk-inline-row]:not(.is-hidden)');
                if (!openDetailRow) {
                    return null;
                }

                const sourceButton = openDetailRow.previousElementSibling?? ;
                if (!sourceButton) {
                    return null;
                }

                const allButtons = Array.from(document.querySelectorAll('#nk-profit-table-panel tbody [data-nk-detail]'));
                const rowIndex = allButtons.indexOf(sourceButton);

                return {
                    detailType: sourceButton.getAttribute('data-nk-detail-type') || 'profit',
                    orderNumber: sourceButton.getAttribute('data-order-number') || '',
                    rowIndex,
                };
            };

            const restoreOpenProfitDetailState = (state) => {
                if (!state) {
                    return;
                }

                const buttons = Array.from(document.querySelectorAll('#nk-profit-table-panel tbody [data-nk-detail]'));
                let targetButton = buttons.find((btn) => {
                    const detailType = btn.getAttribute('data-nk-detail-type') || 'profit';
                    const orderNumber = btn.getAttribute('data-order-number') || '';

                    return detailType === state.detailType && orderNumber === state.orderNumber;
                });

                if (!targetButton && state.rowIndex >= 0 && state.rowIndex < buttons.length) {
                    targetButton = buttons[state.rowIndex];
                }

                targetButton?? ;
            };

            const refreshOrdersTable = async () => {
                if (isRefreshingOrders || stockViewToggle?? ) {
                    return;
                }

                const openDetailState = captureOpenProfitDetailState();

                isRefreshingOrders = true;
                try {
                    const response = await fetch(window.location.href, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        return;
                    }

                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const nextTbody = doc.querySelector('#nk-profit-table-panel tbody');
                    const currentTbody = document.querySelector('#nk-profit-table-panel tbody');

                    if (!nextTbody || !currentTbody) {
                        return;
                    }

                    currentTbody.innerHTML = nextTbody.innerHTML;
                    if (selectAll) {
                        selectAll.checked = false;
                    }
                    bindSelectAll();
                    bindDetailButtons();
                    restoreOpenProfitDetailState(openDetailState);
                } catch (error) {
                    console.error(error);
                } finally {
                    isRefreshingOrders = false;
                }
            };

            window.setInterval(refreshOrdersTable, refreshIntervalMs);

        })();
    </script>
<?php $__env->stopPush(); ?>





<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
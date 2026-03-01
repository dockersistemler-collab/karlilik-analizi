@extends('layouts.admin')

@section('header')
    Müşteri İletişim Merkezi
@endsection

@section('content')
    @php
        $statusLabels = [
            'open' => 'Açık',
            'pending' => 'Beklemede',
            'answered' => 'Yanıtlandı',
            'closed' => 'Kapalı',
            'overdue' => 'Gecikmiş',
        ];

        $channelLabels = [
            'question' => 'Soru',
            'message' => 'Mesaj',
            'review' => 'Yorum',
            'return' => 'İade',
        ];

        $queryBase = request()->except('page');
        $questionCount = (int) ($channelCounts['question'] ?? 0);
        $messageCount = (int) ($channelCounts['message'] ?? 0);
        $reviewCount = (int) ($channelCounts['review'] ?? 0);
        $allMarketplaceCount = collect($marketplaceCounts ?? [])->sum();
        $statusTotals = collect($statusCounts ?? []);
        $awaitingStatusCount = (int) (
            ($statusTotals->get('open', 0))
            + ($statusTotals->get('pending', 0))
            + ($statusTotals->get('overdue', 0))
        );
        $answeredStatusCount = (int) ($statusTotals->get('answered', 0));
        $storeTotals = collect($storeCounts ?? []);
        $allStoreCount = $storeTotals->sum();
        $normalizeMarketplaceKey = static function (?string $marketplaceName): string {
            return \Illuminate\Support\Str::of(trim((string) $marketplaceName))->lower()->ascii()->value();
        };
        $marketplaceLogoUrl = static function (?string $marketplaceName) use ($normalizeMarketplaceKey): ?string {
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
    @endphp

    <style>
        body.menu-modern-shell .menu-modern-hero {
            display: none;
        }

        .cc-hero {
            background:
                radial-gradient(120% 130% at 0% 0%, rgba(255, 237, 213, 0.7) 0%, transparent 52%),
                radial-gradient(100% 120% at 100% 0%, rgba(219, 234, 254, 0.75) 0%, transparent 55%),
                linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 1.2rem;
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.08);
        }

        .cc-card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: transparent;
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        }

        .cc-topbar-wrap {
            border: 1px solid #dbe4ef;
            border-radius: 1rem;
            background:
                radial-gradient(120% 130% at 0% 0%, rgba(248, 250, 252, 0.92) 0%, transparent 55%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
            padding: .8rem;
        }

        .cc-tab {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .48rem .9rem;
            border-radius: 999px;
            border: 1px solid #d4dce7;
            background: transparent;
            color: #0f172a;
            font-size: .92rem;
            font-weight: 500;
            transition: .2s ease;
        }

        .cc-tab:hover {
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        .cc-tab.is-active {
            border-color: #93c5fd;
            color: #0f172a;
            background: transparent;
            font-weight: 700;
            box-shadow: 0 0 0 1px rgba(96, 165, 250, 0.35);
        }

        .cc-label {
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            min-width: 0;
        }

        .cc-label i {
            font-size: .72rem;
            color: #64748b;
        }

        .cc-tab.is-active .cc-label i {
            color: #64748b;
        }

        .cc-mini {
            border: 1px solid #e2e8f0;
            border-radius: .9rem;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .cc-mini-label {
            font-size: .76rem;
            font-weight: 800;
            color: #64748b;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .cc-mini-value {
            margin-top: .22rem;
            font-size: 1.2rem;
            line-height: 1;
            font-weight: 800;
        }

        .cc-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.35rem;
            padding: 0 .35rem;
            border-radius: 999px;
            color: #fff;
            font-size: .72rem;
            font-weight: 800;
            line-height: 1;
        }

        .cc-badge.hot {
            background: #ef4444;
        }

        .cc-badge.done {
            background: #16a34a;
        }

        .cc-badge.zero {
            background: #6b7280;
        }
        .cc-market-grid {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            justify-content: center;
            align-items: center;
        }

        .cc-market-grid .cc-market-chip {
            position: relative;
            width: 92px;
            min-height: 80px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .1rem;
            border-radius: 12px !important;
            border: 1.5px solid #b9cbe3 !important;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: #0f172a;
            font-weight: 700;
            padding: .35rem;
            box-shadow: inset 0 0 0 1px #edf3fb, 0 8px 16px rgba(15, 23, 42, 0.08);
            transition: .2s ease;
        }

        .cc-market-grid .cc-market-chip:hover {
            transform: translateY(-1px);
            border-color: #b9cbe3 !important;
            box-shadow: inset 0 0 0 1px #edf3fb, 0 10px 18px rgba(15, 23, 42, 0.10);
        }

        .cc-market-grid .cc-market-chip.is-active {
            border-color: #b9cbe3 !important;
            background: linear-gradient(135deg, #edf4ff 0%, #dceafe 52%, #edf5ff 100%);
            color: #0f172a;
            box-shadow: 0 14px 28px rgba(59, 130, 246, 0.18);
        }

        .cc-market-logo-wrap {
            width: 58px;
            height: 58px;
            border-radius: 0;
            border: none;
            background: transparent;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            outline: none;
            outline-offset: 0;
            box-shadow: none;
        }

        .cc-market-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: saturate(1.12) contrast(1.06);
            transform: scale(1.08);
            transition: transform .22s ease;
        }

        .cc-market-logo-fallback {
            font-size: .72rem;
            font-weight: 800;
            color: #334155;
        }

        .cc-market-name {
            display: none;
        }
        .cc-market-logo-fallback-all {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
            font-size: .78rem;
            letter-spacing: .01em;
        }

        .cc-market-logo-fallback-all i {
            font-size: .78rem;
            line-height: 1;
        }

        .cc-market-chip .cc-badge {
            position: absolute;
            right: -.35rem;
            top: -.35rem;
            z-index: 2;
        }

        .cc-market-chip:hover .cc-market-logo {
            transform: scale(1.15);
        }
        .cc-market-cell-card {
            position: relative;
            width: 88px;
            min-height: 58px;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0;
            border-radius: 12px;
            border: 1.5px solid #b9cbe3;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            color: #0f172a;
            font-weight: 700;
            padding: .25rem;
            box-shadow: inset 0 0 0 1px #edf3fb, 0 8px 16px rgba(15, 23, 42, 0.08);
            transition: .2s ease;
        }

        .cc-market-cell-card .cc-market-logo-fallback {
            font-size: .72rem;
            color: #1e293b;
        }

        .cc-market-cell-card .cc-market-logo-wrap {
            width: 48px;
            height: 48px;
            border-radius: 0;
            border: none;
            box-shadow: none;
        }

        .cc-market-cell-card .cc-market-logo {
            width: 44px;
            height: 44px;
        }

        .cc-market-cell-name {
            display: none;
        }

        .cc-market-cell-card:hover .cc-market-logo {
            transform: scale(1.15);
        }

        .cc-filter-card {
            border: 1px solid rgba(148, 163, 184, 0.28);
            border-radius: 1.25rem;
            background:
                radial-gradient(130% 120% at 0% 0%, rgba(219, 234, 254, 0.55) 0%, transparent 52%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85), 0 16px 34px rgba(15, 23, 42, 0.08);
        }

        .cc-filter-card > h3 {
            margin: 0 0 .35rem 0;
            padding: .2rem .25rem .85rem;
            border-bottom: 1px solid #dbe5f1;
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: .01em;
        }

        .cc-expand-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            color: #0f172a;
            padding: .4rem .85rem;
            font-weight: 700;
            transition: .2s ease;
        }

        .cc-expand-btn:hover {
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        .cc-expand-btn i {
            transition: transform .2s ease;
        }

        .cc-expand-btn.is-open i {
            transform: rotate(180deg);
        }

        .cc-sync-now-btn {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border: 1px solid rgba(148, 163, 184, 0.45) !important;
            border-radius: 999px !important;
            padding: .58rem 1.05rem !important;
            color: #fff !important;
            font-size: .92rem;
            font-weight: 500;
            letter-spacing: .01em;
            background: linear-gradient(135deg, #111827 0%, #374151 55%, #111827 100%) !important;
            box-shadow: 0 6px 14px rgba(17, 24, 39, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.14);
            transition: transform .2s ease, box-shadow .2s ease, filter .2s ease;
        }

        .cc-sync-now-btn::before {
            content: "";
            position: absolute;
            inset: 1px;
            border-radius: inherit;
            pointer-events: none;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.20), transparent 48%);
        }

        .cc-sync-now-btn:hover {
            border-color: rgba(34, 197, 94, 0.55) !important;
            transform: translateY(-1px);
            filter: brightness(1.04);
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.28), 0 0 18px rgba(34, 197, 94, 0.28), 0 8px 18px rgba(17, 24, 39, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.18);
        }

        .cc-sync-now-btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(17, 24, 39, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .cc-sync-now-btn:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.28), 0 10px 22px rgba(17, 24, 39, 0.26);
        }

        .cc-sync-now-btn > * {
            position: relative;
            z-index: 1;
        }

        .cc-detail-row {
            display: none;
        }

        .cc-detail-row.is-open {
            display: table-row;
        }

        .cc-detail-row .cc-inline-wrap {
            transform-origin: top center;
            max-height: 1400px;
            overflow: hidden;
            opacity: 1;
            transform: translateY(0);
            transition: max-height 1.1s ease, opacity .9s ease, transform .9s ease;
        }

        .cc-detail-row.is-sending-close .cc-inline-wrap {
            max-height: 0;
            opacity: 0;
            transform: translateY(-22px);
        }

        .cc-detail-wrap {
            margin: .2rem 0 .9rem;
            border: 1px solid #dbe4ef;
            border-radius: 1rem;
            background:
                radial-gradient(120% 130% at 0% 0%, rgba(219, 234, 254, 0.35) 0%, transparent 55%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            padding: .9rem;
        }

        .cc-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .6rem;
        }

        .cc-detail-item {
            border: 1px solid #dbe3ee;
            border-radius: 12px;
            background: transparent;
            padding: .6rem .7rem;
            display: flex;
            align-items: center;
            gap: .55rem;
        }

        .cc-detail-item i {
            color: #64748b;
            width: 1rem;
            text-align: center;
        }

        .cc-detail-k {
            color: #64748b;
            font-size: .72rem;
            font-weight: 600;
        }

        .cc-detail-v {
            color: #0f172a;
            font-size: .85rem;
            font-weight: 800;
        }

        .cc-detail-actions {
            margin-top: .75rem;
            display: flex;
            justify-content: flex-end;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .cc-detail-link {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            background: #fff;
            color: #1e293b;
            font-weight: 700;
            padding: .45rem .75rem;
        }

        .cc-detail-link.primary {
            border-color: #bfdbfe;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #1d4ed8;
        }

        .cc-inline-wrap {
            margin: .2rem 0 .9rem;
            border: 1px solid #dbe4ef;
            border-radius: 1rem;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            padding: .9rem;
        }

        .cc-inline-wrap.sent-ok {
            border-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.22), 0 12px 24px rgba(15, 23, 42, 0.08);
        }

        .cc-inline-meta {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .55rem;
        }

        .cc-inline-meta-item {
            border: 1px solid #dbe3ee;
            border-radius: 12px;
            background: #fff;
            padding: .55rem .65rem;
        }

        .cc-inline-meta-k {
            font-size: .72rem;
            color: #64748b;
            font-family: "Inter", "Segoe UI", sans-serif;
            font-weight: 500;
        }

        .cc-inline-meta-v {
            margin-top: .15rem;
            font-size: .92rem;
            color: #0f172a;
            font-family: "Inter", "Segoe UI", sans-serif;
            font-weight: 500;
            line-height: 1.2;
        }

        .cc-inline-chat {
            margin-top: .65rem;
            border: 1px solid #dbe4ef;
            border-radius: .9rem;
            background: #fff;
            max-height: 220px;
            overflow: auto;
            padding: .65rem;
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .cc-inline-msg {
            max-width: 82%;
            border-radius: .85rem;
            padding: .48rem .62rem;
            font-size: .86rem;
            line-height: 1.35;
            position: relative;
        }

        .cc-inline-msg.in {
            align-self: flex-start;
            background: linear-gradient(180deg, #eff6ff 0%, #dbeafe 100%);
            color: #0f172a;
        }

        .cc-inline-msg.out {
            align-self: flex-end;
            background: linear-gradient(180deg, #dcfce7 0%, #bbf7d0 100%);
            color: #14532d;
        }

        .cc-inline-msg-body {
            white-space: pre-wrap;
            word-break: break-word;
        }
.cc-inline-edit-btn {
            position: absolute;
            left: -1.55rem;
            top: .28rem;
            width: 1.2rem;
            height: 1.2rem;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #ffffff;
            color: #334155;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transform: translateX(4px);
            transition: opacity .18s ease, transform .18s ease;
        }

        .cc-inline-msg.out:hover .cc-inline-edit-btn {
            opacity: 1;
            transform: translateX(0);
        }

        .cc-inline-editing-note {
            margin-top: .5rem;
            border: 1px solid #bfdbfe;
            border-radius: .65rem;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: .76rem;
            font-weight: 700;
            padding: .35rem .55rem;
            display: none;
        }

        .cc-inline-editing-note.show {
            display: block;
        }

        .cc-inline-time {
            margin-top: .18rem;
            font-size: .7rem;
            opacity: .78;
        }

        .cc-inline-compose {
            margin-top: .75rem;
            border: 1px solid #dbe4ef;
            border-radius: .95rem;
            background: #fff;
            padding: .7rem;
        }

        .cc-inline-tools {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .5rem;
            align-items: center;
        }

        .cc-inline-quick {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-top: .5rem;
        }

        .cc-inline-quick-btn {
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #fff;
            color: #1e293b;
            font-size: .72rem;
            font-weight: 700;
            padding: .3rem .58rem;
        }

        .cc-inline-box {
            margin-top: .55rem;
            border: 1px solid #dbe4ef;
            border-radius: 12px;
            overflow: hidden;
        }

        .cc-inline-box-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .42rem .6rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: .76rem;
            font-weight: 700;
            color: #475569;
        }

        .cc-inline-box textarea {
            width: 100%;
            border: 0 !important;
            min-height: 110px;
            resize: vertical;
        }

        .cc-inline-actions {
            margin-top: .55rem;
            display: flex;
            justify-content: flex-end;
            gap: .45rem;
            flex-wrap: wrap;
        }

        .cc-inline-send {
            display: inline-flex;
            align-items: center;
            gap: .38rem;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
            color: #1e3a8a;
            font-weight: 800;
            padding: .42rem .72rem;
        }

        .cc-inline-success {
            margin-top: .55rem;
            border: 1px solid #bbf7d0;
            border-radius: .7rem;
            background: #f0fdf4;
            color: #166534;
            font-size: .82rem;
            font-weight: 700;
            padding: .45rem .6rem;
            display: none;
            align-items: center;
            gap: .42rem;
        }

        .cc-inline-success.show {
            display: inline-flex;
        }

        @media (max-width: 1200px) {
            .cc-inline-meta {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .cc-filter-title {
            font-size: .75rem;
            font-family: "Inter", "Segoe UI", sans-serif;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            margin-bottom: .6rem;
        }

        .cc-status-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem;
            border: 0;
            border-radius: 0;
            overflow: visible;
            background: transparent;
            box-shadow: none;
        }

        .cc-status-chip {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #d7e2ef;
            border-radius: .9rem;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
            color: #0f172a;
            padding: .62rem .72rem;
            font-size: .8rem;
            font-family: "Inter", "Segoe UI", sans-serif;
            font-weight: 600;
            transition: box-shadow .22s ease, transform .22s ease, background-color .22s ease;
            min-height: 3rem;
            position: relative;
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.92);
        }

        .cc-status-chip::before {
            content: "";
            position: absolute;
            left: 0;
            top: .35rem;
            bottom: .35rem;
            width: 3px;
            border-radius: 999px;
            background: #93c5fd;
        }

        .cc-status-chip:hover {
            border-color: #bfdbfe;
            background: linear-gradient(180deg, #ffffff 0%, #f2f7ff 100%);
            transform: translateY(-1px);
            box-shadow: 0 0 0 1px rgba(191, 219, 254, 0.7), 0 12px 24px rgba(15, 23, 42, 0.10);
            z-index: 2;
        }

        .cc-status-chip.is-active {
            border-color: #60a5fa;
            background: linear-gradient(135deg, #eaf2ff 0%, #dbeafe 52%, #ebf3ff 100%);
            color: #1e3a8a;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25), 0 14px 26px rgba(37, 99, 235, 0.15);
        }

        .cc-chip-label {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            min-width: 0;
            font-family: "Inter", "Segoe UI", sans-serif;
            font-weight: 500;
        }

        .cc-chip-label i {
            font-size: .72rem;
            color: #64748b;
        }

        .cc-store-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .55rem;
            border: 0;
            border-radius: 0;
            overflow: visible;
            background: transparent;
            box-shadow: none;
        }

        .cc-store-chip {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #d7e2ef;
            border-radius: .9rem;
            background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
            color: #0f172a;
            padding: .62rem .72rem;
            font-size: .8rem;
            font-family: "Inter", "Segoe UI", sans-serif;
            font-weight: 600;
            transition: box-shadow .22s ease, transform .22s ease, background-color .22s ease;
            min-height: 3rem;
            position: relative;
            box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.92);
        }

        .cc-store-chip::before {
            content: "";
            position: absolute;
            left: 0;
            top: .35rem;
            bottom: .35rem;
            width: 3px;
            border-radius: 999px;
            background: #93c5fd;
        }

        .cc-store-chip:hover {
            border-color: #bfdbfe;
            background: linear-gradient(180deg, #ffffff 0%, #f2f7ff 100%);
            transform: translateY(-1px);
            box-shadow: 0 0 0 1px rgba(191, 219, 254, 0.7), 0 12px 24px rgba(15, 23, 42, 0.10);
            z-index: 2;
        }

        .cc-store-chip.is-active {
            border-color: #60a5fa;
            background: linear-gradient(135deg, #eaf2ff 0%, #dbeafe 52%, #ebf3ff 100%);
            color: #1e3a8a;
            box-shadow: 0 0 0 2px rgba(96, 165, 250, 0.25), 0 14px 26px rgba(37, 99, 235, 0.15);
        }

        .cc-store-chip.is-active .cc-chip-label i,
        .cc-status-chip.is-active .cc-chip-label i {
            color: #2563eb;
        }

        .cc-filter-section {
            border: 1px solid #d7e2ef;
            border-radius: 1.05rem;
            padding: .8rem;
            background:
                radial-gradient(120% 120% at 0% 0%, rgba(219, 234, 254, 0.32) 0%, transparent 55%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.88), 0 10px 20px rgba(15, 23, 42, 0.06);
        }

        .cc-store-grid > *,
        .cc-status-grid > * {
            border-right: 0;
            border-bottom: 0;
            box-shadow: none;
        }

        .cc-status-text {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .8rem;
            font-weight: 800;
        }

        .cc-status-open {
            color: #16a34a;
        }

        .cc-status-pending {
            color: #d97706;
        }

        .cc-status-answered {
            color: #6366f1;
        }

        .cc-status-overdue {
            color: #dc2626;
        }

        .cc-status-closed {
            color: #475569;
        }

        .cc-field-label {
            font-size: .75rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: .3rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .cc-date-input,
        .cc-search-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            min-height: 2.7rem;
            font-size: .9rem;
            color: #0f172a;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .8), 0 8px 18px rgba(15, 23, 42, .05);
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .cc-date-input:focus,
        .cc-search-input:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 4px rgba(147, 197, 253, .25), 0 10px 20px rgba(15, 23, 42, .08);
            outline: none;
        }

        .cc-search-input::placeholder {
            color: #94a3b8;
        }

        .cc-apply-btn {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: .95rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 52%, #e8f2ff 100%);
            color: #1e3a8a;
            font-weight: 800;
            letter-spacing: .01em;
            min-height: 2.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            box-shadow: 0 10px 22px rgba(148, 163, 184, .2);
            transition: transform .18s ease, box-shadow .2s ease, filter .2s ease;
        }

        .cc-apply-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(148, 163, 184, .26);
            filter: brightness(1.02);
        }

        .cc-filter-divider {
            height: 1px;
            margin: .65rem .2rem .45rem;
            background: linear-gradient(90deg, transparent 0%, #cbd5e1 14%, #b6c3d6 50%, #cbd5e1 86%, transparent 100%);
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.78), 0 -1px 0 rgba(148, 163, 184, 0.14);
        }

        .cc-template-builder {
            border: 1px solid #d9e3ef;
            border-radius: 1rem;
            background:
                radial-gradient(120% 120% at 0% 0%, rgba(226, 232, 240, 0.45) 0%, transparent 55%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.75), 0 10px 20px rgba(15, 23, 42, 0.05);
            padding: .55rem;
            margin-top: .45rem;
        }

        .cc-template-head {
            display: grid;
            grid-template-columns: 1.75rem 1fr 1.75rem;
            align-items: center;
            width: 100%;
            padding: 0 .35rem;
            min-height: 2.45rem;
            border-radius: .8rem;
            border: 1px solid #dbe4ef;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            cursor: pointer;
            user-select: none;
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .cc-template-head:hover {
            border-color: #cbd5e1;
            box-shadow:
                inset 0 2px 6px rgba(148, 163, 184, .22),
                inset 0 -1px 2px rgba(255, 255, 255, .85);
            transform: translateY(0);
        }

        .cc-template-title {
            font-size: 1.02rem;
            font-weight: 800;
            letter-spacing: .01em;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .4rem;
            grid-column: 2;
            justify-self: center;
            margin: 0;
            white-space: nowrap;
            text-align: center;
        }

        .cc-template-spacer {
            grid-column: 1;
            justify-self: center;
            width: 1rem;
            height: 1rem;
            opacity: 0;
            pointer-events: none;
        }

        .cc-template-chevron {
            grid-column: 3;
            justify-self: center;
            color: #64748b;
            transition: transform .25s ease;
        }

        .cc-template-builder.is-open .cc-template-chevron {
            transform: rotate(180deg);
        }

        .cc-template-panel {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: max-height .35s ease, opacity .2s ease, margin-top .2s ease;
            margin-top: 0;
        }

        .cc-template-builder.is-open .cc-template-panel {
            max-height: 520px;
            opacity: 1;
            margin-top: .55rem;
        }

        .cc-template-row-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .55rem;
            margin-bottom: .3rem;
        }

        .cc-template-hint {
            font-size: .69rem;
            color: #64748b;
            font-weight: 700;
            white-space: nowrap;
        }

        .cc-template-input,
        .cc-template-textarea {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: .8rem;
            background: #fff;
            color: #0f172a;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .cc-template-input {
            min-height: 2.45rem;
            font-size: .88rem;
        }

        .cc-template-textarea {
            min-height: 6.5rem;
            resize: vertical;
            font-size: .86rem;
        }

        .cc-template-input:focus,
        .cc-template-textarea:focus {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(147, 197, 253, .25);
            outline: none;
        }

        .cc-template-save {
            width: 100%;
            border: 1px solid #bfdbfe;
            border-radius: .85rem;
            min-height: 2.55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .42rem;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #1d4ed8;
            font-weight: 800;
            transition: transform .16s ease, box-shadow .18s ease;
        }

        .cc-template-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 22px rgba(37, 99, 235, .18);
        }

        .cc-template-feedback {
            display: none;
            margin-top: .45rem;
            border-radius: .7rem;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1e3a8a;
            font-size: .75rem;
            font-weight: 700;
            padding: .4rem .55rem;
        }

        .cc-template-feedback.show {
            display: block;
        }

        .cc-template-feedback.is-error {
            border-color: #fecaca;
            background: #fff1f2;
            color: #be123c;
        }

    </style>

    <div class="space-y-6">
        <section class="cc-hero p-5 md:p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                        Canlı Operasyon
                    </div>
                    <h2 class="mt-3 text-3xl font-bold text-slate-900">Müşteri İletişim Merkezi</h2>
                    <p class="mt-1 text-sm text-slate-600">Pazaryeri mesajlarını tek ekrandan takip edin ve hızlı yanıtlayın.</p>
                </div>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div class="cc-mini p-3">
                        <div class="cc-mini-label">Toplam</div>
                        <div class="cc-mini-value text-slate-900">{{ number_format($threads->total()) }}</div>
                    </div>
                    <div class="cc-mini p-3">
                        <div class="cc-mini-label">Açık</div>
                        <div class="cc-mini-value text-blue-700">{{ $threads->getCollection()->where('status', 'open')->count() }}</div>
                    </div>
                    <div class="cc-mini p-3">
                        <div class="cc-mini-label">Kritik</div>
                        <div class="cc-mini-value text-amber-700">{{ $threads->getCollection()->filter(fn ($t) => in_array($t->status, ['open', 'pending', 'overdue'], true) && $t->due_at && !$t->due_at->isPast() && now()->diffInMinutes($t->due_at, false) <= $criticalThresholdMinutes)->count() }}</div>
                    </div>
                    <div class="cc-mini p-3">
                        <div class="cc-mini-label">Gecikmiş</div>
                        <div class="cc-mini-value text-rose-700">{{ $threads->getCollection()->filter(fn ($t) => in_array($t->status, ['open', 'pending', 'overdue'], true) && $t->due_at && $t->due_at->isPast())->count() }}</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="cc-topbar-wrap">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('portal.communication-center.questions', $queryBase) }}" class="cc-tab {{ $channel === 'question' ? 'is-active' : '' }}">
                    <span class="cc-label"><i class="fa-regular fa-circle-question"></i><span>Sorular</span></span>
                    <span class="cc-badge {{ $questionCount > 0 ? 'hot' : 'zero' }}">{{ $questionCount }}</span>
                </a>
                <a href="{{ route('portal.communication-center.messages', $queryBase) }}" class="cc-tab {{ $channel === 'message' ? 'is-active' : '' }}">
                    <span class="cc-label"><i class="fa-regular fa-envelope"></i><span>Mesajlar</span></span>
                    <span class="cc-badge {{ $messageCount > 0 ? 'hot' : 'zero' }}">{{ $messageCount }}</span>
                </a>
                <a href="{{ route('portal.communication-center.reviews', $queryBase) }}" class="cc-tab {{ $channel === 'review' ? 'is-active' : '' }}">
                    <span class="cc-label"><i class="fa-regular fa-comments"></i><span>Yorumlar</span></span>
                    <span class="cc-badge {{ $reviewCount > 0 ? 'hot' : 'zero' }}">{{ $reviewCount }}</span>
                </a>
            </div>

            <div class="flex-1 min-w-[320px] flex items-center justify-center">
                <div class="cc-market-grid">
                    <button type="button" class="cc-market-chip {{ $filters['marketplaceId'] === 0 ? 'is-active' : '' }}" data-marketplace-id="">
                        <span class="cc-market-logo-wrap">
                            <span class="cc-market-logo-fallback cc-market-logo-fallback-all"><i class="fa-solid fa-layer-group"></i><span>TÜMÜ</span></span>
                        </span>
                        <span class="cc-market-name">Tümü</span>
                        @if($allMarketplaceCount > 0)
                            <span class="cc-badge hot">{{ $allMarketplaceCount }}</span>
                        @endif
                    </button>
                    @foreach($marketplaces as $marketplace)
                        @php
                            $marketCount = (int) ($marketplaceCounts[$marketplace->id] ?? 0);
                            $marketLogo = $marketplaceLogoUrl($marketplace->name);
                            $marketInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $marketplace->name, 0, 2));
                        @endphp
                        <button type="button" class="cc-market-chip {{ $filters['marketplaceId'] === $marketplace->id ? 'is-active' : '' }}" data-marketplace-id="{{ $marketplace->id }}">
                            <span class="cc-market-logo-wrap">
                                @if($marketLogo)
                                    <img src="{{ $marketLogo }}" alt="{{ $marketplace->name }}" class="cc-market-logo">
                                @else
                                    <span class="cc-market-logo-fallback">{{ $marketInitials }}</span>
                                @endif
                            </span>
                            <span class="cc-market-name">{{ $marketplace->name }}</span>
                            @if($marketCount > 0)
                                <span class="cc-badge hot">{{ $marketCount }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('portal.communication-center.sync-now') }}">
                @csrf
                <button type="submit" class="btn btn-solid-accent cc-sync-now-btn">
                    <i class="fa-solid fa-rotate-right text-xs"></i>
                    <span>Hemen Senkronize Et</span>
                </button>
            </form>
        </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-4 xl:items-start">
            <form method="GET" id="cc-filters-form" class="cc-filter-card p-4 space-y-4 xl:col-span-1 self-start h-fit">
                <h3 class="text-sm font-semibold text-slate-900">Filtreler</h3>

                <input type="hidden" name="marketplace_id" id="cc-marketplace-input" value="{{ $filters['marketplaceId'] ?: '' }}">

                <div class="cc-filter-section">
                    <label class="cc-filter-title">Mağaza</label>
                    <select name="store_id" class="hidden">
                        <option value="">Tümü</option>
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}" @selected($filters['storeId'] === $store->id)>{{ $store->store_name }}</option>
                        @endforeach
                    </select>
                    <div class="cc-store-grid mt-2">
                        <button type="button" class="cc-store-chip {{ $filters['storeId'] === 0 ? 'is-active' : '' }}" data-store-value="">
                            <span class="cc-chip-label"><i class="fa-solid fa-layer-group"></i><span>Tümü</span></span>
                            @if($allStoreCount > 0)
                                <span class="cc-badge hot">{{ $allStoreCount }}</span>
                            @endif
                        </button>
                        @foreach($stores as $store)
                            @php $storeCount = (int) ($storeCounts[$store->id] ?? 0); @endphp
                            <button type="button" class="cc-store-chip {{ $filters['storeId'] === $store->id ? 'is-active' : '' }}" data-store-value="{{ $store->id }}">
                                <span class="cc-chip-label"><i class="fa-solid fa-shop"></i><span>{{ $store->store_name }}</span></span>
                                @if($storeCount > 0)
                                    <span class="cc-badge hot">{{ $storeCount }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="cc-filter-section">
                    <label class="cc-filter-title">Durum</label>
                    <select name="status" class="hidden">
                        <option value="">Tümü</option>
                        @foreach(['open' => 'Açık', 'pending' => 'Beklemede', 'answered' => 'Yanıtlandı', 'overdue' => 'Gecikmiş'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="cc-status-grid mt-2">
                        <button type="button" class="cc-status-chip {{ $filters['status'] === '' ? 'is-active' : '' }}" data-status-value="">
                            <span class="cc-chip-label"><i class="fa-solid fa-layer-group"></i><span>Tümü</span></span>
                            @if($awaitingStatusCount > 0)
                                <span class="cc-badge hot">{{ $awaitingStatusCount }}</span>
                            @endif
                        </button>
                        <button type="button" class="cc-status-chip {{ $filters['status'] === 'open' ? 'is-active' : '' }}" data-status-value="open">
                            <span class="cc-chip-label"><i class="fa-solid fa-folder-open"></i><span>Açık</span></span>
                            @if(((int) ($statusCounts['open'] ?? 0)) > 0)
                                <span class="cc-badge hot">{{ (int) ($statusCounts['open'] ?? 0) }}</span>
                            @endif
                        </button>
                        <button type="button" class="cc-status-chip {{ $filters['status'] === 'pending' ? 'is-active' : '' }}" data-status-value="pending">
                            <span class="cc-chip-label"><i class="fa-regular fa-hourglass-half"></i><span>Beklemede</span></span>
                            @if(((int) ($statusCounts['pending'] ?? 0)) > 0)
                                <span class="cc-badge hot">{{ (int) ($statusCounts['pending'] ?? 0) }}</span>
                            @endif
                        </button>
                        <button type="button" class="cc-status-chip {{ $filters['status'] === 'answered' ? 'is-active' : '' }}" data-status-value="answered">
                            <span class="cc-chip-label"><i class="fa-regular fa-circle-check"></i><span>Yanıtlandı</span></span><span class="cc-badge {{ $answeredStatusCount > 0 ? 'done' : 'zero' }}">{{ $answeredStatusCount }}</span>
                        </button>
                        <button type="button" class="cc-status-chip {{ $filters['status'] === 'overdue' ? 'is-active' : '' }}" data-status-value="overdue">
                            <span class="cc-chip-label"><i class="fa-regular fa-clock"></i><span>Gecikmiş</span></span>
                            @if(((int) ($statusCounts['overdue'] ?? 0)) > 0)
                                <span class="cc-badge hot">{{ (int) ($statusCounts['overdue'] ?? 0) }}</span>
                            @endif
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="cc-field-label"><i class="fa-regular fa-calendar"></i><span>Başlangıç</span></label>
                        <input type="date" name="date_from" value="{{ $filters['dateFrom'] }}" class="cc-date-input">
                    </div>
                    <div>
                        <label class="cc-field-label"><i class="fa-regular fa-calendar-check"></i><span>Bitiş</span></label>
                        <input type="date" name="date_to" value="{{ $filters['dateTo'] }}" class="cc-date-input">
                    </div>
                </div>

                <div>
                    <label class="cc-field-label"><i class="fa-solid fa-magnifying-glass"></i><span>Arama</span></label>
                    <input type="text" name="search" value="{{ $filters['search'] }}" class="cc-search-input" placeholder="Müşteri, ürün, konu">
                </div>

                <button class="cc-apply-btn"><i class="fa-solid fa-sliders"></i><span>Uygula</span></button>
                <div class="cc-filter-divider" aria-hidden="true"></div>

                <div class="cc-template-builder" data-template-builder data-template-store-url="{{ route('portal.communication-center.templates.store') }}">
                    <button type="button" class="cc-template-head" data-template-toggle>
                        <span class="cc-template-spacer" aria-hidden="true"></span>
                        <div class="cc-template-title"><i class="fa-regular fa-note-sticky"></i><span>Sablon Olustur</span></div>
                        <i class="fa-solid fa-chevron-down cc-template-chevron"></i>
                    </button>

                    <div class="cc-template-panel" data-template-panel>
                    <div class="space-y-2">
                        <div>
                            <div class="cc-template-row-head">
                                <label class="cc-field-label !mb-0"><i class="fa-regular fa-bookmark"></i><span>Konusu</span></label>
                                <div class="cc-template-hint">Hazir sablon listesine eklenir</div>
                            </div>
                            <input type="text" class="cc-template-input" maxlength="255" placeholder="Orn: Kargo Bilgilendirme" data-template-title-input>
                        </div>
                        <div>
                            <label class="cc-field-label"><i class="fa-regular fa-pen-to-square"></i><span>Icerigi</span></label>
                            <textarea class="cc-template-textarea" maxlength="5000" placeholder="Musteriye gondereceginiz yanit metnini yazin..." data-template-body-input></textarea>
                        </div>
                    </div>

                    <button type="button" class="cc-template-save mt-2" data-template-create-btn>
                        <i class="fa-solid fa-plus"></i>
                        <span>Sablonu Kaydet</span>
                    </button>

                    <div class="cc-template-feedback" data-template-feedback></div>
                    </div>
                </div>
            </form>

            <section class="cc-card p-4 xl:col-span-3 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                            <th class="py-3 pr-3">Pazaryeri</th>
                            <th class="py-3 pr-3">Mağaza</th>
                            <th class="py-3 pr-3">Kanal</th>
                            <th class="py-3 pr-3">Ürün</th>
                            <th class="py-3 pr-3">Müşteri</th>
                            <th class="py-3 pr-3">Durum</th>
                            <th class="py-3 pr-3">Süre</th>
                            <th class="py-3 pr-3">Öncelik</th>
                            <th class="py-3 pr-3 text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($threads as $thread)
                            @php
                                $isOverdue = $thread->due_at && $thread->due_at->isPast();
                                $isCritical = !$isOverdue && $thread->due_at && now()->diffInMinutes($thread->due_at, false) <= $criticalThresholdMinutes;
                                $threadMarketplaceName = (string) ($thread->marketplace?->name ?? '');
                                $threadMarketplaceLogo = $marketplaceLogoUrl($threadMarketplaceName);
                                $threadMarketplaceInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($threadMarketplaceName !== '' ? $threadMarketplaceName : '-', 0, 2));
                                $statusClass = match ($thread->status) {
                                    'open' => 'cc-status-open',
                                    'pending' => 'cc-status-pending',
                                    'answered' => 'cc-status-answered',
                                    'overdue' => 'cc-status-overdue',
                                    'closed' => 'cc-status-closed',
                                    default => 'cc-status-closed',
                                };
                                $statusIcon = match ($thread->status) {
                                    'open' => 'fa-folder-open',
                                    'pending' => 'fa-hourglass-half',
                                    'answered' => 'fa-circle-check',
                                    'overdue' => 'fa-clock',
                                    'closed' => 'fa-circle-xmark',
                                    default => 'fa-circle-info',
                                };
                                $canReply = $thread->last_inbound_at
                                    && (!$thread->last_outbound_at || $thread->last_outbound_at->lt($thread->last_inbound_at));
                            @endphp
                            <tr class="border-b border-slate-100 transition hover:bg-slate-50/70">
                                <td class="py-3 pr-3">
                                    <div class="cc-market-cell-card">
                                        <span class="cc-market-logo-wrap">
                                            @if($threadMarketplaceLogo)
                                                <img src="{{ $threadMarketplaceLogo }}" alt="{{ $threadMarketplaceName }}" class="cc-market-logo">
                                            @else
                                                <span class="cc-market-logo-fallback">{{ $threadMarketplaceInitials }}</span>
                                            @endif
                                        </span>
                                        <span class="cc-market-cell-name">{{ $threadMarketplaceName !== '' ? $threadMarketplaceName : '-' }}</span>
                                    </div>
                                </td>
                                <td class="py-3 pr-3 font-medium text-slate-800">{{ $thread->marketplaceStore?->store_name ?? '-' }}</td>
                                <td class="py-3 pr-3">
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                        {{ $channelLabels[$thread->channel] ?? $thread->channel }}
                                    </span>
                                </td>
                                <td class="py-3 pr-3 text-slate-700">{{ $thread->product_name ?: '-' }}</td>
                                <td class="py-3 pr-3 text-slate-700">{{ $thread->customer_name ?: '-' }}</td>
                                <td class="py-3 pr-3">
                                    <span class="cc-status-text {{ $statusClass }}" data-thread-status="{{ $thread->id }}">
                                        <i class="fa-regular {{ $statusIcon }}"></i>
                                        {{ $statusLabels[$thread->status] ?? $thread->status }}
                                    </span>
                                </td>
                                <td class="py-3 pr-3" data-thread-deadline="{{ $thread->id }}">
                                    @if($thread->status === 'answered')
                                        <span class="text-slate-700">{{ optional($thread->last_outbound_at)->format('d.m.Y H:i') ?: '-' }}</span>
                                    @elseif($isOverdue)
                                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">GECİKTİ</span>
                                    @elseif($isCritical)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700">KRİTİK</span>
                                    @else
                                        <span class="text-slate-700">{{ optional($thread->due_at)->format('d.m.Y H:i') ?: '-' }}</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-3 font-semibold text-slate-900">{{ $thread->priority_score }}</td>
                                <td class="py-3 pr-3 text-right">
                                    <button type="button" class="cc-expand-btn" data-thread-toggle="{{ $thread->id }}">
                                        <span>Aç</span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr class="cc-detail-row" data-thread-detail="{{ $thread->id }}">
                                <td colspan="9" class="py-0 pr-0">
                                    <div class="cc-inline-wrap" data-inline-wrap="{{ $thread->id }}" data-inline-channel="{{ $thread->channel }}">
                                        <div class="cc-inline-meta">
                                            <div class="cc-inline-meta-item">
                                                <div class="cc-inline-meta-k"><i class="fa-solid fa-hashtag mr-1"></i>Konu</div>
                                                <div class="cc-inline-meta-v">{{ $thread->subject ?: '-' }}</div>
                                            </div>
                                            <div class="cc-inline-meta-item">
                                                <div class="cc-inline-meta-k"><i class="fa-regular fa-user mr-1"></i>Müşteri</div>
                                                <div class="cc-inline-meta-v">{{ $thread->customer_name ?: '-' }}</div>
                                            </div>
                                            <div class="cc-inline-meta-item">
                                                <div class="cc-inline-meta-k"><i class="fa-solid fa-box-open mr-1"></i>Ürün</div>
                                                <div class="cc-inline-meta-v">{{ $thread->product_name ?: '-' }}</div>
                                            </div>
                                            <div class="cc-inline-meta-item">
                                                <div class="cc-inline-meta-k"><i class="fa-regular fa-clock mr-1"></i>Oluşturma</div>
                                                <div class="cc-inline-meta-v">{{ optional($thread->created_at)->format('d.m.Y H:i') ?: '-' }}</div>
                                            </div>
                                        </div>

                                        <div class="cc-inline-chat">
                                            @forelse($thread->messages as $message)
                                                <div class="cc-inline-msg {{ $message->direction === 'outbound' ? 'out' : 'in' }}" @if($message->direction === 'outbound') data-inline-outbound-id="{{ $message->id }}" @endif>
                                                    @if($message->direction === 'outbound')
                                                        <button type="button" class="cc-inline-edit-btn" data-inline-edit-trigger="{{ $thread->id }}" data-edit-message-id="{{ $message->id }}" title="Duzenle">
                                                            <i class="fa-solid fa-pen text-[10px]"></i>
                                                        </button>
                                                    @endif
                                                    <div class="cc-inline-msg-body">{{ $message->body }}</div>
                                                    <div class="cc-inline-time">{{ optional($message->created_at_external ?? $message->created_at)->format('d.m.Y H:i') }}</div>
                                                </div>
                                            @empty
                                                <div class="text-sm text-slate-500">Henüz mesaj yok.</div>
                                            @endforelse
                                        </div>

                                        <div class="cc-inline-compose">
                                            <form method="POST" action="{{ route('portal.communication-center.thread.reply', $thread) }}" data-inline-reply-form="{{ $thread->id }}" data-can-reply="{{ $canReply ? '1' : '0' }}">
                                                @csrf
                                                <input type="hidden" name="used_ai" value="0" data-inline-used-ai="{{ $thread->id }}">
                                                <input type="hidden" name="ai_template_id" value="" data-inline-template-id="{{ $thread->id }}">
                                                <input type="hidden" name="ai_confidence" value="" data-inline-confidence="{{ $thread->id }}">
                                                <input type="hidden" name="edit_message_id" value="" data-inline-edit-id="{{ $thread->id }}">

                                                <div class="cc-inline-tools">
                                                    <select class="w-full" data-inline-template-select="{{ $thread->id }}">
                                                        <option value="">Hazır şablon seçin</option>
                                                        @foreach($templates as $template)
                                                            <option value="{{ $template->body }}" data-template-id="{{ $template->id }}">{{ $template->title }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-outline inline-flex items-center gap-2" data-inline-ai-btn="{{ $thread->id }}">
                                                        <i class="fa-solid fa-wand-magic-sparkles"></i><span>AI Öner</span>
                                                    </button>
                                                </div>

                                                <div class="hidden rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-900 mt-2" data-inline-ai-info="{{ $thread->id }}"></div>
                                                <div class="cc-inline-success" data-inline-success="{{ $thread->id }}"></div>
                                                <div class="cc-inline-editing-note" data-inline-edit-note="{{ $thread->id }}"></div>
                                                @unless($canReply)
                                                    <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-800">
                                                        Müşteri yanıtı bekleniyor. Yeni mesaj gelmeden tekrar cevap veremezsiniz.
                                                    </div>
                                                @endunless

                                                <div class="cc-inline-box">
                                                    <div class="cc-inline-box-head">
                                                        <span><i class="fa-regular fa-pen-to-square mr-1"></i>Yanıt Metni</span>
                                                        <span data-inline-char="{{ $thread->id }}">0 karakter</span>
                                                    </div>
                                                    <textarea name="body" required data-inline-body="{{ $thread->id }}" @disabled(!$canReply)></textarea>
                                                </div>

                                                <div class="cc-inline-actions">
                                                    <button type="submit" class="cc-inline-send" @disabled(!$canReply)><i class="fa-regular fa-paper-plane"></i><span>Gönder</span></button>
                                                    <button type="button" class="btn btn-outline hidden" data-inline-edit-cancel="{{ $thread->id }}"><i class="fa-solid fa-rotate-left"></i><span>Iptal</span></button>
                                                    <a href="{{ route('portal.communication-center.thread.show', $thread) }}" class="cc-detail-link">
                                                        <i class="fa-solid fa-up-right-from-square"></i>
                                                        <span>Sayfada Aç</span>
                                                    </a>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-12 text-center text-sm text-slate-500">Kayıt bulunamadı.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @include('admin.partials.modern-pagination-bar', [
                    'paginator' => $threads,
                    'perPageName' => 'per_page',
                    'perPageLabel' => 'Sayfa başına',
                    'perPageOptions' => [10, 25, 50, 100],
                ])
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const marketplaceInput = document.getElementById('cc-marketplace-input');
            const filterForm = document.getElementById('cc-filters-form');
            const statusSelect = filterForm ? filterForm.querySelector('select[name="status"]') : null;
            const storeSelect = filterForm ? filterForm.querySelector('select[name="store_id"]') : null;
            const templateBuilder = document.querySelector('[data-template-builder]');
            if (!marketplaceInput) return;

            document.querySelectorAll('[data-marketplace-id]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    const value = chip.getAttribute('data-marketplace-id') || '';
                    marketplaceInput.value = value;

                    document.querySelectorAll('[data-marketplace-id]').forEach(function (el) {
                        el.classList.remove('is-active');
                    });
                    chip.classList.add('is-active');

                    if (filterForm) {
                        filterForm.submit();
                    }
                });
            });

            document.querySelectorAll('[data-status-value]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    if (!statusSelect || !filterForm) return;
                    statusSelect.value = chip.getAttribute('data-status-value') || '';

                    document.querySelectorAll('[data-status-value]').forEach(function (el) {
                        el.classList.remove('is-active');
                    });
                    chip.classList.add('is-active');

                    filterForm.submit();
                });
            });

            document.querySelectorAll('[data-store-value]').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    if (!storeSelect || !filterForm) return;
                    storeSelect.value = chip.getAttribute('data-store-value') || '';

                    document.querySelectorAll('[data-store-value]').forEach(function (el) {
                        el.classList.remove('is-active');
                    });
                    chip.classList.add('is-active');

                    filterForm.submit();
                });
            });

            document.querySelectorAll('[data-thread-toggle]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const id = btn.getAttribute('data-thread-toggle');
                    const row = document.querySelector('[data-thread-detail="' + id + '"]');
                    if (!row) return;
                    const isOpen = row.classList.contains('is-open');
                    const label = btn.querySelector('span');
                    row.classList.remove('is-sending-close');
                    row.classList.toggle('is-open', !isOpen);
                    btn.classList.toggle('is-open', !isOpen);
                    if (label) label.textContent = isOpen ? 'A\u00E7' : 'Kapat';
                });
            });

            const aiSuggestBase = "{{ url('/communication-center/thread') }}";
            const csrf = "{{ csrf_token() }}";

            const appendTemplateToSelects = (template) => {
                if (!template || !template.id) return;
                document.querySelectorAll('[data-inline-template-select]').forEach((select) => {
                    const exists = Array.from(select.options).some(
                        (option) => String(option.dataset.templateId || '') === String(template.id)
                    );
                    if (exists) return;
                    const option = new Option(template.title || 'Yeni Sablon', template.body || '');
                    option.dataset.templateId = String(template.id);
                    select.appendChild(option);
                });
            };

            if (templateBuilder) {
                const storeUrl = templateBuilder.getAttribute('data-template-store-url') || '';
                const toggleBtn = templateBuilder.querySelector('[data-template-toggle]');
                const panel = templateBuilder.querySelector('[data-template-panel]');
                const titleInput = templateBuilder.querySelector('[data-template-title-input]');
                const bodyInput = templateBuilder.querySelector('[data-template-body-input]');
                const createBtn = templateBuilder.querySelector('[data-template-create-btn]');
                const feedback = templateBuilder.querySelector('[data-template-feedback]');

                if (toggleBtn && panel) {
                    toggleBtn.addEventListener('click', () => {
                        templateBuilder.classList.toggle('is-open');
                    });
                }

                const setTemplateFeedback = (text, isError = false) => {
                    if (!feedback) return;
                    feedback.textContent = text;
                    feedback.classList.add('show');
                    feedback.classList.toggle('is-error', !!isError);
                };

                createBtn?.addEventListener('click', async () => {
                    const title = (titleInput?.value || '').trim();
                    const body = (bodyInput?.value || '').trim();

                    if (!title || !body) {
                        setTemplateFeedback('Konu ve icerik alani zorunludur.', true);
                        return;
                    }
                    if (!storeUrl) {
                        setTemplateFeedback('Sablon kaydetme adresi bulunamadi.', true);
                        return;
                    }
                    if (createBtn.dataset.submitting === '1') return;

                    createBtn.dataset.submitting = '1';
                    const originalText = createBtn.innerHTML;
                    createBtn.setAttribute('disabled', 'disabled');
                    createBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Kaydediliyor</span>';

                    try {
                        const res = await fetch(storeUrl, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ title, body }),
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok || !data?.ok) {
                            const message = data?.errors?.title?.[0]
                                || data?.errors?.body?.[0]
                                || data?.message
                                || 'Sablon kaydedilemedi.';
                            setTemplateFeedback(message, true);
                            return;
                        }

                        appendTemplateToSelects(data.template || {});
                        if (titleInput) titleInput.value = '';
                        if (bodyInput) bodyInput.value = '';
                        setTemplateFeedback('Sablon kaydedildi. Hazir sablon listesine eklendi.', false);
                    } catch (error) {
                        setTemplateFeedback('Ag hatasi nedeniyle sablon kaydedilemedi.', true);
                    } finally {
                        createBtn.dataset.submitting = '0';
                        createBtn.removeAttribute('disabled');
                        createBtn.innerHTML = originalText;
                    }
                });
            }

            const updateInlineCount = (id) => {
                const body = document.querySelector('[data-inline-body="' + id + '"]');
                const counter = document.querySelector('[data-inline-char="' + id + '"]');
                if (!body || !counter) return;
                counter.textContent = `${body.value.length} karakter`;
            };

            document.querySelectorAll('[data-inline-template-select]').forEach((select) => {
                select.addEventListener('change', () => {
                    const id = select.getAttribute('data-inline-template-select');
                    const body = document.querySelector('[data-inline-body="' + id + '"]');
                    const usedAi = document.querySelector('[data-inline-used-ai="' + id + '"]');
                    const tplId = document.querySelector('[data-inline-template-id="' + id + '"]');
                    const conf = document.querySelector('[data-inline-confidence="' + id + '"]');
                    const info = document.querySelector('[data-inline-ai-info="' + id + '"]');
                    if (body) body.value = select.value || '';
                    const selectedOption = select.options[select.selectedIndex];
                    if (tplId) tplId.value = selectedOption?.dataset?.templateId || '';
                    if (usedAi) usedAi.value = '0';
                    if (conf) conf.value = '';
                    if (info) info.classList.add('hidden');
                    updateInlineCount(id);
                });
            });

            document.querySelectorAll('[data-inline-quick]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const id = btn.getAttribute('data-inline-quick');
                    const body = document.querySelector('[data-inline-body="' + id + '"]');
                    const usedAi = document.querySelector('[data-inline-used-ai="' + id + '"]');
                    const tplId = document.querySelector('[data-inline-template-id="' + id + '"]');
                    const conf = document.querySelector('[data-inline-confidence="' + id + '"]');
                    const info = document.querySelector('[data-inline-ai-info="' + id + '"]');
                    if (body) body.value = btn.getAttribute('data-value') || '';
                    if (usedAi) usedAi.value = '0';
                    if (tplId) tplId.value = '';
                    if (conf) conf.value = '';
                    if (info) info.classList.add('hidden');
                    updateInlineCount(id);
                });
            });

            document.querySelectorAll('[data-inline-body]').forEach((input) => {
                const id = input.getAttribute('data-inline-body');
                updateInlineCount(id);
                input.addEventListener('input', () => updateInlineCount(id));
            });

            document.querySelectorAll('[data-inline-ai-btn]').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const id = btn.getAttribute('data-inline-ai-btn');
                    const body = document.querySelector('[data-inline-body="' + id + '"]');
                    const usedAi = document.querySelector('[data-inline-used-ai="' + id + '"]');
                    const tplId = document.querySelector('[data-inline-template-id="' + id + '"]');
                    const conf = document.querySelector('[data-inline-confidence="' + id + '"]');
                    const info = document.querySelector('[data-inline-ai-info="' + id + '"]');
                    const tplSelect = document.querySelector('[data-inline-template-select="' + id + '"]');

                    const res = await fetch(`${aiSuggestBase}/${id}/ai-suggest`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                        },
                    });
                    if (!res.ok) return;

                    const data = await res.json();
                    if (body && data.suggested_text) {
                        body.value = data.suggested_text;
                    }
                    if (usedAi) usedAi.value = '1';
                    if (tplId) tplId.value = data.template_id ?? '';
                    if (conf) conf.value = data.confidence ?? '';
                    if (tplSelect && data.template_id) {
                        const option = Array.from(tplSelect.options)
                            .find((item) => String(item.dataset.templateId || '') === String(data.template_id));
                        if (option) tplSelect.value = option.value;
                    }
                    if (info) {
                        const reasons = Array.isArray(data.reason)
                            ? data.reason.map((x) => String(x).replace('keyword:', '')).join(', ')
                            : '';
                        const source = data.source ? ` | Kaynak: ${data.source}` : '';
                        info.textContent = `AI Güven: ${data.confidence ?? 0}%${source}${reasons ? ` (${reasons})` : ''}`;
                        info.classList.remove('hidden');
                    }
                    updateInlineCount(id);
                });
            });

            const channelLabel = (channel) => {
                if (channel === 'review') return 'Yorum';
                if (channel === 'message') return 'Mesaj';
                return 'Soru';
            };

            const resetEditMode = (id, keepLock = true) => {
                const form = document.querySelector('[data-inline-reply-form="' + id + '"]');
                const body = document.querySelector('[data-inline-body="' + id + '"]');
                const submitBtn = form ? form.querySelector('.cc-inline-send') : null;
                const templateSelect = document.querySelector('[data-inline-template-select="' + id + '"]');
                const aiBtn = document.querySelector('[data-inline-ai-btn="' + id + '"]');
                const editIdInput = document.querySelector('[data-inline-edit-id="' + id + '"]');
                const editNote = document.querySelector('[data-inline-edit-note="' + id + '"]');
                const cancelBtn = document.querySelector('[data-inline-edit-cancel="' + id + '"]');
                const canReply = form && form.dataset.canReply === '1';

                if (editIdInput) editIdInput.value = '';
                if (editNote) {
                    editNote.textContent = '';
                    editNote.classList.remove('show');
                }
                if (cancelBtn) cancelBtn.classList.add('hidden');

                if (!keepLock && canReply) {
                    body?.removeAttribute('disabled');
                    submitBtn?.removeAttribute('disabled');
                    templateSelect?.removeAttribute('disabled');
                    aiBtn?.removeAttribute('disabled');
                    if (form) form.dataset.replyLocked = '0';
                    return;
                }

                if (!canReply) {
                    body?.setAttribute('disabled', 'disabled');
                    submitBtn?.setAttribute('disabled', 'disabled');
                    templateSelect?.setAttribute('disabled', 'disabled');
                    aiBtn?.setAttribute('disabled', 'disabled');
                    if (form) form.dataset.replyLocked = '1';
                }
            };

            document.addEventListener('click', (event) => {
                const editBtn = event.target.closest('[data-inline-edit-trigger]');
                if (editBtn) {
                    const id = editBtn.getAttribute('data-inline-edit-trigger');
                    const messageId = editBtn.getAttribute('data-edit-message-id') || '';
                    const form = document.querySelector('[data-inline-reply-form="' + id + '"]');
                    const body = document.querySelector('[data-inline-body="' + id + '"]');
                    const submitBtn = form ? form.querySelector('.cc-inline-send') : null;
                    const templateSelect = document.querySelector('[data-inline-template-select="' + id + '"]');
                    const aiBtn = document.querySelector('[data-inline-ai-btn="' + id + '"]');
                    const editIdInput = document.querySelector('[data-inline-edit-id="' + id + '"]');
                    const editNote = document.querySelector('[data-inline-edit-note="' + id + '"]');
                    const cancelBtn = document.querySelector('[data-inline-edit-cancel="' + id + '"]');
                    const bodyNode = editBtn.closest('.cc-inline-msg')?.querySelector('.cc-inline-msg-body');
                    const messageText = bodyNode ? bodyNode.textContent.trim() : '';

                    if (editIdInput) editIdInput.value = messageId;
                    if (body) {
                        body.removeAttribute('disabled');
                        body.value = messageText;
                        body.focus();
                    }
                    if (form) form.dataset.replyLocked = '0';
                    submitBtn?.removeAttribute('disabled');
                    templateSelect?.removeAttribute('disabled');
                    aiBtn?.removeAttribute('disabled');

                    if (editNote) {
                        editNote.textContent = 'Duzenleme modu aktif. Metni guncelleyip Gonder ile kaydedin.';
                        editNote.classList.add('show');
                    }
                    if (cancelBtn) cancelBtn.classList.remove('hidden');
                    updateInlineCount(id);
                    return;
                }

                const cancelBtn = event.target.closest('[data-inline-edit-cancel]');
                if (cancelBtn) {
                    const id = cancelBtn.getAttribute('data-inline-edit-cancel');
                    resetEditMode(id, true);
                    return;
                }
            });

            document.querySelectorAll('[data-inline-reply-form]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const id = form.getAttribute('data-inline-reply-form');
                    const wrap = document.querySelector('[data-inline-wrap="' + id + '"]');
                    const body = document.querySelector('[data-inline-body="' + id + '"]');
                    const success = document.querySelector('[data-inline-success="' + id + '"]');
                    const info = document.querySelector('[data-inline-ai-info="' + id + '"]');
                    const chat = wrap ? wrap.querySelector('.cc-inline-chat') : null;
                    const channel = wrap ? wrap.getAttribute('data-inline-channel') : '';
                    if (!body || body.value.trim() === '') return;
                    if (form.dataset.submitting === '1') return;

                    const submitBtn = form.querySelector('.cc-inline-send');
                    const templateSelect = document.querySelector('[data-inline-template-select="' + id + '"]');
                    const aiBtn = document.querySelector('[data-inline-ai-btn="' + id + '"]');
                    const editIdInput = document.querySelector('[data-inline-edit-id="' + id + '"]');
                    const isEditing = !!(editIdInput && editIdInput.value);
                    const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

                    form.dataset.submitting = '1';
                    if (submitBtn) {
                        submitBtn.setAttribute('disabled', 'disabled');
                        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i><span>Gönderiliyor</span>';
                    }

                    try {
                        const fd = new FormData(form);
                        const controller = new AbortController();
                        const timer = window.setTimeout(() => controller.abort(), 20000);

                        const res = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: fd,
                            signal: controller.signal,
                        });

                        window.clearTimeout(timer);

                        if (!res.ok) {
                            try {
                                const errorPayload = await res.json();
                                const message = errorPayload?.errors?.body?.[0]
                                    || errorPayload?.message
                                    || 'Yanit gonderilemedi.';
                                if (info) {
                                    info.textContent = message;
                                    info.classList.remove('hidden');
                                }
                            } catch (e) {
                                if (info) {
                                    info.textContent = 'Yanit gonderilemedi.';
                                    info.classList.remove('hidden');
                                }
                            }
                            return;
                        }

                        const data = await res.json();
                        if (!(data?.ok)) return;

                        if (chat) {
                            if (data.edited && data.message_id) {
                                const current = chat.querySelector('[data-inline-outbound-id="' + String(data.message_id) + '"]');
                                if (current) {
                                    const bodyNode = current.querySelector('.cc-inline-msg-body');
                                    const timeNode = current.querySelector('.cc-inline-time');
                                    if (bodyNode) bodyNode.textContent = String(data.body || '');
                                    if (timeNode) timeNode.textContent = data.created_at || '';
                                }
                            } else {
                                const msg = document.createElement('div');
                                msg.className = 'cc-inline-msg out';
                                if (data.message_id) msg.setAttribute('data-inline-outbound-id', String(data.message_id));
                                msg.innerHTML = `<button type="button" class="cc-inline-edit-btn" data-inline-edit-trigger="${id}" data-edit-message-id="${data.message_id || ''}" title="Duzenle"><i class="fa-solid fa-pen text-[10px]"></i></button><div class="cc-inline-msg-body">${String(data.body || '').replace(/</g, '&lt;')}</div><div class="cc-inline-time">${data.created_at || ''}</div>`;
                                chat.appendChild(msg);
                                chat.scrollTop = chat.scrollHeight;
                            }
                        }

                        body.value = '';
                        updateInlineCount(id);
                        if (info) info.classList.add('hidden');

                        const usedAi = document.querySelector('[data-inline-used-ai="' + id + '"]');
                        const tplId = document.querySelector('[data-inline-template-id="' + id + '"]');
                        const conf = document.querySelector('[data-inline-confidence="' + id + '"]');
                        if (usedAi) usedAi.value = '0';
                        if (tplId) tplId.value = '';
                        if (conf) conf.value = '';
                        if (editIdInput) editIdInput.value = '';
                        resetEditMode(id, true);

                        if (success) {
                            success.innerHTML = `<i class="fa-regular fa-circle-check"></i><span>${channelLabel(channel)} basariyla cevaplandi.</span>`;
                            success.classList.add('show');
                        }

                        const rowStatus = document.querySelector('[data-thread-status="' + id + '"]');
                        if (rowStatus) {
                            rowStatus.classList.remove('cc-status-open', 'cc-status-pending', 'cc-status-overdue', 'cc-status-closed');
                            rowStatus.classList.add('cc-status-answered');
                            rowStatus.innerHTML = '<i class="fa-regular fa-circle-check"></i> Yan\u0131tland\u0131';
                        }
                        const rowDeadline = document.querySelector('[data-thread-deadline="' + id + '"]');
                        if (rowDeadline) {
                            rowDeadline.innerHTML = '<span class="text-slate-700">' + (data.created_at || '-') + '</span>';
                        }

                        if (!isEditing) {
                            body.setAttribute('disabled', 'disabled');
                            if (templateSelect) templateSelect.setAttribute('disabled', 'disabled');
                            if (aiBtn) aiBtn.setAttribute('disabled', 'disabled');
                            form.dataset.replyLocked = '1';
                        } else {
                            // Edit sonrası da tek-yanıt kuralı korunur.
                            body.setAttribute('disabled', 'disabled');
                            if (templateSelect) templateSelect.setAttribute('disabled', 'disabled');
                            if (aiBtn) aiBtn.setAttribute('disabled', 'disabled');
                            form.dataset.replyLocked = '1';
                        }

                        if (wrap) {
                            wrap.classList.add('sent-ok');
                            window.setTimeout(() => {
                                wrap.classList.remove('sent-ok');
                                success?.classList.remove('show');
                            }, 30000);
                        }

                        const row = document.querySelector('[data-thread-detail="' + id + '"]');
                        const toggleBtn = document.querySelector('[data-thread-toggle="' + id + '"]');
                        if (row) {
                            row.classList.add('is-sending-close');
                            window.setTimeout(() => {
                                row.classList.remove('is-open', 'is-sending-close');
                                if (toggleBtn) {
                                    toggleBtn.classList.remove('is-open');
                                    const label = toggleBtn.querySelector('span');
                                    if (label) label.textContent = 'A\u00E7';
                                }
                            }, 1100);
                        }
                    } catch (err) {
                        if (info) {
                            info.textContent = 'Ag hatasi nedeniyle gonderim tamamlanamadi.';
                            info.classList.remove('hidden');
                        }
                    } finally {
                        form.dataset.submitting = '0';
                        if (submitBtn) {
                            submitBtn.innerHTML = originalBtnHtml || '<i class="fa-regular fa-paper-plane"></i><span>Gonder</span>';
                            if (form.dataset.replyLocked === '1') {
                                submitBtn.setAttribute('disabled', 'disabled');
                            } else {
                                submitBtn.removeAttribute('disabled');
                            }
                        }
                    }
                });
            });
        });
    </script>
@endsection

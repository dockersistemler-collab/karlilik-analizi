<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pazaryeri Paneli</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        (function () {
            try {
                if (localStorage.getItem('adminSidebarPinned') === '1') {
                    document.documentElement.classList.add('admin-sidebar-pinned');
                }
            } catch (e) {}
        })();
    </script>
    <style>
        :root {
            --panel-ink: #0f172a;
            --panel-muted: #64748b;
            --panel-bg: #ffffff;
            --panel-card: #ffffff;
            --panel-border: #e2e8f0;
            --panel-primary: #0f172a;
            --panel-primary-dark: #0b1220;
            --panel-accent: #ff4439;
        }
        body {
            font-family: "Manrope", sans-serif;
            background: radial-gradient(circle at 10% 10%, #eff6ff 0%, transparent 45%),
                        radial-gradient(circle at 90% 5%, #ecfeff 0%, transparent 40%),
                        var(--panel-bg);
            color: var(--panel-ink);
        }
        .rich-content p,
        .ck-content p,
        .cke_editable p {
            margin: 0 0 0.75rem;
        }
        .rich-content ul,
        .rich-content ol,
        .ck-content ul,
        .ck-content ol,
        .cke_editable ul,
        .cke_editable ol {
            padding-left: 1.25rem;
            margin: 0 0 0.75rem;
        }
        .rich-content li,
        .ck-content li,
        .cke_editable li {
            margin: 0.25rem 0;
        }
        .rich-content h2,
        .rich-content h3,
        .ck-content h2,
        .ck-content h3,
        .cke_editable h2,
        .cke_editable h3 {
            margin: 1rem 0 0.5rem;
            font-weight: 600;
            color: #0f172a;
        }
        .rich-content blockquote,
        .ck-content blockquote,
        .cke_editable blockquote {
            border-left: 3px solid #e2e8f0;
            margin: 0.75rem 0;
            padding-left: 0.75rem;
            color: #475569;
        }
        .rich-content a,
        .ck-content a,
        .cke_editable a {
            color: #2563eb;
            text-decoration: underline;
        }
        .rounded-full,
        .rounded-2xl,
        .rounded-xl,
        .rounded-lg,
        .rounded-md {
            border-radius: 5px !important;
        }
        .sidebar {
            width: 76px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            position: fixed;
            top: 12px;
            left: 12px;
            height: calc(100vh - 24px);
            overflow-y: auto;
            transition: width 220ms ease;
        }
        .sidebar:hover {
            width: 260px;
        }
        .sidebar.is-pinned {
            width: 260px;
        }
        .admin-sidebar-pinned .sidebar {
            width: 260px;
        }
        .sidebar-brand {
            height: 56px;
            border-bottom: 1px solid var(--panel-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem;
            color: #1f2937;
            padding: 0 0.85rem;
        }
        .sidebar-label,
        .sidebar-section,
        .sidebar-action-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
            white-space: nowrap;
            transition: opacity 200ms ease, width 200ms ease;
        }
        .sidebar:hover .sidebar-label,
        .sidebar:hover .sidebar-section,
        .sidebar:hover .sidebar-action-text,
        .sidebar.is-pinned .sidebar-label,
        .sidebar.is-pinned .sidebar-section,
        .sidebar.is-pinned .sidebar-action-text {
            opacity: 1;
            width: auto;
        }
        .admin-sidebar-pinned .sidebar-label,
        .admin-sidebar-pinned .sidebar-section,
        .admin-sidebar-pinned .sidebar-action-text {
            opacity: 1;
            width: auto;
        }
        .sidebar-pin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 8px;
            border: 1px solid var(--panel-border);
            color: #64748b;
            background: #ffffff;
            transition: color 160ms ease, border 160ms ease, background 160ms ease;
            opacity: 0;
            pointer-events: none;
        }
        .sidebar-pin:hover {
            color: var(--panel-accent);
            border-color: #ffd4d1;
            background: #fff1f0;
        }
        .sidebar-pin.is-active {
            color: var(--panel-accent);
            border-color: #ffd4d1;
            background: #fff1f0;
        }
        .sidebar:hover .sidebar-pin,
        .sidebar.is-pinned .sidebar-pin {
            opacity: 1;
            pointer-events: auto;
        }
        .admin-sidebar-pinned .sidebar-pin {
            opacity: 1;
            pointer-events: auto;
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.65rem 0.85rem;
            border-radius: 14px;
            color: #374151;
            transition: color 160ms ease, background 160ms ease;
        }
        .sidebar-link i {
            color: #6b7280;
        }
        .sidebar-link:hover {
            color: var(--panel-accent);
            background: #fff1f0;
        }
        .sidebar-link:hover i {
            color: var(--panel-accent);
        }
        .sidebar-link.is-active {
            background: #fff1f0;
            color: var(--panel-accent);
        }
        .sidebar-link.is-active i {
            color: var(--panel-accent);
        }
        .sidebar-submenu {
            display: none;
            padding-left: 2rem;
            margin-top: 0.35rem;
        }
        .sidebar-submenu.is-open {
            display: block;
        }
        .sidebar:not(:hover):not(.is-pinned) .sidebar-submenu,
        .admin-sidebar-pinned .sidebar:not(:hover) .sidebar-submenu {
            display: none;
        }
        .sidebar-submenu .sidebar-link {
            padding: 0.5rem 0.75rem;
            border-radius: 12px;
        }
        .sidebar-submenu .sidebar-link i {
            font-size: 0.8rem;
        }
        .sidebar-section {
            padding: 0 0.85rem;
            font-size: 10px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #9ca3af;
            margin-top: 1rem;
        }
        .sidebar-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 26px;
            height: 26px;
            border-radius: 10px;
            border: 1px solid var(--panel-border);
            color: #6b7280;
            transition: color 160ms ease, border 160ms ease, background 160ms ease;
        }
        .sidebar-action:hover {
            color: var(--panel-accent);
            border-color: #ffd4d1;
            background: #fff1f0;
        }
        main {
            padding-left: 100px;
            transition: padding-left 220ms ease;
        }
        .sidebar:hover ~ main,
        .sidebar.is-pinned ~ main,
        .admin-sidebar-pinned main {
            padding-left: 284px;
        }
        .panel-card {
            background: var(--panel-card);
            border: 1px solid var(--panel-border);
            border-radius: 5px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }
        .panel-card.table-shell {
            box-shadow: none;
        }
        .panel-pill {
            border-radius: 999px;
            padding: 2px 10px;
        }
        main input,
        main select,
        main textarea {
            background: #ffffff !important;
            border: 1px solid var(--panel-border) !important;
            border-radius: 5px !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.9rem !important;
            color: var(--panel-ink) !important;
        }
        main input:focus,
        main select:focus,
        main textarea:focus {
            outline: none;
            border-color: #94a3b8 !important;
            box-shadow: 0 0 0 3px rgba(148, 163, 184, 0.22) !important;
        }
        main button:not(.btn):not(.topbar-icon) {
            padding: 0.45rem 1rem !important;
            font-size: 0.85rem !important;
            line-height: 1.2 !important;
            border-radius: 5px !important;
            border: 1px solid transparent !important;
            background-color: transparent !important;
            color: inherit !important;
            min-height: 36px !important;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.45rem 1rem;
            min-height: 36px;
            border-radius: 6px !important;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background-color 160ms ease, color 160ms ease, border-color 160ms ease;
        }
        .btn-outline-accent {
            border: 1px solid var(--panel-accent);
            color: var(--panel-accent);
            background: transparent;
        }
        .btn-outline-accent:hover {
            background: var(--panel-accent);
            color: #ffffff;
        }
        .btn-outline {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.45rem !important;
            padding: 0.45rem 1rem !important;
            min-height: 36px !important;
            border-radius: 6px !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            border-width: 1px !important;
            border-style: dashed !important;
            border-color: var(--panel-accent) !important;
            color: var(--panel-accent) !important;
            background: transparent !important;
        }
        .btn-outline:hover {
            background: #fff1f0 !important;
            color: var(--panel-accent) !important;
            border-color: var(--panel-accent) !important;
        }
        .btn-solid-accent {
            border: 1px solid var(--panel-accent);
            background: var(--panel-accent);
            color: #ffffff;
        }
        .btn-solid-accent:hover {
            background: #e83a31;
            border-color: #e83a31;
            color: #ffffff;
        }
        .tox button,
        .tox .tox-button,
        .tox .tox-button--secondary,
        .tox .tox-button--icon,
        .tox .tox-mbtn,
        .tox .tox-tbtn {
            background: transparent !important;
            color: inherit !important;
            padding: 0.25rem 0.45rem !important;
            border-radius: 4px !important;
            font-size: 0.75rem !important;
            line-height: 1.2 !important;
        }
        .tox .tox-button--primary {
            background: #111827 !important;
            color: #ffffff !important;
        }
        .tox .tox-tbtn:hover,
        .tox .tox-mbtn:hover,
        .tox .tox-button:hover {
            background: #f3f4f6 !important;
            color: inherit !important;
        }
        .tox .tox-edit-area__iframe {
            background: #ffffff !important;
        }
        main a.bg-blue-600,
        main a.bg-slate-900,
        main a.bg-amber-600,
        main a.bg-emerald-600,
        main a.bg-sky-600,
        main a.bg-indigo-600,
        main a.bg-teal-600 {
            padding: 0.45rem 0.9rem !important;
            font-size: 0.9rem !important;
            border-radius: 5px !important;
            background-color: #ff4439 !important;
        }
        main a.bg-blue-600:hover,
        main a.bg-slate-900:hover,
        main a.bg-amber-600:hover,
        main a.bg-emerald-600:hover,
        main a.bg-sky-600:hover,
        main a.bg-indigo-600:hover,
        main a.bg-teal-600:hover {
            background-color: #e83a31 !important;
        }
        main .shadow,
        main .shadow-sm {
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.05) !important;
        }
        main table th,
        main table td {
            padding: 0.65rem 0.85rem !important;
        }
        main table {
            border-collapse: separate !important;
            border-spacing: 0 8px !important;
        }
        main table thead {
            background: transparent !important;
        }
        main table thead.bg-slate-50 {
            background: transparent !important;
        }
        main table thead th {
            background: transparent !important;
            border-bottom: 1px solid var(--panel-border) !important;
        }
        main table thead th:first-child {
            border-top-left-radius: 10px !important;
            border-bottom-left-radius: 10px !important;
        }
        main table thead th:last-child {
            border-top-right-radius: 10px !important;
            border-bottom-right-radius: 10px !important;
        }
        main table tbody tr {
            background: #ffffff !important;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
        }
        main table tbody td:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }
        main table tbody td:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        main table tbody tr:hover {
            background: rgba(15, 23, 42, 0.03) !important;
        }
        main h2 {
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            letter-spacing: 0.01em;
        }
        main h3 {
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            color: #1f2937;
        }
        main h4 {
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            color: #334155;
        }
        main table thead th {
            font-size: 0.7rem !important;
            letter-spacing: 0.08em !important;
            text-transform: uppercase !important;
            color: #94a3b8 !important;
            font-weight: 600 !important;
        }
        .topbar-link {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: #475569;
        }
        .topbar-link:hover {
            color: #0f172a;
        }
        .topbar-icon {
            width: 34px;
            height: 34px;
            border-radius: 5px;
            border: 1px solid var(--panel-border);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #475569 !important;
            background: #ffffff;
        }
        .topbar-icon:hover {
            color: #0f172a;
            border-color: var(--panel-accent);
        }
        .quick-actions {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 60;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 12px;
        }
        .quick-actions-toggle {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            border: none;
            background: var(--panel-accent);
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.18);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: transform 160ms ease, box-shadow 160ms ease;
        }
        .quick-actions-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px rgba(15, 23, 42, 0.22);
        }
        .quick-actions-menu {
            background: #ffffff;
            border: 1px solid var(--panel-border);
            border-radius: 16px;
            padding: 10px;
            min-width: 200px;
            box-shadow: 0 18px 30px rgba(15, 23, 42, 0.12);
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .quick-actions-menu[hidden] {
            display: none !important;
        }
        .quick-actions-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 12px;
            color: #0f172a;
            transition: background 150ms ease, color 150ms ease;
        }
        .quick-actions-item:hover {
            background: #fff1f0;
            color: var(--panel-accent);
        }
        .quick-actions-icon {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #f8fafc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--panel-accent);
            border: 1px solid #f1f5f9;
        }
        main a.text-blue-600,
        main a.text-blue-700,
        main a.text-blue-900 {
            color: #ff4439 !important;
        }
        main a.text-blue-600:hover,
        main a.text-blue-700:hover,
        main a.text-blue-900:hover {
            color: #e83a31 !important;
        }
    </style>
</head>
<body class="antialiased">

    <div class="min-h-screen flex">

        <aside class="sidebar flex flex-col">
            <div class="sidebar-brand">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-store text-lg"></i>
                    <h1 class="text-sm font-semibold tracking-[0.32em] sidebar-label">PAZARYERİ</h1>
                </div>
                <button id="sidebar-pin-toggle" type="button" class="sidebar-pin" title="Sabitle">
                    <i class="fa-solid fa-thumbtack text-xs"></i>
                </button>
            </div>

            @php
                $subUser = auth('subuser')->user();
                $subPermissions = $subUser ? $subUser->permissions()->pluck('permission_key')->flip() : collect();
                $can = function (string $key) use ($subPermissions, $subUser) {
                    return !$subUser || $subPermissions->has($key);
                };
                $canReportsAny = !$subUser || $subPermissions->has('reports') || $subPermissions->keys()->contains(function ($key) {
                    return str_starts_with($key, 'reports.');
                });
                $canReports = function (string $key) use ($subPermissions, $subUser) {
                    return !$subUser || $subPermissions->has($key) || $subPermissions->has('reports');
                };
                $showStoreSection = $can('products') || $can('orders') || $can('customers');
                $showSettingsSection = $can('settings') || $canReportsAny;
                $showSupportSection = $can('tickets');
                $showIntegrationSection = $can('integrations') || $can('addons');
                $showSubscriptionSection = $can('subscription') || $can('invoices');
            @endphp

            <nav class="flex-1 px-3 py-4 space-y-2">

                @if($can('dashboard'))
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link is-active">
                        <i class="fa-solid fa-chart-line w-6"></i>
                        <span class="sidebar-label">Panel Özeti</span>
                    </a>
                @endif

                @if($showStoreSection)
                    <p class="sidebar-section">Mağaza Yönetimi</p>
                @endif

                @if($can('products'))
                    <button id="products-menu-toggle" type="button" class="sidebar-link w-full text-left">
                        <i class="fa-solid fa-box w-6"></i>
                        <span class="sidebar-label flex-1">Ürünler</span>
                        <i class="fa-solid fa-chevron-down text-xs sidebar-label"></i>
                    </button>
                    <div id="products-submenu" class="sidebar-submenu">
                        <a href="{{ route('admin.products.index') }}" class="sidebar-link">
                            <i class="fa-regular fa-rectangle-list w-6"></i>
                            <span class="sidebar-label">Ürün Listesi</span>
                        </a>
                        <a href="{{ route('admin.categories.index') }}" class="sidebar-link">
                            <i class="fa-solid fa-tags w-6"></i>
                            <span class="sidebar-label">Kategoriler</span>
                        </a>
                        <a href="{{ route('admin.brands.index') }}" class="sidebar-link">
                            <i class="fa-solid fa-certificate w-6"></i>
                            <span class="sidebar-label">Markalar</span>
                        </a>
                        <a href="#" class="sidebar-link" title="Yakında">
                            <i class="fa-solid fa-sliders w-6"></i>
                            <span class="sidebar-label">Seçenekler</span>
                        </a>
                    </div>
                @endif

                @if($can('orders'))
                    <a href="{{ route('admin.orders.index') }}" class="sidebar-link">
                        <i class="fa-solid fa-cart-shopping w-6"></i>
                        <span class="sidebar-label">Siparişler</span>
                    </a>
                @endif

                @if($can('customers'))
                    <a href="{{ route('admin.customers.index') }}" class="sidebar-link">
                        <i class="fa-solid fa-user-group w-6"></i>
                        <span class="sidebar-label">Müşteriler</span>
                    </a>
                @endif

                @if($showSettingsSection)
                    <p class="sidebar-section">Ayarlar</p>
                @endif

                @if($can('settings'))
                    <a href="{{ route('admin.settings.index') }}" class="sidebar-link">
                        <i class="fa-solid fa-gear w-6"></i>
                        <span class="sidebar-label">Genel Ayarlar</span>
                    </a>
                @endif
                @unless(auth('subuser')->check())
                    <a href="{{ route('admin.sub-users.index') }}" class="sidebar-link">
                        <i class="fa-solid fa-user-shield w-6"></i>
                        <span class="sidebar-label">Alt Kullanıcılar</span>
                    </a>
                @endunless

                @if($canReportsAny)
                    <button id="reports-menu-toggle" type="button" class="sidebar-link w-full text-left">
                        <i class="fa-regular fa-clock w-6"></i>
                        <span class="sidebar-label flex-1">Raporlar</span>
                        <i class="fa-solid fa-chevron-down text-xs sidebar-label"></i>
                    </button>
                    <div id="reports-submenu" class="sidebar-submenu">
                        @if($canReports('reports.top_products'))
                            <a href="{{ route('admin.reports.top-products') }}" class="sidebar-link">
                                <span class="sidebar-label">Çok Satan Ürünler</span>
                            </a>
                        @endif
                        @if($canReports('reports.sold_products'))
                            <a href="{{ route('admin.reports.sold-products') }}" class="sidebar-link">
                                <span class="sidebar-label">Satılan Ürün Listesi</span>
                            </a>
                        @endif
                        @if($canReports('reports.orders'))
                            <a href="{{ route('admin.reports.index') }}" class="sidebar-link">
                                <span class="sidebar-label">Sipariş ve Ciro</span>
                            </a>
                        @endif
                        @if($canReports('reports.category_sales'))
                            <a href="{{ route('admin.reports.category-sales') }}" class="sidebar-link">
                                <span class="sidebar-label">Kategori Bazlı Satış</span>
                            </a>
                        @endif
                        @if($canReports('reports.brand_sales'))
                            <a href="{{ route('admin.reports.brand-sales') }}" class="sidebar-link">
                                <span class="sidebar-label">Marka Bazlı Satış</span>
                            </a>
                        @endif
                        @if($canReports('reports.vat'))
                            <a href="{{ route('admin.reports.vat') }}" class="sidebar-link">
                                <span class="sidebar-label">KDV Raporu</span>
                            </a>
                        @endif
                        @if($canReports('reports.commission'))
                            <a href="{{ route('admin.reports.commission') }}" class="sidebar-link">
                                <span class="sidebar-label">Komisyon Raporu</span>
                            </a>
                        @endif
                        @if($canReports('reports.stock_value'))
                            <a href="{{ route('admin.reports.stock-value') }}" class="sidebar-link">
                                <span class="sidebar-label">Stoktaki Ürün Tutarları Raporu</span>
                            </a>
                        @endif
                    </div>
                @endif

                @if($showSupportSection)
                    <p class="sidebar-section">Destek</p>
                @endif

                @if($can('tickets'))
                    <a href="{{ route('admin.tickets.index') }}" class="sidebar-link">
                        <i class="fa-solid fa-life-ring w-6"></i>
                        <span class="sidebar-label">Destek</span>
                    </a>
                @endif

                @if($showIntegrationSection)
                    <p class="sidebar-section">Entegrasyon</p>
                @endif

                @if($can('integrations'))
                    <a href="{{ route('admin.integrations.index') }}" class="sidebar-link">
                        <i class="fa-solid fa-plug w-6"></i>
                        <span class="sidebar-label">Mağaza Bağla</span>
                    </a>
                @endif
                @if($can('addons'))
                    <a href="{{ route('admin.addons.index') }}" class="sidebar-link">
                        <i class="fa-solid fa-layer-group w-6"></i>
                        <span class="sidebar-label">Ek Modüller</span>
                    </a>
                @endif

                @if($showSubscriptionSection)
                    <p class="sidebar-section">Abonelik</p>
                @endif

                @if($can('subscription'))
                    <a href="{{ route('admin.subscription') }}" class="sidebar-link">
                        <i class="fa-solid fa-crown w-6"></i>
                        <span class="sidebar-label">Paketim</span>
                    </a>
                @endif

                @if($can('invoices'))
                    <a href="{{ route('admin.invoices.index') }}" class="sidebar-link">
                        <i class="fa-solid fa-file-invoice w-6"></i>
                        <span class="sidebar-label">Faturalar</span>
                    </a>
                @endif

            </nav>

            <div class="p-4 border-t border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-slate-200 text-slate-700 flex items-center justify-center text-lg font-bold">
                        {{ substr(Auth::user()->name ?? 'A', 0, 1) }}
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800 sidebar-label">{{ Auth::user()->name ?? 'Kullanıcı' }}</p>
                        <p class="text-xs text-slate-400 sidebar-label">
                            {{ Auth::user()?->isSuperAdmin() ? 'Super Admin' : 'Müşteri' }}
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col">
            <header class="min-h-[96px] flex items-start justify-between px-6 py-6 border-b border-slate-200/70 text-slate-800">
                <div class="flex-1 flex items-start justify-between gap-6 ml-6 pt-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm">
                        <a href="{{ route('admin.help.training') }}" class="topbar-link">
                            <i class="fa-solid fa-graduation-cap text-xs"></i>
                            Eğitim Merkezi
                        </a>
                        <a href="{{ route('admin.help.support') }}" class="topbar-link">
                            <i class="fa-solid fa-headset text-xs"></i>
                            Destek Merkezi
                        </a>
                        <a href="{{ route('admin.tickets.create') }}" class="topbar-link">
                            <i class="fa-solid fa-plus text-xs"></i>
                            Destek Talebi Oluştur
                        </a>
                    </div>
                    <div class="flex items-center gap-3 pt-1">
                        <a href="#" class="topbar-icon" title="Bildirimler">
                            <i class="fa-regular fa-bell text-sm"></i>
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="topbar-icon" title="Ayarlar">
                            <i class="fa-solid fa-gear text-sm"></i>
                        </a>
                        <div class="relative">
                            <button id="profile-menu-button" type="button" class="topbar-icon" title="Profil">
                                <i class="fa-regular fa-user text-sm"></i>
                            </button>
                            <div id="profile-menu-panel" class="absolute right-0 mt-3 w-56 origin-top-right rounded-lg border border-slate-200 bg-white p-2 shadow-lg hidden">
                                <a href="{{ route('admin.subscription') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                                    <i class="fa-regular fa-credit-card"></i>
                                    Abonelik Ayarları
                                </a>
                                @if(auth('subuser')->check())
                                    <a href="{{ route('admin.subuser.password.edit') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                                        <i class="fa-solid fa-key"></i>
                                        Profili Düzenle
                                    </a>
                                @else
                                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                                        <i class="fa-solid fa-key"></i>
                                        Profili Düzenle
                                    </a>
                                @endif
                                @unless(auth('subuser')->check())
                                    <a href="{{ route('admin.sub-users.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                                        <i class="fa-solid fa-users"></i>
                                        Kullanıcı Yönetimi
                                    </a>
                                @endunless
                                <a href="{{ route('admin.invoices.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                                    <i class="fa-regular fa-file-lines"></i>
                                    Fatura Bilgileri
                                </a>
                                @if(auth('subuser')->check())
                                    <form method="POST" action="{{ route('subuser.logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-red-500 hover:bg-red-50">
                                            <i class="fa-solid fa-right-from-bracket"></i>
                                            Oturumu Kapat
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-red-500 hover:bg-red-50">
                                            <i class="fa-solid fa-right-from-bracket"></i>
                                            Oturumu Kapat
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('admin.help.refer') }}" class="topbar-icon" title="Tavsiye Et">
                            <i class="fa-solid fa-gift text-sm"></i>
                        </a>
                    </div>
                </div>
            </header>

            @php
                $adminBanners = \App\Models\Banner::query()
                    ->active()
                    ->forPlacement('admin_header')
                    ->orderBy('sort_order')
                    ->get();
            @endphp
            @foreach($adminBanners as $banner)
                <div class="w-full">
                    @if($banner->image_path)
                        @php
                            $bannerImage = asset('storage/' . $banner->image_path);
                        @endphp
                        @if($banner->link_url)
                            <a href="{{ $banner->link_url }}" class="block" target="_blank" rel="noopener noreferrer">
                                <img src="{{ $bannerImage }}" alt="{{ $banner->title ?? 'Banner' }}" class="w-full max-h-40 object-cover">
                            </a>
                        @else
                            <img src="{{ $bannerImage }}" alt="{{ $banner->title ?? 'Banner' }}" class="w-full max-h-40 object-cover">
                        @endif
                        @if($banner->show_countdown && $banner->ends_at)
                            <div class="px-6 py-2 text-xs text-slate-600 bg-white border-b border-slate-200">
                                Kalan süre:
                                <span class="banner-countdown" data-ends-at="{{ $banner->ends_at->toIso8601String() }}"></span>
                            </div>
                        @endif
                    @else
                        @php
                            $bg = $banner->bg_color ?: '#0f172a';
                            $fg = $banner->text_color ?: '#ffffff';
                        @endphp
                        <div class="px-6 py-3 text-sm" style="background: {{ $bg }}; color: {{ $fg }};">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                                <div class="flex flex-col">
                                    @if($banner->title)
                                        <span class="font-semibold">{{ $banner->title }}</span>
                                    @endif
                                    @if($banner->message)
                                        <span class="text-xs md:text-sm">{{ $banner->message }}</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4">
                                    @if($banner->show_countdown && $banner->ends_at)
                                        <span class="text-xs font-semibold banner-countdown" data-ends-at="{{ $banner->ends_at->toIso8601String() }}"></span>
                                    @endif
                                    @if($banner->link_url)
                                        <a href="{{ $banner->link_url }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold underline">
                                            {{ $banner->link_text ?: 'Detay' }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
            <div class="px-6 pb-6 pt-6">
                <div class="mb-6">
                    <h2 class="text-2xl font-semibold text-slate-900">
                        @yield('header')
                    </h2>
                </div>
                @if(session('success'))
                    <div class="panel-card px-4 py-3 mb-4 border-green-200 text-green-700">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('info'))
                    <div class="panel-card px-4 py-3 mb-4 border-blue-200 text-blue-700">
                        {{ session('info') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @php
        $quickActionOptions = [
            'invoices.create' => [
                'label' => 'Fatura Ekle',
                'route' => 'admin.invoices.create',
                'icon' => 'fa-file-invoice',
            ],
            'categories.create' => [
                'label' => 'Kategori Ekle',
                'route' => 'admin.categories.create',
                'icon' => 'fa-tags',
            ],
            'brands.create' => [
                'label' => 'Marka Ekle',
                'route' => 'admin.brands.create',
                'icon' => 'fa-certificate',
            ],
            'products.create' => [
                'label' => 'Ürün Ekle',
                'route' => 'admin.products.create',
                'icon' => 'fa-box',
            ],
        ];
        $quickActionsRaw = \App\Models\AppSetting::getValue('admin_quick_actions_v2', '[]');
        $quickActionsConfig = json_decode($quickActionsRaw, true);
        if (!is_array($quickActionsConfig)) {
            $quickActionsConfig = [];
        }
        $userRole = auth('subuser')->check() ? 'subuser' : 'client';
        $quickActions = collect($quickActionsConfig)
            ->filter(fn ($item) => ($item['enabled'] ?? false) && in_array($userRole, $item['roles'] ?? [], true))
            ->filter(fn ($item) => array_key_exists($item['key'] ?? '', $quickActionOptions))
            ->sortBy(fn ($item) => $item['order'] ?? 0)
            ->map(function ($item) use ($quickActionOptions) {
                $base = $quickActionOptions[$item['key']];
                return [
                    'label' => $base['label'],
                    'route' => $base['route'],
                    'icon' => $item['icon'] ?? $base['icon'],
                    'color' => $item['color'] ?? '#ff4439',
                ];
            })
            ->values();
    @endphp
    @php
        $hideQuickActions = request()->routeIs('admin.invoices.create');
    @endphp
    @if($quickActions->isNotEmpty() && !$hideQuickActions)
        <div class="quick-actions">
            <button type="button" id="quick-actions-toggle" class="quick-actions-toggle">
                <i class="fa-solid fa-plus"></i>
            </button>
            <div id="quick-actions-menu" class="quick-actions-menu hidden" hidden>
                @foreach($quickActions as $action)
                    <a href="{{ route($action['route']) }}" class="quick-actions-item" data-quick-link>
                        <span class="quick-actions-icon" style="color: {{ $action['color'] }};">
                            <i class="fa-solid {{ $action['icon'] }}"></i>
                        </span>
                        <span class="text-sm font-semibold text-slate-700">{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @stack('scripts')
    <script>
        const profileMenuButton = document.getElementById('profile-menu-button');
        const profileMenuPanel = document.getElementById('profile-menu-panel');

        function closeProfileMenu() {
            profileMenuPanel?.classList.add('hidden');
        }

        function toggleProfileMenu() {
            profileMenuPanel?.classList.toggle('hidden');
        }

        profileMenuButton?.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleProfileMenu();
        });

        document.addEventListener('click', () => {
            closeProfileMenu();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeProfileMenu();
            }
        });
    </script>
    <script>
        const productsMenuToggle = document.getElementById('products-menu-toggle');
        const productsSubmenu = document.getElementById('products-submenu');

        function setProductsMenu(open) {
            productsSubmenu?.classList.toggle('is-open', open);
        }

        if (productsSubmenu) {
            setProductsMenu(false);
        }

        productsMenuToggle?.addEventListener('click', () => {
            const isOpen = productsSubmenu?.classList.contains('is-open');
            setProductsMenu(!isOpen);
        });
    </script>
    <script>
        const reportsMenuToggle = document.getElementById('reports-menu-toggle');
        const reportsSubmenu = document.getElementById('reports-submenu');

        function setReportsMenu(open) {
            reportsSubmenu?.classList.toggle('is-open', open);
        }

        if (reportsSubmenu) {
            setReportsMenu(false);
        }

        reportsMenuToggle?.addEventListener('click', () => {
            const isOpen = reportsSubmenu?.classList.contains('is-open');
            setReportsMenu(!isOpen);
        });
    </script>
    <script>
        const sidebar = document.querySelector('.sidebar');
        const sidebarPin = document.getElementById('sidebar-pin-toggle');
        const adminPinKey = 'adminSidebarPinned';

        function setPinnedState(isPinned) {
            sidebar?.classList.toggle('is-pinned', isPinned);
            sidebarPin?.classList.toggle('is-active', isPinned);
            document.documentElement.classList.toggle('admin-sidebar-pinned', isPinned);
            if (isPinned) {
                localStorage.setItem(adminPinKey, '1');
            } else {
                localStorage.removeItem(adminPinKey);
            }
        }

        const isPinnedStored = localStorage.getItem(adminPinKey) === '1';
        setPinnedState(isPinnedStored);

        sidebarPin?.addEventListener('click', (event) => {
            event.preventDefault();
            const nextState = !sidebar?.classList.contains('is-pinned');
            setPinnedState(nextState);
        });
    </script>
    <script>
        function formatCountdown(diffMs) {
            if (diffMs <= 0) return 'Sona erdi';
            const totalSeconds = Math.floor(diffMs / 1000);
            const days = Math.floor(totalSeconds / 86400);
            const hours = Math.floor((totalSeconds % 86400) / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            const parts = [];
            if (days > 0) parts.push(`${days}g`);
            parts.push(`${String(hours).padStart(2, '0')}s`);
            parts.push(`${String(minutes).padStart(2, '0')}d`);
            parts.push(`${String(seconds).padStart(2, '0')}sn`);
            return parts.join(' ');
        }

        function startBannerCountdowns() {
            const nodes = document.querySelectorAll('.banner-countdown');
            if (!nodes.length) return;

            function tick() {
                const now = Date.now();
                nodes.forEach((node) => {
                    const endsAt = node.dataset.endsAt;
                    if (!endsAt) return;
                    const target = Date.parse(endsAt);
                    if (Number.isNaN(target)) return;
                    node.textContent = formatCountdown(target - now);
                });
            }

            tick();
            setInterval(tick, 1000);
        }

        startBannerCountdowns();
    </script>
    <script>
        const quickActionsToggle = document.getElementById('quick-actions-toggle');
        const quickActionsMenu = document.getElementById('quick-actions-menu');

        function closeQuickActions() {
            quickActionsMenu?.classList.add('hidden');
            quickActionsMenu?.setAttribute('hidden', '');
        }

        function toggleQuickActions() {
            const willOpen = quickActionsMenu?.classList.contains('hidden');
            if (!quickActionsMenu) return;
            quickActionsMenu.classList.toggle('hidden', !willOpen);
            if (willOpen) {
                quickActionsMenu.removeAttribute('hidden');
            } else {
                quickActionsMenu.setAttribute('hidden', '');
            }
        }

        quickActionsToggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleQuickActions();
        });

        document.addEventListener('click', (event) => {
            const isToggle = event.target.closest('#quick-actions-toggle');
            const isMenu = event.target.closest('#quick-actions-menu');
            if (!isToggle && !isMenu) {
                closeQuickActions();
            }
        }, true);

        quickActionsMenu?.addEventListener('click', (event) => {
            const target = event.target.closest('[data-quick-link]');
            if (target) {
                closeQuickActions();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            closeQuickActions();
        });

        window.addEventListener('pageshow', () => {
            closeQuickActions();
        });
    </script>
</body>
</html>

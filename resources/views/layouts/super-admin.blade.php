<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Süper Admin Paneli</title>

    @php

        $panelThemeFont = \App\Models\AppSetting::getValue('panel_theme_font', 'poppins');

        $panelThemeAccent = \App\Models\AppSetting::getValue('panel_theme_accent', '#ff4439');

        $panelThemeRadius = (int) \App\Models\AppSetting::getValue('panel_theme_radius', 5);



        $panelFonts = [

            'poppins' => ['family' => 'Poppins', 'bunny' => 'poppins:300,400,500,600,700'],

            'manrope' => ['family' => 'Manrope', 'bunny' => 'manrope:400,500,600,700'],

            'space_grotesk' => ['family' => 'Space Grotesk', 'bunny' => 'space-grotesk:400,500,600,700'],

            'system' => ['family' => 'system-ui', 'bunny' => null],

        ];



        if (!array_key_exists($panelThemeFont, $panelFonts)) {

            $panelThemeFont = 'poppins';

        }



        if (!is_string($panelThemeAccent) || !preg_match('/^#[0-9a-fA-F]{6}$/', $panelThemeAccent)) {

            $panelThemeAccent = '#ff4439';

        }



        if ($panelThemeRadius < 0) {

            $panelThemeRadius = 0;

        }

        if ($panelThemeRadius > 16) {

            $panelThemeRadius = 16;

        }



        $panelFontFamily = $panelFonts[$panelThemeFont]['family'];

        $panelFontBunny = $panelFonts[$panelThemeFont]['bunny'];

        $panelCssFontStack = $panelThemeFont === 'system'

            ? 'system-ui, -apple-system, Segoe UI, Roboto, sans-serif'

            : '"' . $panelFontFamily . '", system-ui, -apple-system, Segoe UI, Roboto, sans-serif';

    @endphp

    <link rel="preconnect" href="https://fonts.bunny.net">

    @if($panelFontBunny)

        <link href="https://fonts.bunny.net/css?family={{ $panelFontBunny }}&display=swap" rel="stylesheet" />

    @endif

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>

        :root {

            --panel-ink: #0f172a;

            --panel-muted: #64748b;

            --panel-border: #e2e8f0;

            --panel-accent: {{ $panelThemeAccent }};

            --panel-radius: {{ $panelThemeRadius }}px;

            --panel-font-family: {!! $panelCssFontStack !!};

        }

        body {

            font-family: var(--panel-font-family) !important;

        }

        .rounded-full,

        .rounded-2xl,

        .rounded-xl,

        .rounded-lg,

        .rounded-md {

            border-radius: var(--panel-radius) !important;

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

        .sidebar-section {

            opacity: 0;

            width: 0;

            overflow: hidden;

            white-space: nowrap;

            transition: opacity 200ms ease, width 200ms ease;

        }

        .sidebar:hover .sidebar-label,

        .sidebar:hover .sidebar-section,

        .sidebar.is-pinned .sidebar-label,

        .sidebar.is-pinned .sidebar-section {

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

        .sidebar-section {

            padding: 0 0.85rem;

            font-size: 10px;

            letter-spacing: 0.16em;

            text-transform: uppercase;

            color: #9ca3af;

            margin-top: 1rem;

        }

        main input,

        main select,

        main textarea {

            background: #ffffff !important;

            border: 1px solid var(--panel-border) !important;

            border-radius: var(--panel-radius) !important;

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

            border-radius: var(--panel-radius) !important;

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

            filter: brightness(0.92);

            color: #ffffff;

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

            border-radius: var(--panel-radius) !important;

            background-color: var(--panel-accent) !important;

        }

        main a.bg-blue-600:hover,

        main a.bg-slate-900:hover,

        main a.bg-amber-600:hover,

        main a.bg-emerald-600:hover,

        main a.bg-sky-600:hover,

        main a.bg-indigo-600:hover,

        main a.bg-teal-600:hover {

            filter: brightness(0.92);

        }

        main .shadow,

        main .shadow-sm {

            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06) !important;

        }

        main {

            padding-left: 100px;

            transition: padding-left 220ms ease;

        }

        .sidebar:hover ~ main,

        .sidebar.is-pinned ~ main {

            padding-left: 284px;

        }

        .panel-card {

            background: #ffffff;

            border: 1px solid var(--panel-border);

            border-radius: var(--panel-radius);

            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);

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

        main a.text-blue-600,

        main a.text-blue-700,

        main a.text-blue-900 {

            color: var(--panel-accent) !important;

        }

        main a.text-blue-600:hover,

        main a.text-blue-700:hover,

        main a.text-blue-900:hover {

            opacity: 0.9;

        }

        .topbar-icon {

            width: 34px;

            height: 34px;

            border-radius: var(--panel-radius);

            border: 1px solid var(--panel-border);

            display: inline-flex;

            align-items: center;

            justify-content: center;

            color: #475569;

            background: #ffffff;

        }

        .topbar-icon:hover {

            color: #0f172a;

            border-color: var(--panel-accent);

        }

    </style>

</head>

<body class="bg-white font-sans antialiased">

    <div class="min-h-screen flex">

        <aside class="sidebar flex flex-col">

            <div class="sidebar-brand">

                <div class="flex items-center gap-2">

                    <i class="fa-solid fa-user-shield text-lg"></i>

                    <h1 class="text-sm font-semibold tracking-[0.32em] sidebar-label">SUPER ADMIN</h1>

                </div>

                <button id="super-sidebar-pin-toggle" type="button" class="sidebar-pin" title="Sabitle">

                    <i class="fa-solid fa-thumbtack text-xs"></i>

                </button>

            </div>



            <nav class="flex-1 px-3 py-4 space-y-2">

                <a href="{{ route('super-admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('super-admin.dashboard') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-gauge-high w-6"></i>

                    <span class="sidebar-label">Genel Ã–zet</span>

                </a>



                <p class="sidebar-section">Kullanıcılar</p>



                <a href="{{ route('super-admin.users.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.users.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-users w-6"></i>

                    <span class="sidebar-label">Kullanıcılar</span>

                </a>

                <a href="{{ route('super-admin.sub-users.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.sub-users.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-user-shield w-6"></i>

                    <span class="sidebar-label">Alt Kullanıcılar</span>

                </a>

                <a href="{{ route('super-admin.customers.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.customers.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-user-group w-6"></i>

                    <span class="sidebar-label">Müşteriler</span>

                </a>



                <p class="sidebar-section">Destek</p>



                <a href="{{ route('super-admin.tickets.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.tickets.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-life-ring w-6"></i>

                    <span class="sidebar-label">Ticketlar</span>

                </a>

                <a href="{{ route('super-admin.support-view-sessions.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.support-view-sessions.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-eye w-6"></i>

                    <span class="sidebar-label">Support View Oturumları</span>

                </a>



                <p class="sidebar-section">Abonelik & Faturalama</p>



                <a href="{{ route('super-admin.subscriptions.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.subscriptions.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-file-invoice w-6"></i>

                    <span class="sidebar-label">Abonelikler</span>

                </a>

                <a href="{{ route('super-admin.invoices.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.invoices.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-receipt w-6"></i>

                    <span class="sidebar-label">Faturalar</span>

                </a>

                <a href="{{ route('super-admin.plans.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.plans.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-layer-group w-6"></i>

                    <span class="sidebar-label">Abonelik Paketleri</span>

                </a>

                <a href="{{ route('super-admin.modules.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.modules.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-puzzle-piece w-6"></i>

                    <span class="sidebar-label">Modüller</span>

                </a>

                <a href="{{ route('super-admin.module-purchases.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.module-purchases.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-cash-register w-6"></i>

                    <span class="sidebar-label">Modül Satışları</span>

                </a>



                <p class="sidebar-section">Entegrasyonlar</p>



                <a href="{{ route('super-admin.marketplaces.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.marketplaces.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-store w-6"></i>

                    <span class="sidebar-label">Pazaryerleri</span>

                </a>

                <a href="{{ route('super-admin.cargo.providers.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.cargo.providers.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-truck-fast w-6"></i>

                    <span class="sidebar-label">Kargo Sağlayıcıları</span>

                </a>

                <a href="{{ route('super-admin.cargo.mappings.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.cargo.mappings.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-link w-6"></i>

                    <span class="sidebar-label">Kargo Mapping</span>

                </a>

                <a href="{{ route('super-admin.cargo.health.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.cargo.health.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-heart-pulse w-6"></i>

                    <span class="sidebar-label">Cargo Health</span>

                </a>



                <p class="sidebar-section">Pazarlama</p>



                <a href="{{ route('super-admin.referrals.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.referrals.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-user-plus w-6"></i>

                    <span class="sidebar-label">Tavsiyeler</span>

                </a>

                <a href="{{ route('super-admin.banners.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.banners.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-bullhorn w-6"></i>

                    <span class="sidebar-label">Bannerlar</span>

                </a>



                <p class="sidebar-section">Raporlar</p>



                <a href="{{ route('super-admin.reports.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.reports.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-chart-column w-6"></i>

                    <span class="sidebar-label">Gelir Raporları</span>

                </a>



                <p class="sidebar-section">Sistem</p>



                <a href="{{ route('super-admin.system.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.system.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-sliders w-6"></i>

                    <span class="sidebar-label">Sistem Merkezi</span>

                </a>

                <a href="{{ route('super-admin.settings.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.settings.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-gear w-6"></i>

                    <span class="sidebar-label">Sistem Ayarları</span>

                </a>

                <a href="{{ route('super-admin.mail-logs.index') }}" class="sidebar-link {{ request()->routeIs('super-admin.mail-logs.*') ? 'is-active' : '' }}">

                    <i class="fa-solid fa-envelope-open-text w-6"></i>

                    <span class="sidebar-label">E-Posta Kayıtları</span>

                </a>

            </nav>



            <div class="p-4 border-t border-slate-200">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-700 flex items-center justify-center text-lg font-bold">

                        {{ substr(Auth::user()->name ?? 'S', 0, 1) }}

                    </div>

                    <div>

                        <p class="text-sm font-semibold text-slate-800 sidebar-label">{{ Auth::user()->name ?? 'Super Admin' }}</p>

                        <p class="text-xs text-slate-400 sidebar-label">Sistem Yöneticisi</p>

                    </div>

                </div>

            </div>

        </aside>



        <main class="flex-1 flex flex-col">

            <header class="min-h-[72px] bg-white shadow-sm flex items-center justify-between px-6 py-4">

                <h2 class="text-lg font-semibold text-slate-800">

                    @yield('header')

                </h2>

                <div class="flex items-center gap-3">

                    @include('partials.notification-bell')

                    <div class="relative">

                        <button id="super-profile-menu-button" type="button" class="topbar-icon" title="Profil">

                            <i class="fa-regular fa-user text-sm"></i>

                        </button>

                        <div id="super-profile-menu-panel" class="absolute right-0 mt-3 w-56 origin-top-right rounded-lg border border-slate-200 bg-white p-2 shadow-lg hidden">

                            <a href="{{ route('super-admin.subscriptions.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">

                                <i class="fa-regular fa-credit-card"></i>

                                Abonelik Ayarları

                            </a>

                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">

                                <i class="fa-solid fa-key"></i>

                                Şifre DeÄŸiÅŸtirme

                            </a>

                            <a href="{{ route('super-admin.users.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">

                                <i class="fa-solid fa-users"></i>

                                Kullanıcı Yönetimi

                            </a>

                            <a href="{{ route('super-admin.invoices.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">

                                <i class="fa-regular fa-file-lines"></i>

                                Fatura Bilgileri

                            </a>

                            <form method="POST" action="{{ route('logout') }}">

                                @csrf

                                <button type="submit" class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-red-500 hover:bg-red-50">

                                    <i class="fa-solid fa-right-from-bracket"></i>

                                    Oturumu Kapat

                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            </header>



            <div class="p-6 overflow-y-auto">

                @if(session('success'))

                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">

                        {{ session('success') }}

                    </div>

                @endif



                @if(session('info'))

                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4">

                        {{ session('info') }}

                    </div>

                @endif



                @yield('content')

            </div>

        </main>

    </div>

    @stack('scripts')

    <script>

        const superProfileMenuButton = document.getElementById('super-profile-menu-button');

        const superProfileMenuPanel = document.getElementById('super-profile-menu-panel');



        function closeSuperProfileMenu() {

            superProfileMenuPanel?.classList.add('hidden');

        }



        function toggleSuperProfileMenu() {

            superProfileMenuPanel?.classList.toggle('hidden');

        }



        superProfileMenuButton?.addEventListener('click', (event) => {

            event.stopPropagation();

            toggleSuperProfileMenu();

        });



        document.addEventListener('click', () => {

            closeSuperProfileMenu();

        });



        document.addEventListener('keydown', (event) => {

            if (event.key === 'Escape') {

                closeSuperProfileMenu();

            }

        });

    </script>

    <script>

        const superSidebar = document.querySelector('.sidebar');

        const superSidebarPin = document.getElementById('super-sidebar-pin-toggle');

        const superPinKey = 'superAdminSidebarPinned';



        function setSuperPinnedState(isPinned) {

            superSidebar?.classList.toggle('is-pinned', isPinned);

            superSidebarPin?.classList.toggle('is-active', isPinned);

            if (isPinned) {

                localStorage.setItem(superPinKey, '1');

            } else {

                localStorage.removeItem(superPinKey);

            }

        }



        const superIsPinnedStored = localStorage.getItem(superPinKey) === '1';

        setSuperPinnedState(superIsPinnedStored);



        superSidebarPin?.addEventListener('click', (event) => {

            event.preventDefault();

            const nextState = !superSidebar?.classList.contains('is-pinned');

            setSuperPinnedState(nextState);

        });

    </script>

</body>

</html>





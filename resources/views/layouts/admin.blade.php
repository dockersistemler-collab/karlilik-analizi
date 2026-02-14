<!DOCTYPE html>

<html lang="tr">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pazaryeri Paneli</title>

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

            --panel-ink: #2a1f1b;

            --panel-muted: #7b6a62;

            --panel-bg: #fff7f1;

            --panel-card: #fffdfb;

            --panel-border: #f1e3da;

            --panel-primary: #2a1f1b;

            --panel-primary-dark: #1e1714;

            --panel-accent: {{ $panelThemeAccent }};

            --panel-accent-soft: #ffe3d7;

            --panel-mint: #78c9b2;

            --panel-sky: #8cc9ff;

            --panel-butter: #f7d27b;

            --panel-radius: {{ $panelThemeRadius }}px;

            --panel-font-family: {!! $panelCssFontStack !!};

        }

        body {

            font-family: var(--panel-font-family);

            background: radial-gradient(circle at 8% 12%, rgba(255, 226, 214, 0.7) 0%, transparent 48%),

                        radial-gradient(circle at 90% 8%, rgba(209, 238, 255, 0.6) 0%, transparent 45%),

                        radial-gradient(circle at 70% 80%, rgba(215, 247, 236, 0.7) 0%, transparent 42%),

                        var(--panel-bg);

            color: var(--panel-ink);

        }

        body::before {

            content: "";

            position: fixed;

            inset: 0;

            background:

                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.6) 0%, transparent 55%),

                radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.5) 0%, transparent 60%);

            pointer-events: none;

            z-index: 0;

        }

        body > * {

            position: relative;

            z-index: 2;

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

            padding: 0.6rem 0.85rem;

            border-radius: 14px;

            color: #374151;

            transition: color 160ms ease, background 160ms ease;
            font-size: 0.9rem;
            margin-bottom: 0.18rem;

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
        .sidebar-submenu.is-open {
            position: relative;
            padding-left: 2.35rem;
        }
        .sidebar-submenu.is-open::before {
            content: "";
            position: absolute;
            left: 1.15rem;
            top: 0.4rem;
            bottom: 0.4rem;
            width: 1px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.7);
        }

        html:not(.admin-sidebar-pinned) .sidebar:not(:hover):not(.is-pinned) .sidebar-submenu.is-open {

            display: none;

        }

        .sidebar-submenu .sidebar-link {

            padding: 0.45rem 0.75rem;

            border-radius: 999px;

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

            border-radius: var(--panel-radius);

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

            border-radius: var(--panel-radius);

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

            color: var(--panel-accent) !important;

        }

        main a.text-blue-600:hover,

        main a.text-blue-700:hover,

        main a.text-blue-900:hover {

            opacity: 0.9;

        }

        /* Marketplace skin v3: admin + customer portal only (super-admin uses its own layout) */
        :root {
            --mk-bg: #f2f2f2;
            --mk-bg-soft: #e9e9e9;
            --mk-surface: #ffffff;
            --mk-surface-2: #f7f7f7;
            --mk-border: #c0c0c0;
            --mk-ink: #373737;
            --mk-muted: #818181;
            --mk-muted-2: #a1a1a1;
            --mk-muted-3: #c0c0c0;
            --mk-accent: #555555;
            --mk-accent-soft: #e5e5e5;
            --mk-highlight: #555555;
            --mk-warn: #818181;
            --mk-danger: #555555;
        }

        body {
            background:
                radial-gradient(circle at 12% 10%, rgba(255, 255, 255, 0.35) 0%, transparent 40%),
                radial-gradient(circle at 88% 15%, rgba(255, 255, 255, 0.22) 0%, transparent 36%),
                linear-gradient(180deg, #f4f4f4 0%, #fafafa 100%);
            color: var(--mk-ink);
        }

        .sidebar {
            background: linear-gradient(180deg, #ffffff 0%, var(--mk-surface-2) 100%);
            border: 1px solid var(--mk-border);
            box-shadow: 0 18px 40px rgba(55, 55, 55, 0.12);
        }

        .sidebar-brand {
            border-color: var(--mk-border);
            border-top: none;
        }

        .sidebar-link {
            color: var(--mk-ink);
            border: 1px solid transparent;
            padding: 0.6rem 0.85rem;
            border-radius: 999px;
            font-size: 0.9rem;
            margin-bottom: 0.18rem;
        }

        .sidebar-link i {
            color: var(--mk-muted);
        }

        .sidebar-link:hover {
            color: var(--mk-ink);
            background: var(--mk-accent-soft);
            border-color: var(--mk-muted-3);
        }

        .sidebar-link:hover i {
            color: var(--mk-accent);
        }

        .sidebar-link.is-active {
            background: rgba(255, 107, 74, 0.32);
            color: #373737;
            border-color: rgba(255, 107, 74, 0.5);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4);
            border-radius: 999px;
        }

        .sidebar-link.is-active i {
            color: #373737;
        }

        .sidebar-section {
            color: var(--mk-muted-2);
        }

        .sidebar-pin,
        .sidebar-action,
        .topbar-icon {
            border-color: var(--mk-border);
            background: var(--mk-surface);
            color: var(--mk-ink) !important;
            box-shadow: 0 10px 20px rgba(55, 55, 55, 0.1);
        }

        .sidebar-pin:hover,
        .sidebar-pin.is-active,
        .sidebar-action:hover,
        .topbar-icon:hover {
            color: var(--mk-ink) !important;
            border-color: var(--mk-muted-3);
            background: #ededed;
        }

        .panel-card {
            background: var(--mk-surface);
            border-color: transparent;
            box-shadow: 0 10px 22px rgba(55, 55, 55, 0.12);
        }

        .topbar-link {
            color: var(--mk-ink);
        }

        .topbar-link:hover {
            color: var(--mk-ink);
        }

        main input,
        main select,
        main textarea {
            border-color: var(--mk-border) !important;
            background: #ffffff !important;
            color: var(--mk-ink) !important;
        }

        main input:focus,
        main select:focus,
        main textarea:focus {
            border-color: var(--mk-muted-2) !important;
            box-shadow: 0 0 0 3px rgba(129, 129, 129, 0.18) !important;
        }

        .btn {
            border-radius: 999px !important;
        }

        .btn-solid-accent {
            border-color: var(--mk-accent);
            background: linear-gradient(135deg, #555555 0%, #373737 100%);
            color: #ffffff;
        }

        .btn-solid-accent:hover {
            filter: none;
            background: linear-gradient(135deg, #4a4a4a 0%, #2f2f2f 100%);
            color: #ffffff;
        }

        .btn-outline,
        .btn-outline-accent {
            border-color: var(--mk-muted-2) !important;
            color: var(--mk-ink) !important;
            background: #f7f7f7 !important;
            border-style: solid !important;
        }

        .btn-outline:hover,
        .btn-outline-accent:hover {
            border-color: var(--mk-ink) !important;
            color: #ffffff !important;
            background: var(--mk-ink) !important;
        }

        main table {
            border-spacing: 0 12px !important;
        }

        main table thead th {
            color: var(--mk-muted) !important;
            border-bottom-color: var(--mk-border) !important;
        }

        main table tbody tr {
            background: #ffffff !important;
            box-shadow: 0 12px 24px rgba(55, 55, 55, 0.14);
            border: 1px solid #e6e6e6;
        }

        main table tbody tr:hover {
            background: #f7f7f7 !important;
        }

        main table tbody td {
            border-top: 1px solid #ededed;
            border-bottom: 1px solid #ededed;
        }

        main table tbody td:first-child {
            border-left: 1px solid #ededed;
        }

        main table tbody td:last-child {
            border-right: 1px solid #ededed;
        }

        .badge,
        .panel-pill {
            border: 1px solid var(--mk-border);
            background: #efefef;
            color: var(--mk-ink);
        }

        .quick-actions-toggle {
            background: linear-gradient(135deg, #555555 0%, #373737 100%);
        }

        .quick-actions-menu {
            background: var(--mk-surface);
            border-color: var(--mk-border);
        }

        .quick-actions-item:hover {
            background: #ffe6da;
        }

        /* Force grayscale text palette across admin + customer panels */
        main h1,
        main h2,
        main h3,
        main h4,
        main h5,
        main h6 {
            color: var(--mk-ink) !important;
        }

        main .text-slate-900,
        main .text-slate-800,
        main .text-slate-700 {
            color: #373737 !important;
        }

        main .text-slate-600 {
            color: #555555 !important;
        }

        main .text-slate-500 {
            color: #818181 !important;
        }

        main .text-slate-400 {
            color: #a1a1a1 !important;
        }

        main .text-slate-300 {
            color: #c0c0c0 !important;
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

                $ownerUser = $subUser ? $subUser->owner : auth()->user();

                $activePlan = $ownerUser?->getActivePlan();
$hasModule = function (string $moduleKey) use ($ownerUser) {

                    return $ownerUser

                        ? app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, $moduleKey)

                        : false;

                };

                $can = function (string $key) use ($subPermissions, $subUser) {

                    return !$subUser || $subPermissions->has($key);

                };

                $canReportsAnyPermission = !$subUser || $subPermissions->has('reports') || $subPermissions->keys()->contains(function ($key) {

                    return str_starts_with($key, 'reports.');

                });

                $canReportsAny = $hasModule('feature.reports') && $canReportsAnyPermission;
                $canProfitability = $hasModule('feature.reports.profitability')
                    && (!$subUser || $subPermissions->has('reports') || $subPermissions->has('reports.profitability'));

                $canReports = function (string $key) use ($subPermissions, $subUser) {

                    return !$subUser || $subPermissions->has($key) || $subPermissions->has('reports');

                };

                $showStoreSection = $can('products') || $can('orders') || $can('customers');

                $showSettingsSection = $can('settings') || $canReportsAny;

                $showSupportSection = $hasModule('feature.tickets') && $can('tickets');

                $showIntegrationSection = $can('addons') || ($hasModule('feature.integrations') && $can('integrations'));

                $showSubscriptionSection = $can('subscription') || $can('invoices');

                $hasCargoTracking = $ownerUser && app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, 'feature.cargo_tracking');
                $inventoryModuleEnabled = $ownerUser && app(\App\Services\Modules\ModuleGate::class)->isEnabledForUser($ownerUser, 'feature.inventory');

            @endphp



            <nav class="flex-1 px-3 py-4 space-y-2">



                @if($can('dashboard'))
                    <a href="{{ route('portal.dashboard') }}" class="sidebar-link {{ request()->routeIs('portal.dashboard') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-chart-line w-6"></i>
                        <span class="sidebar-label">Y&#246;netim Paneli</span>
                    </a>
                @endif

                @if($showStoreSection)

                    <p class="sidebar-section">Mağaza Yönetimi</p>

                @endif




                



                @if($can('products'))

                    <button id="products-menu-toggle" type="button" class="sidebar-link w-full text-left {{ request()->routeIs('portal.products.*', 'portal.categories.*', 'portal.brands.*') ? 'is-active' : '' }}">

                        <i class="fa-solid fa-box w-6"></i>

                        <span class="sidebar-label flex-1">Ürünler</span>

                        <i class="fa-solid fa-chevron-down text-xs sidebar-label"></i>

                    </button>

                    <div id="products-submenu" class="sidebar-submenu">

                        <a href="{{ route('portal.products.index') }}" class="sidebar-link {{ request()->routeIs('portal.products.*') ? 'is-active' : '' }}">

                            <i class="fa-regular fa-rectangle-list w-6"></i>

                            <span class="sidebar-label">Ürün Listesi</span>

                        </a>

                        <a href="{{ route('portal.categories.index') }}" class="sidebar-link {{ request()->routeIs('portal.categories.*') ? 'is-active' : '' }}">

                            <i class="fa-solid fa-tags w-6"></i>

                            <span class="sidebar-label">Kategoriler</span>

                        </a>

                        <a href="{{ route('portal.brands.index') }}" class="sidebar-link {{ request()->routeIs('portal.brands.*') ? 'is-active' : '' }}">

                            <i class="fa-solid fa-certificate w-6"></i>

                            <span class="sidebar-label">Markalar</span>

                        </a>

                        <a href="#" class="sidebar-link" title="Yakında">

                            <i class="fa-solid fa-sliders w-6"></i>

                            <span class="sidebar-label">Seçenekler</span>

                        </a>

                    </div>

                @endif

                @if($inventoryModuleEnabled && $can('products'))

                    @if(auth('subuser')->check())

                        <a href="{{ route('portal.inventory.user.products.index') }}" class="sidebar-link {{ request()->routeIs('portal.inventory.user.*') ? 'is-active' : '' }}">

                            <i class="fa-solid fa-warehouse w-6"></i>

                            <span class="sidebar-label">Stok</span>

                        </a>

                    @else

                        <a href="{{ route('portal.inventory.admin.products.index') }}" class="sidebar-link {{ request()->routeIs('portal.inventory.admin.*') ? 'is-active' : '' }}">

                            <i class="fa-solid fa-warehouse w-6"></i>

                            <span class="sidebar-label">Stok</span>

                        </a>

                    @endif

                @endif



                @if($can('orders'))

                    <a href="{{ route('portal.orders.index') }}" class="sidebar-link {{ request()->routeIs('portal.orders.*') ? 'is-active' : '' }}">

                        <i class="fa-solid fa-cart-shopping w-6"></i>

                        <span class="sidebar-label">Siparişler</span>

                    </a>

                    @if($hasCargoTracking)

                        <a href="{{ route('portal.shipments.index') }}" class="sidebar-link {{ request()->routeIs('portal.shipments.*') ? 'is-active' : '' }}">

                            <i class="fa-solid fa-truck-ramp-box w-6"></i>

                            <span class="sidebar-label">Kargo Takip</span>

                        </a>

                    @endif

                @endif
                @if($can('invoices'))

                    <a href="{{ route('portal.invoices.index') }}" class="sidebar-link {{ request()->routeIs('portal.invoices.*') ? 'is-active' : '' }}">

                        <i class="fa-regular fa-file-lines w-6"></i>

                        <span class="sidebar-label">Faturalar</span>

                    </a>


                @endif
                @if($can('customers'))

                    <a href="{{ route('portal.customers.index') }}" class="sidebar-link {{ request()->routeIs('portal.customers.*') ? 'is-active' : '' }}">

                        <i class="fa-solid fa-user-group w-6"></i>

                        <span class="sidebar-label">Hesaplar</span>

                    </a>

                @endif



                @if(request()->routeIs('portal.*') || request()->is('portal*'))

                    <p class="sidebar-section">Portal</p>

                    <a href="{{ route('portal.billing') }}" class="sidebar-link {{ request()->routeIs('portal.billing') ? 'is-active' : '' }}">

                        <i class="fa-regular fa-credit-card w-6"></i>

                        <span class="sidebar-label">Billing</span>

                    </a>

                @endif

                @if($showSettingsSection)

                    <p class="sidebar-section">Ayarlar</p>

                @endif



                

                @if($hasModule('feature.tickets') && $can('tickets'))

                    <a href="{{ route('portal.tickets.index') }}" class="sidebar-link {{ request()->routeIs('portal.tickets.*') ? 'is-active' : '' }}">

                        <i class="fa-solid fa-life-ring w-6"></i>

                        <span class="sidebar-label">Destek</span>

                    </a>

                @endif

                

                    @if($hasModule('feature.sub_users'))

                    @unless(auth('subuser')->check())

                        <a href="{{ route('portal.sub-users.index') }}" class="sidebar-link {{ request()->routeIs('portal.sub-users.*') ? 'is-active' : '' }}">

                            <i class="fa-solid fa-user-shield w-6"></i>

                            <span class="sidebar-label">Alt Kullanıcılar</span>

                        </a>

                    @endunless

                @endif



                @if($canReportsAny)

                    <button id="reports-menu-toggle" type="button" class="sidebar-link w-full text-left {{ request()->routeIs('portal.reports.*') ? 'is-active' : '' }}">

                        <i class="fa-regular fa-clock w-6"></i>

                        <span class="sidebar-label flex-1">Raporlar</span>

                        <i class="fa-solid fa-chevron-down text-xs sidebar-label"></i>

                    </button>

                    <div id="reports-submenu" class="sidebar-submenu">

                        @if($canReports('reports.top_products'))

                            <a href="{{ route('portal.reports.top-products') }}" class="sidebar-link {{ request()->routeIs('portal.reports.top-products') ? 'is-active' : '' }}">

                                <span class="sidebar-label">Çok Satan Ürünler</span>

                            </a>

                        @endif

                        @if($canReports('reports.sold_products'))

                            <a href="{{ route('portal.reports.sold-products') }}" class="sidebar-link {{ request()->routeIs('portal.reports.sold-products') ? 'is-active' : '' }}">

                                <span class="sidebar-label">Satılan Ürün Listesi</span>

                            </a>

                        @endif

                        @if($canReports('reports.orders'))

                            <a href="{{ route('portal.reports.index') }}" class="sidebar-link {{ request()->routeIs('portal.reports.index') ? 'is-active' : '' }}">

                                <span class="sidebar-label">Sipariş ve Ciro</span>

                            </a>

                        @endif

                        @if($canReports('reports.profitability'))

                            <a href="{{ route('portal.reports.order-profitability') }}" class="sidebar-link {{ request()->routeIs('portal.reports.order-profitability') ? 'is-active' : '' }}">

                                <span class="sidebar-label">Sipariş Kârlılık</span>

                            </a>

                        @endif

                        @if($canReports('reports.category_sales'))

                            <a href="{{ route('portal.reports.category-sales') }}" class="sidebar-link {{ request()->routeIs('portal.reports.category-sales') ? 'is-active' : '' }}">

                                <span class="sidebar-label">Kategori Bazlı Satış</span>

                            </a>

                        @endif

                        @if($canReports('reports.brand_sales'))

                            <a href="{{ route('portal.reports.brand-sales') }}" class="sidebar-link {{ request()->routeIs('portal.reports.brand-sales') ? 'is-active' : '' }}">

                                <span class="sidebar-label">Marka Bazlı Satış</span>

                            </a>

                        @endif

                        @if($canReports('reports.vat'))

                            <a href="{{ route('portal.reports.vat') }}" class="sidebar-link {{ request()->routeIs('portal.reports.vat') ? 'is-active' : '' }}">

                                <span class="sidebar-label">KDV Raporu</span>

                            </a>

                        @endif

                        @if($canReports('reports.commission'))

                            <a href="{{ route('portal.reports.commission') }}" class="sidebar-link {{ request()->routeIs('portal.reports.commission') ? 'is-active' : '' }}">

                                <span class="sidebar-label">Komisyon Raporu</span>

                            </a>

                        @endif

                        @if($canReports('reports.stock_value'))

                            <a href="{{ route('portal.reports.stock-value') }}" class="sidebar-link {{ request()->routeIs('portal.reports.stock-value') ? 'is-active' : '' }}">

                                <span class="sidebar-label">Stoktaki Ürün Tutarları Raporu</span>

                            </a>

                        @endif

                    </div>

                @endif

                @if($canProfitability)
                    <button id="profitability-menu-toggle" type="button" class="sidebar-link w-full text-left {{ request()->routeIs('portal.profitability.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-chart-pie w-6"></i>
                        <span class="sidebar-label flex-1">Kârlılık</span>
                        <i class="fa-solid fa-chevron-down text-xs sidebar-label"></i>
                    </button>
                    <div id="profitability-submenu" class="sidebar-submenu">
                        <a href="{{ route('portal.profitability.index') }}" class="sidebar-link {{ request()->routeIs('portal.profitability.index') ? 'is-active' : '' }}">
                            <span class="sidebar-label">Genel Bakış</span>
                        </a>
                        <a href="{{ route('portal.profitability.accounts.index') }}" class="sidebar-link {{ request()->routeIs('portal.profitability.accounts.*') ? 'is-active' : '' }}">
                            <span class="sidebar-label">Hesaplar</span>
                        </a>
                    </div>
                @endif



                @if($showSubscriptionSection)

                    <p class="sidebar-section">Abonelik</p>

                @endif



                @if($can('subscription'))

                    <a href="{{ route('portal.subscription') }}" class="sidebar-link {{ request()->routeIs('portal.subscription') ? 'is-active' : '' }}">

                        <i class="fa-solid fa-crown w-6"></i>

                        <span class="sidebar-label">Paketim</span>

                    </a>

                @endif

                

                @if($hasModule('feature.tickets') && $can('tickets'))

                    <a href="{{ route('portal.tickets.index') }}" class="sidebar-link {{ request()->routeIs('portal.tickets.*') ? 'is-active' : '' }}">

                        <i class="fa-solid fa-life-ring w-6"></i>

                        <span class="sidebar-label">Destek</span>

                    </a>

                @endif

                
                        @if($can('addons'))
                            <a href="{{ route('portal.addons.index') }}" class="sidebar-link {{ request()->routeIs('portal.addons.*') ? 'is-active' : '' }}">
                                <span class="sidebar-label">Ek Modüller</span>
                            </a>
                            <a href="{{ route('portal.modules.mine') }}" class="sidebar-link {{ request()->routeIs('portal.modules.mine') ? 'is-active' : '' }}">
                                <span class="sidebar-label">Modüllerim</span>
                            </a>
                        @endif



                @if($can('settings'))
                    <button id="general-settings-menu-toggle" type="button" class="sidebar-link w-full text-left {{ request()->routeIs('portal.settings.*') ? 'is-active' : '' }}">
                        <i class="fa-solid fa-gear w-6"></i>
                        <span class="sidebar-label flex-1">Genel Ayarlar</span>
                        <i class="fa-solid fa-chevron-down text-xs sidebar-label"></i>
                    </button>
                    <div id="general-settings-submenu" class="sidebar-submenu">
                        <a href="{{ route('portal.settings.cargo.index') }}" class="sidebar-link {{ request()->routeIs('portal.settings.cargo.*') ? 'is-active' : '' }}">
                            <span class="sidebar-label">Kargo Entegrasyonları</span>
                        </a>
                        <a href="{{ route('portal.settings.api') }}" class="sidebar-link {{ request()->routeIs('portal.settings.api') ? 'is-active' : '' }}">
                            <span class="sidebar-label">API Ayarları</span>
                        </a>
                        @if($hasModule('feature.integrations') && $can('integrations'))
                            <a href="{{ route('portal.integrations.index') }}" class="sidebar-link {{ request()->routeIs('portal.integrations.*') ? 'is-active' : '' }}">
                                <span class="sidebar-label">Mağaza Bağla</span>
                            </a>
                        @endif
                        @if($can('addons'))
                            <a href="{{ route('portal.addons.index') }}" class="sidebar-link {{ request()->routeIs('portal.addons.*') ? 'is-active' : '' }}">
                                <span class="sidebar-label">Ek Modüller</span>
                            </a>
                            <a href="{{ route('portal.modules.mine') }}" class="sidebar-link {{ request()->routeIs('portal.modules.mine') ? 'is-active' : '' }}">
                                <span class="sidebar-label">Modüllerim</span>
                            </a>
                        @endif
                    </div>
                @endif
            </nav>



            <div class="p-4 border-t border-slate-200">

                @php

                    $sidebarUser = \App\Support\SupportUser::currentUser();

                @endphp

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 rounded-2xl bg-slate-200 text-slate-700 flex items-center justify-center text-lg font-bold">

                        {{ substr($sidebarUser?->name ?? 'A', 0, 1) }}

                    </div>

                    <div>

                        <p class="text-sm font-semibold text-slate-800 sidebar-label">{{ $sidebarUser?->name ?? 'Kullanıcı' }}</p>

                        <p class="text-xs text-slate-400 sidebar-label">

                            {{ $sidebarUser?->isSuperAdmin() ? 'Super Admin' : 'Hesap' }}

                        </p>

                    </div>

                </div>

            </div>

        </aside>



        <main class="flex-1 flex flex-col">

            <header class="min-h-[96px] flex items-start justify-between px-6 py-6 border-b border-slate-200/70 text-slate-800">

                <div class="flex-1 flex items-start justify-between gap-6 ml-6 pt-2">

                    <div class="hidden lg:flex items-center gap-4 text-sm">

                        <a href="{{ route('portal.help.training') }}" class="topbar-link">

                            <i class="fa-solid fa-graduation-cap text-xs"></i>

                            Eðitim Merkezi

                        </a>

                        <a href="{{ route('portal.help.support') }}" class="topbar-link">

                            <i class="fa-solid fa-headset text-xs"></i>

                            Destek Merkezi

                        </a>

                        <a href="{{ route('portal.tickets.create') }}" class="topbar-link">

                            <i class="fa-solid fa-plus text-xs"></i>

                            Destek Talebi Oluştur

                        </a>

                    </div>

                    <div class="flex items-center gap-3 pt-1">

                        @include('partials.notification-bell')

                        <div class="relative">

                            <button id="profile-menu-button" type="button" class="topbar-icon" title="Profil">

                                <i class="fa-regular fa-user text-sm"></i>

                            </button>

                            <div id="profile-menu-panel" class="absolute right-0 mt-3 w-56 origin-top-right rounded-lg border border-slate-200 bg-white p-2 shadow-lg hidden">

                                <a href="{{ route('portal.subscription') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">

                                    <i class="fa-regular fa-credit-card"></i>

                                    Abonelik Ayarları

                                </a>

                                @if(auth('subuser')->check())

                                    <a href="{{ route('portal.subuser.password.edit') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">

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

                                    <a href="{{ route('portal.sub-users.index') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">

                                        <i class="fa-solid fa-users"></i>

                                        Kullanıcı Yönetimi

                                    </a>

                                @endunless

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

                        <a href="{{ route('portal.help.refer') }}" class="topbar-icon" title="Tavsiye Et">

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

                @php

                    $supportViewEnabled = \App\Support\SupportUser::isEnabled();

                    $supportTarget = \App\Support\SupportUser::targetUser();

                @endphp

                @if($supportViewEnabled && $supportTarget)

                    <div class="panel-card px-4 py-3 mb-4 border-amber-200 text-amber-800 flex flex-col md:flex-row md:items-center md:justify-between gap-3">

                        <div>

                            <div class="font-semibold text-sm">Support View aktif</div>

                            <div class="text-xs text-amber-700">

                                {{ $supportTarget->name }} verileri görüntüleniyor. İşlem yapılamaz.

                            </div>

                        </div>

                        <form method="POST" action="{{ route('super-admin.support-view.stop') }}">

                            @csrf

                            <button type="submit" class="btn btn-outline-accent">Çık</button>

                        </form>

                    </div>

                @endif

                @if(session('success'))

                    <div class="panel-card px-4 py-3 mb-4 border-green-200 text-green-700">

                        {{ session('success') }}

                    </div>

                @endif

                @if(session('error'))

                    <div class="panel-card px-4 py-3 mb-4 border-red-200 text-red-700">

                        {{ session('error') }}

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

                'route' => 'portal.invoices.create',

                'icon' => 'fa-file-invoice',

            ],

            'categories.create' => [

                'label' => 'Kategori Ekle',

                'route' => 'portal.categories.create',

                'icon' => 'fa-tags',

            ],

            'brands.create' => [

                'label' => 'Marka Ekle',

                'route' => 'portal.brands.create',

                'icon' => 'fa-certificate',

            ],

            'products.create' => [

                'label' => 'Ürün Ekle',

                'route' => 'portal.products.create',

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

        $hideQuickActions = request()->routeIs('portal.invoices.create');

    @endphp

    @if($hasModule('feature.quick_actions') && $quickActions->isNotEmpty() && !$hideQuickActions)

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

            const hasActive = productsSubmenu.querySelector('.is-active');
            const parentActive = productsMenuToggle?.classList.contains('is-active');
            setProductsMenu(Boolean(hasActive || parentActive));

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

            const hasActive = reportsSubmenu.querySelector('.is-active');
            const parentActive = reportsMenuToggle?.classList.contains('is-active');
            setReportsMenu(Boolean(hasActive || parentActive));

        }



	        reportsMenuToggle?.addEventListener('click', () => {

	            const isOpen = reportsSubmenu?.classList.contains('is-open');

	            setReportsMenu(!isOpen);

	        });

	    </script>

        <script>
            const profitabilityMenuToggle = document.getElementById('profitability-menu-toggle');
            const profitabilitySubmenu = document.getElementById('profitability-submenu');

            function setProfitabilityMenu(open) {
                profitabilitySubmenu?.classList.toggle('is-open', open);
            }

            if (profitabilitySubmenu) {
                const hasActive = profitabilitySubmenu.querySelector('.is-active');
                const parentActive = profitabilityMenuToggle?.classList.contains('is-active');
                setProfitabilityMenu(Boolean(hasActive || parentActive));
            }

            profitabilityMenuToggle?.addEventListener('click', () => {
                const isOpen = profitabilitySubmenu?.classList.contains('is-open');
                setProfitabilityMenu(!isOpen);
            });
        </script>

	    <script>

	        const apiSettingsMenuToggle = document.getElementById('api-settings-menu-toggle');

	        const apiSettingsSubmenu = document.getElementById('api-settings-submenu');



	        function setApiSettingsMenu(open) {

	            apiSettingsSubmenu?.classList.toggle('is-open', open);

	        }



	        if (apiSettingsSubmenu) {

	            const hasActive = apiSettingsSubmenu.querySelector('.is-active');
	            const parentActive = apiSettingsMenuToggle?.classList.contains('is-active');
	            setApiSettingsMenu(Boolean(hasActive || parentActive));

	        }



        apiSettingsMenuToggle?.addEventListener('click', () => {

            const isOpen = apiSettingsSubmenu?.classList.contains('is-open');

            setApiSettingsMenu(!isOpen);

        });

    </script>

    <script>
        const generalSettingsMenuToggle = document.getElementById('general-settings-menu-toggle');
        const generalSettingsSubmenu = document.getElementById('general-settings-submenu');

        function setGeneralSettingsMenu(open) {
            generalSettingsSubmenu?.classList.toggle('is-open', open);
        }

        if (generalSettingsSubmenu) {
            const hasActive = generalSettingsSubmenu.querySelector('.is-active');
            const parentActive = generalSettingsMenuToggle?.classList.contains('is-active');
            setGeneralSettingsMenu(Boolean(hasActive || parentActive));
        }

        generalSettingsMenuToggle?.addEventListener('click', () => {
            const isOpen = generalSettingsSubmenu?.classList.contains('is-open');
            setGeneralSettingsMenu(!isOpen);
        });
    </script>


        <script>

            const notificationsMenuToggle = document.getElementById('notifications-menu-toggle');

            const notificationsSubmenu = document.getElementById('notifications-submenu');



            function setNotificationsMenu(open) {

                notificationsSubmenu?.classList.toggle('is-open', open);

            }



            if (notificationsSubmenu) {

                const hasActive = notificationsSubmenu.querySelector('.is-active');
                const parentActive = notificationsMenuToggle?.classList.contains('is-active');
                setNotificationsMenu(Boolean(hasActive || parentActive));

            }



            notificationsMenuToggle?.addEventListener('click', () => {

                const isOpen = notificationsSubmenu?.classList.contains('is-open');

                setNotificationsMenu(!isOpen);

            });

        </script>

        <script>

            const systemLogsMenuToggle = document.getElementById('system-logs-menu-toggle');

            const systemLogsSubmenu = document.getElementById('system-logs-submenu');



            function setSystemLogsMenu(open) {

                systemLogsSubmenu?.classList.toggle('is-open', open);

            }



            if (systemLogsSubmenu) {

                const hasActive = systemLogsSubmenu.querySelector('.is-active');
                const parentActive = systemLogsMenuToggle?.classList.contains('is-active');
                setSystemLogsMenu(Boolean(hasActive || parentActive));

            }



            systemLogsMenuToggle?.addEventListener('click', () => {

                const isOpen = systemLogsSubmenu?.classList.contains('is-open');

                setSystemLogsMenu(!isOpen);

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


































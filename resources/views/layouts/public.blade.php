<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pazaryeri Entegrasyon</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --brand-ink: #0f172a;
            --brand-sand: #f5f1e8;
            --brand-teal: #0f766e;
            --brand-amber: #f59e0b;
        }
        body {
            font-family: "Space Grotesk", sans-serif;
            background: radial-gradient(circle at 10% 10%, #fff7ed 0%, transparent 50%),
                        radial-gradient(circle at 80% 20%, #ecfeff 0%, transparent 45%),
                        linear-gradient(120deg, #f5f1e8 0%, #f8fafc 100%);
            color: var(--brand-ink);
        }
        .glass {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(15, 23, 42, 0.08);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            padding: 0.45rem 1rem;
            min-height: 36px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: background-color 160ms ease, color 160ms ease, border-color 160ms ease;
        }
        .btn-outline-brand {
            border: 1px solid var(--brand-teal);
            color: var(--brand-teal);
            background: transparent;
        }
        .btn-outline-brand:hover {
            background: var(--brand-teal);
            color: #ffffff;
        }
        .btn-solid-brand {
            border: 1px solid var(--brand-teal);
            background: var(--brand-teal);
            color: #ffffff;
        }
        .btn-solid-brand:hover {
            background: #0c5f59;
            border-color: #0c5f59;
            color: #ffffff;
        }
    </style>
</head>
<body class="min-h-screen">
    <header class="px-6 md:px-10 py-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-teal-700 text-white flex items-center justify-center font-bold">
                PZ
            </div>
            <div>
                <p class="text-sm uppercase tracking-widest text-slate-500">Pazaryeri</p>
                <p class="text-lg font-semibold">Entegrasyon</p>
            </div>
        </div>
        <nav class="hidden md:flex items-center gap-6 text-sm">
            <a href="{{ route('public.home') }}" class="hover:text-teal-700">Anasayfa</a>
            <a href="{{ route('pricing') }}" class="hover:text-teal-700">Fiyatland?rma</a>
            @auth
                <a href="{{ route('admin.dashboard') }}" class="hover:text-teal-700">Panelim</a>
            @else
                <a href="{{ route('login') }}" class="hover:text-teal-700">Giri?</a>
                <a href="{{ route('register') }}" class="btn btn-solid-brand">
                    Kay?t Ol
                </a>
            @endauth
        </nav>
        <div class="md:hidden text-sm">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="text-teal-700 font-semibold">Panelim</a>
            @else
                <a href="{{ route('login') }}" class="text-teal-700 font-semibold">Giri?</a>
            @endauth
        </div>
    </header>

    <main class="px-6 md:px-10 pb-16">
        @yield('content')
    </main>

    <footer class="px-6 md:px-10 py-10 text-xs text-slate-500">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <span>? {{ date('Y') }} Pazaryeri Entegrasyon. T?m haklar? sakl?d?r.</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('pricing') }}" class="hover:text-teal-700">Paketler</a>
                <a href="{{ route('login') }}" class="hover:text-teal-700">Giri?</a>
            </div>
        </div>
    </footer>
</body>
</html>

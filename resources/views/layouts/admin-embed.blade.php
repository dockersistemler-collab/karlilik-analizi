<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Pazaryeri Paneli')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            border-radius: 999px;
            padding: .55rem 1rem;
            font-size: .875rem;
            font-weight: 600;
            line-height: 1;
            border: 1px solid transparent;
            text-decoration: none;
        }

        .btn-outline-accent {
            background: #fff;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .btn-solid-accent {
            background: #ff4439;
            border-color: #ff4439;
            color: #fff;
        }

        input[type="text"],
        input[type="date"],
        input[type="number"],
        input[type="email"],
        input[type="password"],
        select,
        textarea {
            height: 44px;
            border-radius: 12px !important;
            border: 1px solid #d7e1ee !important;
            background: linear-gradient(180deg, #ffffff 0%, #f7faff 100%) !important;
            color: #0f172a !important;
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
            padding: 0 0.875rem !important;
        }

        textarea {
            min-height: 120px;
            height: auto;
            padding: 0.75rem 0.875rem !important;
        }

        input[type="text"]:focus,
        input[type="date"]:focus,
        input[type="number"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus,
        textarea:focus {
            border-color: #93c5fd !important;
            box-shadow: 0 0 0 4px rgba(147, 197, 253, 0.2), 0 10px 22px rgba(15, 23, 42, 0.08) !important;
            outline: none;
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="p-4 md:p-6">
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>

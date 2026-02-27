<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>Gecikmiş İletişim Bildirimi</title>
</head>
<body style="font-family:Arial,sans-serif;color:#0f172a;">
    <h2>Gecikmiş İletişim Bildirimi</h2>
    <p>Gecikmiş müşteri iletişimleri aşağıdadır:</p>
    <ul>
        @foreach($threads as $thread)
            <li>
                {{ $thread->customer_name ?? 'Müşteri' }} -
                {{ $thread->product_name ?? 'Ürün belirtilmedi' }} -
                {{ strtoupper($thread->channel) }} -
                {{ optional($thread->due_at)->format('d.m.Y H:i') }}
            </li>
        @endforeach
    </ul>
</body>
</html>


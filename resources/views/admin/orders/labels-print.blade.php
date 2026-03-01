<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kargo Etiket Yazdırma</title>
    <style>
        :root {
            --ink: #0f172a;
            --muted: #475569;
            --line: #cbd5e1;
            --card: #ffffff;
            --bg: #f8fafc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background: var(--bg);
            padding: 16px;
        }
        .print-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .print-toolbar button {
            border: 1px solid #1d4ed8;
            background: #2563eb;
            color: #fff;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .print-info {
            color: var(--muted);
            font-size: 13px;
        }
        .label-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 14px;
        }
        .label-card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 14px;
            min-height: 220px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            page-break-inside: avoid;
        }
        .label-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            border-bottom: 1px dashed var(--line);
            padding-bottom: 8px;
        }
        .label-platform {
            font-size: 14px;
            font-weight: 700;
        }
        .label-order {
            font-size: 12px;
            color: var(--muted);
        }
        .label-row {
            display: grid;
            grid-template-columns: 110px 1fr;
            gap: 8px;
            font-size: 13px;
            line-height: 1.35;
        }
        .label-row span:first-child {
            color: var(--muted);
        }
        .label-address {
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 8px;
            font-size: 13px;
            line-height: 1.35;
            white-space: pre-wrap;
            min-height: 64px;
        }
        .label-footer {
            margin-top: auto;
            border-top: 1px dashed var(--line);
            padding-top: 8px;
            font-size: 12px;
            color: var(--muted);
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .print-toolbar { display: none; }
            .label-grid { gap: 10px; }
            .label-card { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <div class="print-info">
            Platform: <strong>{{ $platformName }}</strong> |
            Etiket: <strong>{{ $labels->count() }}</strong> |
            Tür: <strong>{{ $isBulk ? 'Toplu' : 'Tekli' }}</strong>
        </div>
        <button type="button" onclick="window.print()">Yazdır</button>
    </div>

    <div class="label-grid">
        @foreach($labels as $label)
            <article class="label-card">
                <div class="label-top">
                    <div class="label-platform">{{ $label['platform'] }}</div>
                    <div class="label-order">Sipariş: {{ $label['order_no'] }}</div>
                </div>

                <div class="label-row"><span>Müşteri</span><span>{{ $label['customer_name'] }}</span></div>
                <div class="label-row"><span>Telefon</span><span>{{ $label['customer_phone'] }}</span></div>
                <div class="label-row"><span>Kargo</span><span>{{ $label['cargo_company'] }}</span></div>
                <div class="label-row"><span>Takip No</span><span>{{ $label['tracking_number'] }}</span></div>

                <div class="label-address">
                    {{ $label['address_line'] }}
                    @if($label['district'] || $label['city'] || $label['postal_code'])
{{ trim($label['district'].' / '.$label['city']) }} {{ $label['postal_code'] }}
                    @endif
                </div>

                <div class="label-footer">
                    <span>Sipariş Tarihi: {{ $label['order_date'] }}</span>
                    <span>Yazdırma: {{ $printedAt->format('d.m.Y H:i') }}</span>
                </div>
            </article>
        @endforeach
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>

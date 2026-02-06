<!doctype html>

<html lang="tr">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $invoice->invoice_no ?? 'E-Fatura' }}</title>

    <style>

        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; }

        .container { max-width: 800px; margin: 0 auto; padding: 24px; }

        .row { display: flex; justify-content: space-between; gap: 16px; }

        .muted { color: #6B7280; font-size: 12px; }

        h1 { font-size: 18px; margin: 0; }

        table { width: 100%; border-collapse: collapse; margin-top: 16px; }

        th, td { border-bottom: 1px solid #E5E7EB; padding: 8px; font-size: 12px; text-align: left; }

        .totals { margin-top: 16px; width: 280px; margin-left: auto; font-size: 12px; }

        .totals .line { display: flex; justify-content: space-between; padding: 4px 0; }

        .totals .grand { font-weight: 700; }

    </style>

</head>

<body>

    <div class="container">

        <div class="row">

            <div>

                <h1>E-Fatura</h1>

                <div class="muted">Fatura No: {{ $invoice->invoice_no ?? '-' }}</div>

                <div class="muted">Tarih: {{ $invoice->issued_at?->format('d.m.Y H:i') ?? '-' }}</div>

                <div class="muted">Sipariş: {{ $invoice->marketplace_order_no ?? $invoice->source_id }}</div>

            </div>

            <div>

                <div class="muted">Alıcı</div>

                <div>{{ $invoice->buyer_name ?? '-' }}</div>

                <div class="muted">{{ $invoice->buyer_email ?? '' }}</div>

                <div class="muted">{{ $invoice->buyer_phone ?? '' }}</div>

            </div>

        </div>



        <table>

            <thead>

            <tr>

                <th>SKU</th>

                <th>Ürün</th>

                <th>Adet</th>

                <th>Birim</th>

                <th>KDV</th>

                <th>Toplam</th>

            </tr>

            </thead>

            <tbody>

            @foreach($invoice->items as $item)

                <tr>

                    <td>{{ $item->sku ?? '-' }}</td>

                    <td>{{ $item->name }}</td>

                    <td>{{ $item->quantity }}</td>

                    <td>{{ number_format((float) $item->unit_price, 2) }}</td>

                    <td>%{{ $item->vat_rate }}</td>

                    <td>{{ number_format((float) $item->total, 2) }} {{ $invoice->currency }}</td>

                </tr>

            @endforeach

            </tbody>

        </table>



        <div class="totals">

            <div class="line"><span>Ara Toplam</span><span>{{ number_format((float) $invoice->subtotal, 2) }} {{ $invoice->currency }}</span></div>

            <div class="line"><span>KDV</span><span>{{ number_format((float) $invoice->tax_total, 2) }} {{ $invoice->currency }}</span></div>

            <div class="line grand"><span>Genel Toplam</span><span>{{ number_format((float) $invoice->grand_total, 2) }} {{ $invoice->currency }}</span></div>

        </div>

    </div>

</body>

</html>




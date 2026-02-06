<!doctype html>

<html lang="tr">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Satılan Ürünler Raporu</title>

    <style>

        body { font-family: Arial, sans-serif; color: #111827; margin: 24px; }

        h1 { font-size: 18px; margin-bottom: 8px; }

        .meta { font-size: 12px; color: #6b7280; margin-bottom: 16px; }

        table { width: 100%; border-collapse: collapse; font-size: 12px; }

        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; }

        th { text-transform: uppercase; font-size: 11px; color: #6b7280; }

    </style>

</head>

<body>

    <h1>Satılan Ürünler Raporu</h1>

    <div class="meta">

        Filtre: {{ $filters['date_from'] ?? '-' }} / {{ $filters['date_to'] ?? '-' }}

    </div>

    <table>

        <thead>

            <tr>

                <th>Stok Kodu</th>

                <th>Ürün Adı</th>

                <th>Seçenek</th>

                <th>Satış Adedi</th>

            </tr>

        </thead>

        <tbody>

            @forelse($rows as $row)

                <tr>

                    <td>{{ $row['stock_code'] ?? '-' }}</td>

                    <td>{{ $row['name'] }}</td>

                    <td>{{ $row['variant'] ?? '-' }}</td>

                    <td>{{ number_format($row['quantity']) }}</td>

                </tr>

            @empty

                <tr>

                    <td colspan="4">Kayıt bulunamadı.</td>

                </tr>

            @endforelse

        </tbody>

    </table>

</body>

</html>



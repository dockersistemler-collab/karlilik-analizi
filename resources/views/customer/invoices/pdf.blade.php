<!doctype html>

<html lang="tr">

<head>

    <meta charset="utf-8">

    <title>Fatura {{ $invoice->invoice_number }}</title>

    <style>

        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }

        .header { margin-bottom: 16px; }

        .title { font-size: 16px; font-weight: 700; }

        .meta { margin-top: 6px; }

        .row { margin-bottom: 8px; }

        .label { color: #6b7280; font-size: 11px; }

    </style>

    </head>

<body>

    <div class="header">

        <div class="title">Fatura {{ $invoice->invoice_number }}</div>

        <div class="meta">Tarih: {{ $invoice->issued_at?->format('d.m.Y') ?? '-' }}</div>

    </div>

    <div class="row">

        <div class="label">Tutar</div>

        <div>{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</div>

    </div>

    <div class="row">

        <div class="label">Durum</div>

        <div>{{ $invoice->status }}</div>

    </div>

    <div class="row">

        <div class="label">Fatura Bilgileri</div>

        <div>{{ $invoice->billing_name ?? '-' }}</div>

        <div>{{ $invoice->billing_email ?? '' }}</div>

        <div>{{ $invoice->billing_address ?? '' }}</div>

    </div>

</body>

</html>


@extends('layouts.admin')

@section('header')
    Sipariş Detay
@endsection

@section('content')
@php
    $isPopupMode = request()->boolean('popup');
    $statusMap = [
        'pending' => ['Beklemede', 'bg-yellow-100 text-yellow-800 border-yellow-200'],
        'approved' => ['Onaylandı', 'bg-blue-100 text-blue-800 border-blue-200'],
        'shipped' => ['Kargoda', 'bg-purple-100 text-purple-800 border-purple-200'],
        'delivered' => ['Teslim', 'bg-green-100 text-green-800 border-green-200'],
        'cancelled' => ['İptal', 'bg-red-100 text-red-800 border-red-200'],
        'returned' => ['İade', 'bg-orange-100 text-orange-800 border-orange-200'],
    ];
    [$statusLabel, $statusClass] = $statusMap[$order->status] ?? [ucfirst((string) $order->status), 'bg-slate-100 text-slate-700 border-slate-200'];

    $shippingAddress = $order->shipping_address;
    if (is_array($shippingAddress)) {
        $shippingAddress = trim(implode(' ', array_filter([
            $shippingAddress['address'] ?? $shippingAddress['line1'] ?? null,
            $shippingAddress['district'] ?? null,
            $shippingAddress['city'] ?? null,
        ])));
    }
    $billingAddress = $order->billing_address;
    if (is_array($billingAddress)) {
        $billingAddress = trim(implode(' ', array_filter([
            $billingAddress['address'] ?? $billingAddress['line1'] ?? null,
            $billingAddress['district'] ?? null,
            $billingAddress['city'] ?? null,
        ])));
    }
    $items = is_array($order->items) ? $order->items : [];
    $itemCount = count($items);
    $itemPreview = collect($items)->pluck('name')->filter()->take(2)->implode(', ');
    if ($itemPreview === '') {
        $itemPreview = '-';
    } elseif ($itemCount > 2) {
        $itemPreview .= ' +' . ($itemCount - 2);
    }
@endphp

<style>
    .popup-order-shell {
        border: 1px solid #dbe7f5;
        border-radius: 18px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
        padding: 1rem;
        font-family: "Manrope", "Segoe UI", "Inter", sans-serif;
    }
    .popup-order-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .8rem;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: .7rem;
        margin-bottom: .75rem;
    }
    .popup-order-title {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
    }
    .popup-order-no {
        font-size: .88rem;
        color: #475569;
        font-weight: 700;
        text-align: right;
    }
    .popup-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: .5rem;
    }
    .popup-item {
        border: 1px solid #dbe7f5;
        border-radius: 12px;
        background: #fff;
        padding: .62rem .72rem;
    }
    .popup-line {
        display: flex;
        align-items: flex-start;
        gap: .45rem;
        flex-wrap: nowrap;
    }
    .popup-line--flow {
        display: block;
    }
    .popup-line--flow .popup-label {
        display: inline;
        white-space: normal;
        margin-right: .25rem;
    }
    .popup-line--flow .popup-label i {
        display: inline-flex;
        vertical-align: middle;
        margin-right: .2rem;
    }
    .popup-line--flow .popup-value {
        display: inline;
        min-width: 0;
    }
    .popup-order-shell .popup-label {
        font-size: .88rem;
        color: #111827 !important;
        font-weight: 900 !important;
        display: inline-flex;
        align-items: center;
        gap: .38rem;
        white-space: nowrap;
        flex: 0 0 auto;
    }
    .popup-label i {
        width: 1.4rem;
        height: 1.4rem;
        border-radius: 999px;
        border: 1px solid #dbe7f5;
        background: #f8fbff;
        color: #7c8fb0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .7rem;
    }
    .popup-order-shell .popup-value,
    .popup-order-shell .popup-value-light {
        font-size: .88rem;
        color: #1e293b !important;
        font-weight: 400 !important;
        font-style: normal !important;
        line-height: 1.35;
        word-break: break-word;
        min-width: 0;
        flex: 1 1 auto;
        font-family: "Manrope", "Segoe UI", "Inter", sans-serif !important;
    }
    .popup-order-shell .popup-value-light b,
    .popup-order-shell .popup-value-light strong {
        font-weight: 400 !important;
    }
    .popup-order-shell .popup-value.amount,
    .popup-order-shell .popup-value-light.amount {
        color: #059669 !important;
        font-weight: 400 !important;
    }
    .popup-status {
        display: inline-flex;
        border: 1px solid;
        border-radius: 999px;
        padding: .2rem .56rem;
        font-size: .74rem;
        font-weight: 800;
    }
    .popup-block {
        margin-top: .75rem;
        border-top: 1px solid #e2e8f0;
        padding-top: .75rem;
    }
    .popup-block-title {
        font-size: .9rem;
        color: #0f172a;
        font-weight: 800;
        margin-bottom: .5rem;
    }
    .popup-cargo-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: .45rem;
    }
    .popup-cargo-item {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        padding: .56rem .62rem;
    }
    .popup-cargo-label { font-size: .86rem; color: #111827 !important; font-weight: 900 !important; white-space: nowrap; flex: 0 0 auto; }
    .popup-cargo-value { font-size: .86rem; color: #1e293b !important; font-weight: 400 !important; font-style: normal !important; min-width: 0; flex: 1 1 auto; font-family: "Manrope", "Segoe UI", "Inter", sans-serif !important; }
    .popup-items {
        margin-top: .45rem;
        display: grid;
        gap: .35rem;
    }
    .popup-items li {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        padding: .36rem .5rem;
        font-size: .82rem;
        color: #334155;
    }
    @media (min-width: 768px) {
        .popup-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .popup-cargo-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
</style>

@if($isPopupMode)
<section class="popup-order-shell">
    <header class="popup-order-head">
        <h3 class="popup-order-title"><i class="fa-solid fa-circle-info"></i> Sipariş Detayı</h3>
        <div class="popup-order-no">Sipariş Numarası: {{ $order->marketplace_order_id }}</div>
    </header>

    <div class="popup-grid">
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-regular fa-user"></i> Müşteri :</span><span class="popup-value-light">{{ $order->customer_name ?: '-' }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-solid fa-store"></i> Pazaryeri :</span><span class="popup-value-light">{{ $order->marketplace->name ?? '-' }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-regular fa-circle-check"></i> Durum :</span><span class="popup-status {{ $statusClass }}">{{ $statusLabel }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-solid fa-wallet"></i> Toplam Tutar :</span><span class="popup-value-light amount">{{ number_format((float) $order->total_amount, 2) }} {{ $order->currency }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-solid fa-percent"></i> Komisyon :</span><span class="popup-value-light">{{ number_format((float) ($order->commission_amount ?? 0), 2) }} {{ $order->currency }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-solid fa-coins"></i> Net Tutar :</span><span class="popup-value-light">{{ number_format((float) ($order->net_amount ?? 0), 2) }} {{ $order->currency }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-solid fa-box"></i> Ürün Kalemi :</span><span class="popup-value-light">{{ $itemCount }} ürün</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-solid fa-cubes"></i> Ürünler :</span><span class="popup-value-light">{{ $itemPreview }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line popup-line--flow"><span class="popup-label"><i class="fa-solid fa-truck-fast"></i> Teslimat Adresi :</span><span class="popup-value-light">{{ $shippingAddress ?: '-' }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line popup-line--flow"><span class="popup-label"><i class="fa-regular fa-file-lines"></i> Fatura Adresi :</span><span class="popup-value-light">{{ $billingAddress ?: '-' }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-regular fa-calendar"></i> Sipariş Tarihi :</span><span class="popup-value-light">{{ optional($order->order_date)->format('d.m.Y H:i') ?? '-' }}</span></div>
        </div>
        <div class="popup-item">
            <div class="popup-line"><span class="popup-label"><i class="fa-solid fa-phone"></i> Telefon :</span><span class="popup-value-light">{{ $order->customer_phone ?: '-' }}</span></div>
        </div>
    </div>

    <div class="popup-block">
        <h4 class="popup-block-title">Kargo Bilgileri</h4>
        <div class="popup-cargo-grid">
            <div class="popup-cargo-item">
                <div class="popup-line"><span class="popup-cargo-label">Kargo Firması :</span><span class="popup-cargo-value">{{ $order->cargo_company ?: 'Henüz belirlenmedi' }}</span></div>
            </div>
            <div class="popup-cargo-item">
                <div class="popup-line"><span class="popup-cargo-label">Takip No :</span><span class="popup-cargo-value">{{ $order->tracking_number ?: 'Henüz belirlenmedi' }}</span></div>
            </div>
            <div class="popup-cargo-item">
                <div class="popup-line"><span class="popup-cargo-label">Kargoya Veriliş :</span><span class="popup-cargo-value">{{ optional($order->shipped_at)->format('d.m.Y H:i') ?? '-' }}</span></div>
            </div>
            <div class="popup-cargo-item">
                <div class="popup-line"><span class="popup-cargo-label">Teslim Tarihi :</span><span class="popup-cargo-value">{{ optional($order->delivered_at)->format('d.m.Y H:i') ?? '-' }}</span></div>
            </div>
        </div>
    </div>

    <div class="popup-block">
        <details>
            <summary class="popup-block-title" style="cursor:pointer; margin-bottom:0;">Ürün kalemlerini göster</summary>
            @if($itemCount > 0)
                <ul class="popup-items">
                    @foreach($items as $item)
                        <li>
                            {{ $item['name'] ?? 'Ürün adı yok' }} -
                            {{ $item['quantity'] ?? 0 }} x {{ number_format((float) ($item['price'] ?? 0), 2) }} {{ $order->currency }}
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-slate-500 mt-2">Sipariş kalemi bilgisi bulunmuyor.</p>
            @endif
        </details>
    </div>
</section>
<script>
    (function () {
        const fitPopupHeight = () => {
            try {
                const shell = document.querySelector('.popup-order-shell');
                if (!shell || !window.resizeTo) return;
                const chromeHeight = Math.max(0, window.outerHeight - window.innerHeight);
                const targetHeight = Math.ceil(shell.getBoundingClientRect().height + chromeHeight + 40);
                window.resizeTo(window.outerWidth, Math.max(320, targetHeight));
            } catch (e) {
                // ignore
            }
        };

        window.addEventListener('load', fitPopupHeight);
        setTimeout(fitPopupHeight, 120);
    })();
</script>
@else
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Sipariş Bilgileri</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">Sipariş No</dt>
                <dd><code class="bg-gray-100 px-2 py-1 rounded">{{ $order->marketplace_order_id }}</code></dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Pazaryeri</dt>
                <dd class="font-semibold">{{ $order->marketplace->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Sipariş Tarihi</dt>
                <dd>{{ optional($order->order_date)->format('d.m.Y H:i') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Durum</dt>
                <dd><span class="px-2 py-1 text-xs rounded border {{ $statusClass }}">{{ $statusLabel }}</span></dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Toplam Tutar</dt>
                <dd class="text-2xl font-bold text-green-600">{{ number_format((float) $order->total_amount, 2) }} {{ $order->currency }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Komisyon</dt>
                <dd>{{ number_format((float) ($order->commission_amount ?? 0), 2) }} {{ $order->currency }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Net Tutar</dt>
                <dd class="font-semibold">{{ number_format((float) ($order->net_amount ?? 0), 2) }} {{ $order->currency }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Müşteri Bilgileri</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">Ad Soyad</dt>
                <dd class="font-semibold">{{ $order->customer_name ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">E-posta</dt>
                <dd>{{ $order->customer_email ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Telefon</dt>
                <dd>{{ $order->customer_phone ?: '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Teslimat Adresi</dt>
                <dd class="text-sm">{{ $shippingAddress ?: '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Kargo Bilgileri</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">Kargo Firması</dt>
                <dd>{{ $order->cargo_company ?: 'Henüz belirlenmedi' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Takip No</dt>
                <dd>{{ $order->tracking_number ?: 'Henüz belirlenmedi' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Kargoya Veriliş</dt>
                <dd>{{ optional($order->shipped_at)->format('d.m.Y H:i') ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Teslim Tarihi</dt>
                <dd>{{ optional($order->delivered_at)->format('d.m.Y H:i') ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Durum Geçmişi</h3>
        <div class="space-y-3">
            @forelse($order->statusLogs as $log)
                <div class="border-b pb-2">
                    <div class="text-sm font-semibold text-gray-900">
                        {{ ucfirst((string) ($log->old_status ?? 'başlangıç')) }} -> {{ ucfirst((string) $log->new_status) }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $log->created_at->format('d.m.Y H:i') }}
                        @if($log->user)
                            • {{ $log->user->name }}
                        @endif
                    </div>
                    @if($log->note)
                        <div class="text-sm text-gray-600 mt-1">{{ $log->note }}</div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">Durum geçmişi bulunmuyor.</p>
            @endforelse
        </div>
    </div>
</div>
@endif
@endsection

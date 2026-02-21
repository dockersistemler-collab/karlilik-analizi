@extends('layouts.admin')



@section('header')

    SipariÃ…Å¸ler

@endsection



@section('content')

@php

    $tabs = [

        'all' => 'TÃ¼m SipariÃ…Å¸ler',

        'pending' => 'Onay Bekleyen',

        'approved' => 'Onaylanan',

        'shipped' => 'Kargolanan',

        'delivered' => 'Teslim',

        'cancelled' => 'Ä°ptal',

        'returned' => 'Ä°ade',

    ];

    $activeTab = request('status') ?: 'all';

    $ownerUser = \App\Support\SupportUser::currentUser();

    $canExport = $ownerUser ? app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, 'feature.exports') : false;

    $supportViewEnabled = \App\Support\SupportUser::isEnabled();
    $marketplaceLogoMap = [
        'amazon tr' => 'images/brands/amazon.png',
        'amazon' => 'images/brands/amazon.png',
        'hepsiburada' => 'images/brands/hepsiburada.png',
        'n11' => 'images/brands/n11.png',
        'trendyol' => 'images/brands/trendyol.png',
        'cicek sepeti' => 'images/brands/ciceksepeti.png',
    ];

@endphp

<style>
    .orders-thumb-wrap {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: zoom-in;
        overflow: hidden;
    }
    .orders-thumb {
        width: 48px;
        height: 48px;
        object-fit: cover;
        border-radius: 0.75rem;
    }
    .orders-thumb-placeholder {
        width: 48px;
        height: 48px;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #f1f5f9;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .orders-image-popover {
        position: fixed;
        z-index: 1400;
        pointer-events: none;
        width: 220px;
        height: 220px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.28);
        overflow: hidden;
        display: none;
    }
    .orders-image-popover.is-open {
        display: block;
    }
    .orders-image-popover img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #f8fafc;
    }
    .orders-market-logo-wrap {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .orders-market-cell {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
        width: 96px;
        margin: 0 auto;
    }
    .orders-market-name {
        font-size: 10px;
        line-height: 1.1;
        color: #64748b;
        font-weight: 600;
        text-align: center;
        max-width: 84px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .orders-market-logo {
        width: 38px;
        height: 38px;
        border-radius: 9px;
        object-fit: contain;
        background: #fff;
        border: 1px solid #dbe7f5;
        padding: 1px;
    }
    .orders-market-fallback {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
        display: inline-block;
        width: 96px;
        text-align: center;
    }
</style>



<div class="panel-card p-4 mb-6">

    <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 pb-3">

        @foreach($tabs as $key => $label)

            <a href="{{ route('portal.orders.index', array_filter(array_merge(request()->query(), ['status' => $key === 'all' ? null : $key]))) }}"

               class="text-sm font-semibold {{ $activeTab === $key ? 'text-blue-600 border-b-2 border-blue-600 pb-2' : 'text-slate-400' }}">

                {{ $label }}

            </a>

        @endforeach

    </div>



    <form method="GET" action="{{ route('portal.orders.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 mt-4">

        <div>

            <label class="block text-xs font-medium text-slate-500 mb-1">Pazaryeri</label>

            <select name="marketplace_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

                <option value="">TÃ¼mÃ¼</option>

                @foreach($marketplaces as $marketplace)

                    <option value="{{ $marketplace->id }}" {{ request('marketplace_id') == $marketplace->id ? 'selected' : '' }}>

                        {{ $marketplace->name }}

                    </option>

                @endforeach

            </select>

        </div>



        <div>

            <label class="block text-xs font-medium text-slate-500 mb-1">Durum</label>

            <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

                <option value="">TÃ¼mÃ¼</option>

                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Beklemede</option>

                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>OnaylandÄ±</option>

                <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Kargoda</option>

                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Teslim</option>

                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Ä°ptal</option>

                <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Ä°ade</option>

            </select>

        </div>



        <div>

            <label class="block text-xs font-medium text-slate-500 mb-1">BaÃ…Å¸langÄ±Ã§</label>

            <input type="date" name="date_from" value="{{ request('date_from') }}"

                class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

        </div>



        <div>

            <label class="block text-xs font-medium text-slate-500 mb-1">BitiÃ…Å¸</label>

            <input type="date" name="date_to" value="{{ request('date_to') }}"

                class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

        </div>



        <div class="flex items-end gap-2">

            <button type="submit" class="btn btn-solid-accent w-full">

                Filtrele

            </button>

            <a href="{{ route('portal.orders.index') }}" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50">

                Temizle

            </a>

        </div>

    </form>

</div>



<form method="POST" action="{{ route('portal.orders.bulk-update') }}">

    @csrf

    <div class="panel-card p-4 mb-4 flex flex-col md:flex-row md:items-end gap-3">

        <div>

            <label class="block text-xs font-medium text-slate-500 mb-1">Toplu Durum</label>

            <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" required>

                <option value="">SeÃ§iniz</option>

                <option value="pending">Beklemede</option>

                <option value="approved">OnaylandÄ±</option>

                <option value="shipped">Kargoda</option>

                <option value="delivered">Teslim</option>

                <option value="cancelled">Ä°ptal</option>

                <option value="returned">Ä°ade</option>

            </select>

        </div>

        <div class="flex-1">

            <label class="block text-xs font-medium text-slate-500 mb-1">Not (opsiyonel)</label>

            <input type="text" name="note" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" placeholder="Toplu gÃ¼ncelleme notu">

        </div>

        <button type="submit" class="btn btn-solid-accent">

            SeÃ§ili SipariÃ…Å¸leri GÃ¼ncelle

        </button>

        @if($canExport)

        <a href="{{ route('portal.orders.export', request()->query()) }}" class="ml-auto btn btn-outline-accent">

            CSV indir

        </a>

        @endif

    </div>



    <div class="panel-card overflow-hidden">

        <table class="min-w-full" id="orders-table">

        <thead class="bg-slate-50">

            <tr>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <input type="checkbox" id="orders-select-all" class="rounded border-slate-300 text-[#ff4439] focus:ring-[#ff4439]">

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Görsel</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Sipariş No</th>

                <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase">Pazaryeri</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">MÃ¼ÅŸteri</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tutar</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Durum</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">SipariÃ…Å¸ Tarihi</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ä°Ã…Å¸lem</th>

            </tr>

        </thead>

        <tbody class="bg-white divide-y divide-slate-100">

            @forelse($orders as $order)

            @php
                $firstItem = is_array($order->items) ? ($order->items[0] ?? null) : null;
                $orderImageUrl = data_get($firstItem, 'image_url')
                    ?: data_get($firstItem, 'image')
                    ?: data_get($firstItem, 'product_image')
                    ?: data_get($firstItem, 'productImage');
                $marketplaceName = (string) ($order->marketplace->name ?? '');
                $marketplaceKey = \Illuminate\Support\Str::of(trim($marketplaceName))->lower()->ascii()->value();
                $marketplaceLogo = $marketplaceLogoMap[$marketplaceKey] ?? null;
            @endphp

            <tr>

                <td class="px-6 py-4 whitespace-nowrap">

                    <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="orders-row-check rounded border-slate-300 text-[#ff4439] focus:ring-[#ff4439]">

                    <input type="checkbox" class="hidden orders-bulk-ship-check" name="bulk_ship_ids[]" value="{{ $order->id }}">

                </td>

                <td class="px-6 py-4 whitespace-nowrap">
                    @if($orderImageUrl)
                        <span class="orders-thumb-wrap"
                              data-order-preview-src="{{ $orderImageUrl }}"
                              data-order-preview-alt="{{ $order->marketplace_order_id }}">
                            <img src="{{ $orderImageUrl }}" alt="{{ $order->marketplace_order_id }}" class="orders-thumb">
                        </span>
                    @else
                        <span class="orders-thumb-placeholder">
                            <i class="fas fa-image text-slate-400"></i>
                        </span>
                    @endif
                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <code class="bg-slate-100 px-2 py-1 rounded text-xs">{{ $order->marketplace_order_id }}</code>

                </td>

                <td class="px-6 py-4 whitespace-nowrap font-semibold text-center align-middle">
                    @if($marketplaceLogo)
                        <span class="orders-market-cell">
                            <span class="orders-market-logo-wrap">
                                <img src="{{ asset($marketplaceLogo) }}" alt="{{ $marketplaceName }}" class="orders-market-logo" title="{{ $marketplaceName }}">
                            </span>
                            <span class="orders-market-name">{{ $marketplaceName }}</span>
                        </span>
                    @else
                        <span class="orders-market-fallback">{{ $marketplaceName ?: '-' }}</span>
                    @endif
                </td>

                <td class="px-6 py-4">

                    <div class="text-sm font-medium text-slate-900">{{ $order->customer_name }}</div>

                    @if($order->customer_phone)

                        <div class="text-xs text-slate-500">

                            {{ $supportViewEnabled ? \App\Support\SupportUser::maskPhone($order->customer_phone) : $order->customer_phone }}

                        </div>

                    @endif

                </td>

                <td class="px-6 py-4 whitespace-nowrap font-semibold">

                    {{ number_format($order->total_amount, 2) }} {{ $order->currency }}

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <span class="panel-pill text-xs 

                        @if($order->status == 'pending') bg-yellow-100 text-yellow-800

                        @elseif($order->status == 'approved') bg-blue-100 text-blue-800

                        @elseif($order->status == 'shipped') bg-indigo-100 text-indigo-800

                        @elseif($order->status == 'delivered') bg-green-100 text-green-800

                        @elseif($order->status == 'cancelled') bg-red-100 text-red-800

                        @elseif($order->status == 'returned') bg-orange-100 text-orange-800

                        @endif">

                        {{ ucfirst($order->status) }}

                    </span>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $order->order_date->format('d.m.Y H:i') }}</td>

                <td class="px-6 py-4 whitespace-nowrap text-sm">

                    <a href="{{ route('portal.orders.show', $order) }}" class="text-blue-600 hover:text-blue-900">

                        <i class="fas fa-eye"></i>

                    </a>

                </td>

            </tr>

            @empty

            <tr>

                <td colspan="9" class="px-6 py-4 text-center text-slate-500">HenÃ¼z sipariÃ…Å¸ bulunmuyor</td>

            </tr>

            @endforelse

        </tbody>

        </table>

    </div>

</form>



<form method="POST" action="{{ route('portal.orders.bulk-ship') }}">

    @csrf

    <div class="panel-card p-4 mb-4 grid grid-cols-1 md:grid-cols-5 gap-3">

        <div class="md:col-span-2">

            <label class="block text-xs font-medium text-slate-500 mb-1">Kargo FirmasÄ±</label>

            <input type="text" name="cargo_company" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" required>

        </div>

        <div class="md:col-span-2">

            <label class="block text-xs font-medium text-slate-500 mb-1">Takip No</label>

            <input type="text" name="tracking_number" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" required>

        </div>

        <div>

            <label class="block text-xs font-medium text-slate-500 mb-1">Not</label>

            <input type="text" name="note" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

        </div>

        <div class="md:col-span-5">

            <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">

                SeÃ§ili SipariÃ…Å¸leri Kargoya Al

            </button>

        </div>

    </div>

</form>



<div class="mt-6">

    {{ $orders->links() }}

</div>

<div id="orders-image-popover" class="orders-image-popover" aria-hidden="true">
    <img id="orders-image-popover-img" src="" alt="">
</div>

<script>

    const ordersSelectAll = document.getElementById('orders-select-all');
    const orderRowChecks = Array.from(document.querySelectorAll('.orders-row-check'));

    const syncOrdersSelectAllState = () => {
        if (!ordersSelectAll) return;
        const checkedCount = orderRowChecks.filter((cb) => cb.checked).length;
        ordersSelectAll.checked = checkedCount > 0 && checkedCount === orderRowChecks.length;
        ordersSelectAll.indeterminate = checkedCount > 0 && checkedCount < orderRowChecks.length;
    };

    ordersSelectAll?.addEventListener('change', () => {
        orderRowChecks.forEach((cb) => {
            cb.checked = ordersSelectAll.checked;
            const hidden = cb.closest('td')?.querySelector('.orders-bulk-ship-check');
            if (hidden) {
                hidden.checked = cb.checked;
            }
        });
        syncOrdersSelectAllState();
    });

    document.querySelectorAll('input[name="order_ids[]"]').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var hidden = cb.parentElement.querySelector('input[name="bulk_ship_ids[]"]');
            if (hidden) {
                hidden.checked = cb.checked;
            }
            syncOrdersSelectAllState();
        });
    });

    syncOrdersSelectAllState();

    const ordersImagePopover = document.getElementById('orders-image-popover');
    const ordersImagePopoverImg = document.getElementById('orders-image-popover-img');
    const ordersImageTriggers = Array.from(document.querySelectorAll('[data-order-preview-src]'));

    const placeOrdersPopover = (event) => {
        if (!ordersImagePopover || !ordersImagePopoverImg) return;
        const offset = 16;
        const width = 220;
        const height = 220;
        let left = event.clientX + offset;
        let top = event.clientY + offset;

        if (left + width > window.innerWidth - 8) {
            left = event.clientX - width - offset;
        }
        if (top + height > window.innerHeight - 8) {
            top = event.clientY - height - offset;
        }

        ordersImagePopover.style.left = `${Math.max(8, left)}px`;
        ordersImagePopover.style.top = `${Math.max(8, top)}px`;
    };

    ordersImageTriggers.forEach((trigger) => {
        trigger.addEventListener('mouseenter', (event) => {
            const src = trigger.getAttribute('data-order-preview-src');
            if (!src || !ordersImagePopover || !ordersImagePopoverImg) return;
            ordersImagePopoverImg.src = src;
            ordersImagePopoverImg.alt = trigger.getAttribute('data-order-preview-alt') || 'Siparis gorseli';
            ordersImagePopover.classList.add('is-open');
            placeOrdersPopover(event);
        });

        trigger.addEventListener('mousemove', placeOrdersPopover);

        trigger.addEventListener('mouseleave', () => {
            ordersImagePopover?.classList.remove('is-open');
            if (ordersImagePopoverImg) {
                ordersImagePopoverImg.removeAttribute('src');
            }
        });
    });

</script>

@endsection











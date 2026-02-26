@extends('layouts.admin')

@section('header')
    Profit Engine Detay
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold">Siparis: {{ $snapshot->order?->order_number ?? ('#'.$snapshot->order_id) }}</h2>
            <p class="text-sm text-slate-500">{{ $snapshot->marketplace }} | Son hesaplama: {{ $snapshot->calculated_at?->format('d.m.Y H:i') }}</p>
        </div>
        <form method="POST" action="{{ route('portal.profit-engine.recalculate', $snapshot->order_id) }}">
            @csrf
            <button class="btn btn-solid-accent">Yeniden Hesapla</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="panel-card p-4">
            <div class="text-sm text-slate-500">Gross Revenue</div>
            <div class="text-2xl font-semibold">{{ number_format((float)$snapshot->gross_revenue, 2) }} TRY</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-sm text-slate-500">Net Profit</div>
            <div class="text-2xl font-semibold {{ (float)$snapshot->net_profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format((float)$snapshot->net_profit, 2) }} TRY</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-sm text-slate-500">Net Margin</div>
            <div class="text-2xl font-semibold {{ (float)$snapshot->net_margin >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format((float)$snapshot->net_margin, 2) }}%</div>
        </div>
    </div>

    <div class="panel-card p-4 overflow-auto">
        <h3 class="font-semibold mb-3">Cost Breakdown</h3>
        <table class="min-w-full text-sm">
            <tbody>
                <tr class="border-b"><td class="px-3 py-2">Urun Maliyeti</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->product_cost, 2) }}</td></tr>
                <tr class="border-b"><td class="px-3 py-2">Komisyon</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->commission_amount, 2) }}</td></tr>
                <tr class="border-b"><td class="px-3 py-2">Kargo</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->shipping_amount, 2) }}</td></tr>
                <tr class="border-b"><td class="px-3 py-2">Servis</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->service_amount, 2) }}</td></tr>
                <tr class="border-b"><td class="px-3 py-2">Kampanya</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->campaign_amount, 2) }}</td></tr>
                <tr class="border-b"><td class="px-3 py-2">Reklam</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->ad_amount, 2) }}</td></tr>
                <tr class="border-b"><td class="px-3 py-2">Paketleme</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->packaging_amount, 2) }}</td></tr>
                <tr class="border-b"><td class="px-3 py-2">Operasyon</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->operational_amount, 2) }}</td></tr>
                <tr class="border-b"><td class="px-3 py-2">Iade Riski</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->return_risk_amount, 2) }}</td></tr>
                <tr><td class="px-3 py-2">Diger</td><td class="px-3 py-2 text-right">{{ number_format((float)$snapshot->other_cost_amount, 2) }}</td></tr>
            </tbody>
        </table>
    </div>

    <div class="panel-card p-4">
        <h3 class="font-semibold mb-3">Meta Flags</h3>
        <div class="space-y-2 text-sm">
            <div><strong>rule_missing:</strong> {{ data_get($snapshot->meta, 'rule_missing') ? 'true' : 'false' }}</div>
            <div><strong>cost_missing_skus:</strong> {{ implode(', ', (array) data_get($snapshot->meta, 'cost_missing_skus', [])) ?: '-' }}</div>
        </div>
    </div>

    <div class="panel-card p-4">
        <h3 class="font-semibold mb-3">Item Breakdown</h3>
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left border-b text-slate-500">
                        <th class="px-3 py-2">Order Item</th>
                        <th class="px-3 py-2">SKU</th>
                        <th class="px-3 py-2">Qty</th>
                        <th class="px-3 py-2">Rule</th>
                        <th class="px-3 py-2">Cost Missing</th>
                    </tr>
                </thead>
                <tbody>
                @foreach((array) data_get($snapshot->meta, 'item_breakdowns', []) as $row)
                    <tr class="border-b border-slate-100">
                        <td class="px-3 py-2">{{ data_get($row, 'order_item_id') }}</td>
                        <td class="px-3 py-2">{{ data_get($row, 'sku') }}</td>
                        <td class="px-3 py-2">{{ data_get($row, 'qty') }}</td>
                        <td class="px-3 py-2">{{ data_get($row, 'rule_id') ?: '-' }}</td>
                        <td class="px-3 py-2">{{ data_get($row, 'cost_missing') ? 'yes' : 'no' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection


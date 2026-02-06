@extends('layouts.admin')



@section('header')

    Kargo Takip

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="space-y-6">

            <div class="flex items-center justify-between">

                <h2 class="text-lg font-semibold text-slate-800">Gönderiler</h2>

            </div>



            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="text-left text-slate-500">

                        <tr>

                            <th class="py-2 pr-4">Sipariş</th>

                            <th class="py-2 pr-4">Pazaryeri</th>

                            <th class="py-2 pr-4">Provider</th>

                            <th class="py-2 pr-4">Takip No</th>

                            <th class="py-2 pr-4">Durum</th>

                            <th class="py-2 pr-4">Son Event</th>

                            <th class="py-2 pr-4"></th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse($shipments as $shipment)

                            <tr>

                                <td class="py-3 pr-4 text-slate-800">

                                    {{ $shipment->order?->order_number ?? $shipment->order?->marketplace_order_id ?? '—' }}

                                </td>

                                <td class="py-3 pr-4 text-slate-600">

                                    {{ $shipment->order?->marketplace?->code ?? $shipment->marketplace_code ?? '—' }}

                                </td>

                                <td class="py-3 pr-4 text-slate-600">

                                    {{ $shipment->provider_key ?? '—' }}

                                </td>

                                <td class="py-3 pr-4 font-mono text-xs text-slate-700">

                                    {{ $shipment->tracking_number ?? '—' }}

                                </td>

                                <td class="py-3 pr-4">

                                    @php

                                        $status = $shipment->status;

                                        $badge = match ($status) {

                                            'delivered' => 'border-emerald-200 bg-emerald-50 text-emerald-700',

                                            'in_transit', 'created' => 'border-blue-200 bg-blue-50 text-blue-700',

                                            'returned', 'cancelled' => 'border-rose-200 bg-rose-50 text-rose-700',

                                            'unmapped_carrier', 'provider_not_installed', 'error' => 'border-amber-200 bg-amber-50 text-amber-700',

                                            default => 'border-slate-200 bg-slate-50 text-slate-700',

                                        };

                                    @endphp

                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs {{ $badge }}">

                                        {{ $status ?? 'pending' }}

                                    </span>

                                </td>

                                <td class="py-3 pr-4 text-slate-600">

                                    {{ $shipment->last_event_at?->format('d.m.Y H:i') ?? '—' }}

                                </td>

                                <td class="py-3 pr-4 text-right whitespace-nowrap">

                                    <a href="{{ route('portal.shipments.show', $shipment) }}" class="btn btn-outline">Detay</a>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7" class="py-6 text-center text-slate-500">Gönderi bulunamadı.</td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>



            {{ $shipments->links() }}

        </div>

    </div>

@endsection





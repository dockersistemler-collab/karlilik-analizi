@extends('layouts.admin')



@section('header')

    Kargo Detayı

@endsection



@section('content')

    <div class="space-y-6">

        <div class="panel-card p-6">

            <div class="flex flex-wrap items-center justify-between gap-4">

                <div>

                    <div class="text-sm text-slate-500">Sipariş</div>

                    <div class="text-lg font-semibold text-slate-800">

                        {{ $shipment->order?->order_number ?? $shipment->order?->marketplace_order_id ?? '—' }}

                    </div>

                </div>

                <form method="POST" action="{{ route('portal.shipments.poll', $shipment) }}">

                    @csrf

                    <button type="submit" class="btn btn-outline">Manuel Güncelle</button>

                </form>

            </div>

        </div>



        <div class="grid gap-6 lg:grid-cols-3">

            <div class="panel-card p-6 lg:col-span-2">

                <h3 class="text-base font-semibold text-slate-800 mb-4">Takip Akışı</h3>



                <div class="space-y-4">

                    @forelse($events as $event)

                        <div class="flex gap-4">

                            <div class="h-10 w-10 rounded-full border border-slate-200 bg-slate-50 flex items-center justify-center text-xs text-slate-600">

                                {{ strtoupper(substr($event->event_code ?? 'EV', 0, 2)) }}

                            </div>

                            <div class="flex-1">

                                <div class="flex items-center justify-between">

                                    <div class="font-medium text-slate-800">{{ $event->description   $event->event_code ?? 'Güncelleme' }}</div>

                                    <div class="text-xs text-slate-500">{{ $event->occurred_at?->format('d.m.Y H:i') ?? '—' }}</div>

                                </div>

                                @if($event->location)

                                    <div class="text-xs text-slate-500 mt-1">{{ $event->location }}</div>

                                @endif

                            </div>

                        </div>

                    @empty

                        <div class="text-sm text-slate-500">Henüz takip kaydı yok.</div>

                    @endforelse

                </div>

            </div>



            <div class="panel-card p-6 space-y-4">

                <div>

                    <div class="text-xs text-slate-500">Durum</div>

                    <div class="font-semibold text-slate-800">{{ $shipment->status }}</div>

                </div>

                <div>

                    <div class="text-xs text-slate-500">Provider</div>

                    <div class="font-medium text-slate-700">{{ $shipment->provider_key ?? '—' }}</div>

                </div>

                <div>

                    <div class="text-xs text-slate-500">Takip No</div>

                    <div class="font-mono text-sm text-slate-700">{{ $shipment->tracking_number ?? '—' }}</div>

                </div>

                <div>

                    <div class="text-xs text-slate-500">Son Event</div>

                    <div class="text-sm text-slate-700">{{ $shipment->last_event_at?->format('d.m.Y H:i') ?? '—' }}</div>

                </div>

                @if(in_array($shipment->status, ['unmapped_carrier', 'provider_not_installed'], true))

                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">

                        Kargo eşlemesi eksik. Ayarlar → Kargo Entegrasyonları'na gidin.

                    </div>

                @endif

            </div>

        </div>

    </div>

@endsection





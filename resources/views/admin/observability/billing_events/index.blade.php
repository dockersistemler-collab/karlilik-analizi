@extends('layouts.admin')



@section('header', 'Billing Events')



@section('content')

    <div class="panel-card p-4 mb-4 text-sm text-slate-600">

        Bu ekran; iyzico webhook, dunning denemeleri ve fatura gecisleri gibi odeme akislarini izlemek icin kullanilir.

        Correlation ID ile ayni akis icindeki eventleri takip edebilirsiniz.

    </div>

    <div class="panel-card p-4 mb-4">

        <form method="GET" action="{{ route('super-admin.observability.billing-events.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end md:flex-wrap">

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Tenant ID</label>

                <input type="text" name="tenant_id" value="{{ $tenantId }}" placeholder="123">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Type</label>

                <input type="text" name="type" value="{{ $type }}" placeholder="invoice.created">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Status</label>

                <input type="text" name="status" value="{{ $status }}" placeholder="success">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Provider</label>

                <input type="text" name="provider" value="{{ $provider }}" placeholder="iyzico">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Correlation ID</label>

                <input type="text" name="correlation_id" value="{{ $correlationId }}" placeholder="corr-...">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Invoice ID</label>

                <input type="text" name="invoice_id" value="{{ $invoiceId ?? '' }}" placeholder="456">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Search</label>

                <input type="text" name="search" value="{{ $search }}" placeholder="correlation_id / provider_ref">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Tarih Baslangic</label>

                <input type="date" name="date_from" value="{{ $dateFrom }}">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Tarih Bitis</label>

                <input type="date" name="date_to" value="{{ $dateTo }}">

            </div>

            <div class="flex items-end gap-2">

                <button type="submit" class="btn btn-solid-accent">Uygula</button>

                <a href="{{ route('super-admin.observability.billing-events.index') }}" class="btn btn-outline">Sifirla</a>

            </div>

        </form>

    </div>



    <div class="panel-card p-4">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr>

                        <th class="text-left">Zaman</th>

                        <th class="text-left">Tenant</th>

                        <th class="text-left">Type</th>

                        <th class="text-left">Status</th>

                        <th class="text-left">Tutar</th>

                        <th class="text-left">Provider</th>

                        <th class="text-left">Correlation</th>

                        <th class="text-left">Detay</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($events as $event)

                        @php

                            $amount = $event->amount !== null ? number_format((float) $event->amount, 2) : '-';

                            $currency = $event->currency ? strtoupper($event->currency) : '';

                            $statusLabel = $event->status ?? '-';

                            $badgeClass = match ($statusLabel) {

                                'success', 'succeeded' => 'badge badge-success',

                                'failed', 'error' => 'badge badge-danger',

                                'pending', 'scheduled', 'attempt' => 'badge badge-warning',

                                default => 'badge badge-muted',

                            };

                        @endphp

                        <tr>

                            <td class="text-slate-700">

                                {{ optional($event->created_at)->format('d.m.Y H:i') }}

                            </td>

                            <td>

                                <div class="font-semibold text-slate-800">{{ $event->tenant_name ?? '-' }}</div>

                                <div class="text-xs text-slate-500">{{ $event->tenant_email ?? 'ID: '.$event->tenant_id }}</div>

                            </td>

                            <td class="text-slate-700">{{ $event->type ?? '-' }}</td>

                            <td><span class="{{ $badgeClass }}">{{ $statusLabel }}</span></td>

                            <td class="text-slate-700">{{ trim($amount.' '.$currency) ?: '-' }}</td>

                            <td class="text-slate-700">{{ $event->provider ?? '-' }}</td>

                            <td class="text-slate-700">{{ \Illuminate\Support\Str::limit($event->correlation_id ?? '-', 32) }}</td>

                            <td>

                                <a class="btn btn-outline-accent" href="{{ route('super-admin.observability.billing-events.show', $event) }}">

                                    Detay

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="8" class="text-center text-slate-500 py-6">Kayit bulunamadi.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-4">

            {{ $events->links() }}

        </div>

    </div>

@endsection





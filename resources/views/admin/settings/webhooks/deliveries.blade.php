@extends('layouts.admin')



@section('header')

    Webhook Teslimatları

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-6xl mx-auto space-y-6">

            <div class="flex items-center justify-between">

                <div>

                    <div class="text-lg font-semibold text-slate-900">{{ $endpoint->name }}</div>

                    <div class="text-xs text-slate-500">{{ $endpoint->url }}</div>

                </div>

                <a href="{{ route('portal.webhooks.edit', $endpoint) }}" class="btn btn-outline">Endpoint</a>

            </div>



            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <form method="GET" action="{{ route('portal.webhooks.deliveries', $endpoint) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">

                    <div>

                        <label class="block text-xs font-medium text-slate-600">Status</label>

                        <select name="status" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">

                            @php

                                $status = (string) ($filters['status'] ?? '');

                            @endphp

                            <option value="" @selected($status === '')>Hepsi</option>

                            @foreach(['pending','retrying','success','failed','disabled'] as $s)

                                <option value="{{ $s }}" @selected($status === $s)>{{ $s }}</option>

                            @endforeach

                        </select>

                    </div>

                    <div>

                        <label class="block text-xs font-medium text-slate-600">Event</label>

                        <input name="event" value="{{ $filters['event'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-mono" placeholder="einvoice.issued" />

                    </div>

                    <div>

                        <label class="block text-xs font-medium text-slate-600">From</label>

                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" />

                    </div>

                    <div>

                        <label class="block text-xs font-medium text-slate-600">To</label>

                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" />

                    </div>

                    <div class="md:col-span-4 flex items-center justify-end gap-2">

                        <a href="{{ route('portal.webhooks.deliveries', $endpoint) }}" class="btn btn-outline">Sıfırla</a>

                        <button type="submit" class="btn btn-solid-accent">Filtrele</button>

                    </div>

                </form>



                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="text-left text-slate-500">

                            <tr>

                                <th class="py-2 pr-4">Durum</th>

                                <th class="py-2 pr-4">Event</th>

                                <th class="py-2 pr-4">HTTP</th>

                                <th class="py-2 pr-4">Süre</th>

                                <th class="py-2 pr-4">Deneme</th>

                                <th class="py-2 pr-4">Next Retry</th>

                                <th class="py-2 pr-4">Zaman</th>

                                <th class="py-2 pr-4"></th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($deliveries as $d)

                                <tr>

                                    <td class="py-3 pr-4">

                                        @php($st = (string) $d->status)

                                        @if($st === 'success')

                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">success</span>

                                        @elseif($st === 'retrying')

                                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-xs text-amber-700">retrying</span>

                                        @elseif($st === 'failed')

                                            <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs text-rose-700">failed</span>

                                        @elseif($st === 'disabled')

                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-700">disabled</span>

                                        @else

                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-700">pending</span>

                                        @endif

                                    </td>

                                    <td class="py-3 pr-4 font-mono text-xs text-slate-700">{{ $d->event }}</td>

                                    <td class="py-3 pr-4 text-slate-700">{{ $d->http_status ?? '-' }}</td>

                                    <td class="py-3 pr-4 text-slate-700">{{ $d->duration_ms ? ($d->duration_ms.'ms') : '-' }}</td>

                                    <td class="py-3 pr-4 text-slate-700">{{ ($d->attempt ?? 0) + 1 }}</td>

                                    <td class="py-3 pr-4 text-slate-700">{{ $d->next_retry_at?->format('d.m.Y H:i') ?? '-' }}</td>

                                    <td class="py-3 pr-4 text-slate-700">{{ $d->created_at?->format('d.m.Y H:i') ?? '-' }}</td>

                                    <td class="py-3 pr-4 text-right">

                                        <a href="{{ route('portal.webhooks.deliveries.show', $d) }}" class="btn btn-outline">Detay</a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8" class="py-6 text-center text-slate-500">Kayıt yok.</td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>



                <div class="mt-4">

                    {{ $deliveries->links() }}

                </div>

            </div>

        </div>

    </div>

@endsection





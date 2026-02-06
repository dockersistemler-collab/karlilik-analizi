@section('header', 'Incident Inbox')



@extends('layouts.admin')



@section('header', 'Incident Inbox')



@section('content')

    <div class="panel-card p-4 mb-4">

        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">

            <div>

                <div class="text-sm text-slate-600">Atanmamis ve acik incident'lari hizlica yonetin.</div>

            </div>

            <form method="GET" action="{{ route('portal.incidents.inbox') }}" class="flex items-center gap-2">

                <input type="hidden" name="filter" value="{{ $filter }}">

                <input type="text" name="q" value="{{ $search }}" placeholder="Baslik / pazaryeri ara" class="w-full md:w-64">

                <button type="submit" class="btn btn-outline-accent">Ara</button>

            </form>

        </div>

    </div>



    <div class="flex flex-wrap gap-2 mb-4">

        @php

            $pillQuery = fn ($value) => array_filter(['filter' => $value, 'q' => $search ?: null]);

        @endphp

        <a href="{{ route('portal.incidents.inbox', $pillQuery('unassigned')) }}"

           class="btn {{ $filter === 'unassigned' ? 'btn-solid-accent' : 'btn-outline-accent' }}">Unassigned</a>

        @if($incidentSlaEnabled)

            <a href="{{ route('portal.incidents.inbox', $pillQuery('sla_risk')) }}"

               class="btn {{ $filter === 'sla_risk' ? 'btn-solid-accent' : 'btn-outline-accent' }}">SLA Risk</a>

            <a href="{{ route('portal.incidents.inbox', $pillQuery('sla_breach')) }}"

               class="btn {{ $filter === 'sla_breach' ? 'btn-solid-accent' : 'btn-outline-accent' }}">SLA Breach</a>

        @endif

        <a href="{{ route('portal.incidents.inbox', $pillQuery('my')) }}"

           class="btn {{ $filter === 'my' ? 'btn-solid-accent' : 'btn-outline-accent' }}">My Incidents</a>

        <a href="{{ route('portal.incidents.inbox', $pillQuery('all_open')) }}"

           class="btn {{ $filter === 'all_open' ? 'btn-solid-accent' : 'btn-outline-accent' }}">All Open</a>

    </div>



    <div class="panel-card p-4">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr>

                        <th class="text-left">Incident</th>

                        <th class="text-left">Durum</th>

                        @if($incidentSlaEnabled)

                            <th class="text-left">SLA</th>

                        @endif

                        <th class="text-left">Owner</th>

                        <th class="text-left">Son Guncelleme</th>

                        <th class="text-left">Aksiyon</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($incidents as $incident)

                        <tr class="border-t border-slate-100">

                            <td class="py-2">

                                <div class="flex flex-col">

                                    <span class="text-sm font-semibold text-slate-800">{{ $incident->title }}</span>

                                    <span class="text-xs text-slate-500">{{ $incident->marketplace ?? '-' }}</span>

                                </div>

                            </td>

                            <td class="py-2">

                                <div class="flex flex-col gap-1">

                                    <span class="badge badge-muted">{{ $incident->severity }}</span>

                                    <span class="badge badge-muted">{{ $incident->status }}</span>

                                </div>

                            </td>

                            @if($incidentSlaEnabled)

                                <td class="py-2">

                                    @if($incident->isResolveBreached())

                                        <span class="badge badge-muted text-rose-700">SLA Breach</span>

                                    @elseif($incident->isAckBreached())

                                        <span class="badge badge-muted text-amber-700">SLA Risk</span>

                                    @else

                                        <span class="text-xs text-slate-400">-</span>

                                    @endif

                                </td>

                            @endif

                            <td class="py-2">

                                {{ $incident->assignedUser?->name ?? 'Unassigned' }}

                            </td>

                            <td class="py-2 text-xs text-slate-500">

                                {{ optional($incident->last_seen_at)->diffForHumans() }}

                            </td>

                            <td class="py-2">

                                <div class="flex flex-wrap items-center gap-2">

                                    <form method="POST" action="{{ route('portal.incidents.assign_to_me', $incident) }}">

                                        @csrf

                                        <button type="submit"

                                                class="btn btn-outline-accent"

                                                @if($supportViewEnabled) disabled title="Support View'de islem yapilamaz" @endif>

                                            Assign to me

                                        </button>

                                    </form>

                                    <form method="POST" action="{{ route('portal.incidents.quick_ack', $incident) }}">

                                        @csrf

                                        <button type="submit"

                                                class="btn btn-outline-accent"

                                                @if($supportViewEnabled) disabled title="Support View'de islem yapilamaz" @endif>

                                            Quick ACK

                                        </button>

                                    </form>

                                    <a href="{{ route('portal.incidents.show', $incident) }}" class="btn btn-solid-accent">View</a>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="py-8 text-center text-slate-500">

                                Inbox temiz. <a href="{{ route('portal.incidents.inbox', ['filter' => 'all_open']) }}" class="text-blue-600 hover:underline">Tum acik olaylar</a>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>



        <div class="mt-4">

            {{ $incidents->links() }}

        </div>

    </div>

@endsection





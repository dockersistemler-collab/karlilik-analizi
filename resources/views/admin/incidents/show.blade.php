@extends('layouts.admin')



@section('header', 'Incident Detayi')



@section('content')

    @php

        $mttaSeconds = $incident->mttaSeconds();

        $mttrSeconds = $incident->mttrSeconds();

        $mttaLabel = $mttaSeconds ? \Carbon\CarbonInterval::seconds($mttaSeconds)->cascade()->forHumans() : 'Yok';

        $mttrLabel = $mttrSeconds ? \Carbon\CarbonInterval::seconds($mttrSeconds)->cascade()->forHumans() : 'Yok';

    @endphp

    <div class="panel-card p-4 mb-4">

        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

            <div>

                <div class="text-lg font-semibold text-slate-800">{{ $incident->title }}</div>

                <div class="text-xs text-slate-500">Key: {{ $incident->key }}</div>

            </div>

            <div class="flex gap-2">

                <span class="badge badge-muted">{{ $incident->severity }}</span>

                <span class="badge badge-muted">{{ $incident->status }}</span>

            </div>

        </div>

        <div class="mt-3 text-xs text-slate-600 grid grid-cols-1 md:grid-cols-3 gap-3">

            <div>First seen: {{ optional($incident->first_seen_at)->format('d.m.Y H:i') }}</div>

            <div>Last seen: {{ optional($incident->last_seen_at)->format('d.m.Y H:i') }}</div>

            <div>Resolved: {{ optional($incident->resolved_at)->format('d.m.Y H:i') ?? 'Yok' }}</div>

            @if($incidentSlaEnabled)

                <div>MTTA: {{ $mttaLabel }}</div>

                <div>MTTR: {{ $mttrLabel }}</div>

            @endif

            <div>Owner: {{ $incident->assignedUser?->name ?? 'Unassigned' }}</div>

        </div>

    </div>



    <div class="panel-card p-4 mb-4">

        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

            <div class="text-sm text-slate-600">Islemler</div>

            <div class="flex flex-wrap gap-2 items-center">

                <form method="POST" action="{{ route('portal.incidents.assign', $incident) }}" class="flex items-center gap-2">

                    @csrf

                    <select name="assigned_to_user_id" {{ $supportViewEnabled ? 'disabled' : '' }}>

                        <option value="">Unassigned</option>

                        @foreach($assignableUsers as $assignable)

                            <option value="{{ $assignable->id }}" {{ (string) $incident->assigned_to_user_id === (string) $assignable->id ? 'selected' : '' }}>

                                {{ $assignable->name }}

                            </option>

                        @endforeach

                    </select>

                    <button type="submit" class="btn btn-outline-accent" {{ $supportViewEnabled ? 'disabled' : '' }}>Kaydet</button>

                </form>

                @if(!$supportViewEnabled)

                    <form method="POST" action="{{ route('portal.incidents.ack', $incident) }}">

                        @csrf

                        <button type="submit" class="btn btn-outline-accent">ACK</button>

                    </form>

                    <form method="POST" action="{{ route('portal.incidents.resolve', $incident) }}">

                        @csrf

                        <input type="hidden" name="reason" value="manual">

                        <button type="submit" class="btn btn-solid-accent">RESOLVE</button>

                    </form>

                @else

                    <button type="button" class="btn btn-outline-accent" disabled>ACK</button>

                    <button type="button" class="btn btn-solid-accent" disabled>RESOLVE</button>

                @endif

            </div>

        </div>

    </div>



    <div class="panel-card p-4 mb-4">

        <div class="text-sm font-semibold text-slate-700 mb-3">Olay Gecmisi</div>

        <div class="space-y-3">

            @forelse($events as $event)

                <div class="border border-slate-100 rounded-md p-3">

                    <div class="text-xs text-slate-500">{{ optional($event->created_at)->format('d.m.Y H:i') }}</div>

                    <div class="text-sm font-semibold text-slate-800">{{ $event->type }}</div>

                    <div class="text-xs text-slate-600">{{ $event->message }}</div>

                </div>

            @empty

                <div class="text-sm text-slate-500">Event bulunamadi.</div>

            @endforelse

        </div>

    </div>



    <div class="panel-card p-4">

        <div class="text-sm font-semibold text-slate-700 mb-3">Ilgili Bildirimler</div>

        <div class="space-y-3">

            @forelse($notifications as $notification)

                <div class="border border-slate-100 rounded-md p-3">

                    <div class="text-xs text-slate-500">{{ optional($notification->created_at)->format('d.m.Y H:i') }}</div>

                    <div class="text-sm font-semibold text-slate-800">{{ $notification->title }}</div>

                    <div class="text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($notification->body, 160) }}</div>

                </div>

            @empty

                <div class="text-sm text-slate-500">Bildirim bulunamadi.</div>

            @endforelse

        </div>

    </div>

@endsection





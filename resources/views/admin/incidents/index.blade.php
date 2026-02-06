@extends('layouts.admin')



@section('header', 'Incidentler')



@section('content')

    <div class="panel-card p-4 mb-4">

        <form method="GET" action="{{ route('portal.incidents.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Durum</label>

                <select name="status">

                    <option value="">Tumu</option>

                    <option value="open" {{ ($filters['status'] ?? '') === 'open' ? 'selected' : '' }}>open</option>

                    <option value="acknowledged" {{ ($filters['status'] ?? '') === 'acknowledged' ? 'selected' : '' }}>acknowledged</option>

                    <option value="resolved" {{ ($filters['status'] ?? '') === 'resolved' ? 'selected' : '' }}>resolved</option>

                </select>

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Pazaryeri</label>

                <select name="marketplace">

                    <option value="">Tumu</option>

                    @foreach($marketplaces as $marketplace)

                        <option value="{{ $marketplace }}" {{ ($filters['marketplace'] ?? '') === $marketplace ? 'selected' : '' }}>

                            {{ $marketplace }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="flex items-end">

                <label class="inline-flex items-center gap-2 text-xs text-slate-600">

                    <input type="checkbox" name="unassigned" value="1" {{ ($filters['unassigned'] ?? false) ? 'checked' : '' }}>

                    Unassigned

                </label>

            </div>

            <div class="flex items-end gap-2">

                <button type="submit" class="btn btn-solid-accent">Uygula</button>

                <a href="{{ route('portal.incidents.index') }}" class="btn btn-outline-accent">Sifirla</a>

            </div>

        </form>

    </div>



    <div class="panel-card p-4">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr>

                        <th class="text-left">Baslik</th>

                        <th class="text-left">Pazaryeri</th>

                        <th class="text-left">Owner</th>

                        <th class="text-left">Severity</th>

                        <th class="text-left">Durum</th>

                        @if($incidentSlaEnabled)

                            <th class="text-left">SLA</th>

                        @endif

                        <th class="text-left">First Seen</th>

                        <th class="text-left">Last Seen</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($incidents as $incident)

                        <tr class="border-t border-slate-100">

                            <td class="py-2">

                                <a href="{{ route('portal.incidents.show', $incident) }}" class="text-slate-800 font-semibold hover:underline">

                                    {{ $incident->title }}

                                </a>

                            </td>

                            <td class="py-2">{{ $incident->marketplace ?? '-' }}</td>

                            <td class="py-2">

                                @if($incident->assignedUser)

                                    {{ $incident->assignedUser->name }}

                                @else

                                    <span class="badge badge-muted">Unassigned</span>

                                @endif

                            </td>

                            <td class="py-2">

                                <span class="badge badge-muted">{{ $incident->severity }}</span>

                            </td>

                            <td class="py-2">

                                <span class="badge badge-muted">{{ $incident->status }}</span>

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

                            <td class="py-2">{{ optional($incident->first_seen_at)->format('d.m.Y H:i') }}</td>

                            <td class="py-2">{{ optional($incident->last_seen_at)->format('d.m.Y H:i') }}</td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="8" class="py-6 text-center text-slate-500">Kayit bulunamadi.</td>

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





@extends('layouts.admin')



@section('header', 'Bildirim Tercihleri')



@section('content')

    <div class="panel-card p-4 mb-4">

        <form method="GET" action="{{ route('portal.notification-hub.preferences.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Pazaryeri</label>

                <select name="marketplace">

                    <option value="">Tümü</option>

                    @foreach($marketplaces as $code => $name)

                        <option value="{{ $code }}" {{ request('marketplace') === $code ? 'selected' : '' }}>{{ $name }}</option>

                    @endforeach

                </select>

            </div>

            <div class="flex items-end gap-2">

                <button type="submit" class="btn btn-outline-accent">Seç</button>

            </div>

        </form>

    </div>



@php

    $marketplaceKey = request('marketplace') ?: 'all';

    $currentPrefs = $preferences->get($marketplaceKey, collect());

    $quietHours = $currentPrefs->first()?->quiet_hours ?? [];

@endphp



    <div class="panel-card p-4">

        <form method="POST" action="{{ route('portal.notification-hub.preferences.update') }}" class="space-y-6">

            @csrf

            @method('PUT')

            <input type="hidden" name="marketplace" value="{{ request('marketplace') }}">



            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead>

                        <tr>

                            <th class="text-left">Tür</th>

                            @foreach($channels as $channel)

                                <th class="text-left">{{ $channel }}</th>

                            @endforeach

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($types as $type)

                            <tr>

                                <td class="font-semibold text-slate-700">{{ $type }}</td>

                                @foreach($channels as $channel)

                                    @php

                                        $pref = $currentPrefs->firstWhere(fn ($item) => $item->type === $type && $item->channel === $channel);

                                        $defaultEnabled = match ($type) {

                                            'critical' => true,

                                            'operational' => $channel === 'in_app',

                                            'info' => false,

                                            default => false,

                                        };

                                        $checked = $pref ? (bool) $pref->enabled : $defaultEnabled;

                                    @endphp

                                    <td>

                                        <label class="inline-flex items-center gap-2">

                                            <input type="checkbox" name="preferences[{{ $type }}][{{ $channel }}]" value="1" {{ $checked ? 'checked' : '' }}>

                                            <span class="text-slate-600">Aktif</span>

                                        </label>

                                    </td>

                                @endforeach

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>



            <div class="border-t border-slate-200 pt-4">

                <div class="text-sm font-semibold text-slate-700 mb-2">Sessiz Saatler</div>

                <div class="flex flex-col gap-3 md:flex-row md:items-end">

                    <div class="flex flex-col gap-2">

                        <label class="text-xs font-semibold text-slate-500">Başlangıç</label>

                        <input type="time" name="quiet_start" value="{{ old('quiet_start', $quietHours['start'] ?? '') }}">

                    </div>

                    <div class="flex flex-col gap-2">

                        <label class="text-xs font-semibold text-slate-500">Bitiş</label>

                        <input type="time" name="quiet_end" value="{{ old('quiet_end', $quietHours['end'] ?? '') }}">

                    </div>

                    <div class="flex flex-col gap-2">

                        <label class="text-xs font-semibold text-slate-500">Zaman Dilimi</label>

                        <input type="text" name="quiet_tz" value="{{ old('quiet_tz', $quietHours['tz'] ?? 'Europe/Istanbul') }}">

                    </div>

                </div>

            </div>



            <div>

                <button type="submit" class="btn btn-solid-accent">Kaydet</button>

            </div>

        </form>

    </div>

@endsection





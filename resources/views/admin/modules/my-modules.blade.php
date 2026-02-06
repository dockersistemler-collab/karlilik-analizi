@extends('layouts.admin')



@section('header')

    Modüllerim

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-5xl mx-auto">

            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <div class="text-xl font-semibold text-slate-900">Modüllerim</div>

                        <div class="text-sm text-slate-500 mt-1">Modül bitiş tarihlerini ve kalan günleri buradan takip edebilirsiniz.</div>

                    </div>

                    <a href="{{ route('portal.addons.index') }}" class="btn btn-outline">Ek Modüller</a>

                </div>



                <div class="mt-6 overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="text-left text-slate-500">

                            <tr>

                                <th class="py-2 pr-4">Modül</th>

                                <th class="py-2 pr-4">Durum</th>

                                <th class="py-2 pr-4">Bitiş</th>

                                <th class="py-2 pr-4">Kalan</th>

                                <th class="py-2 pr-4"></th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($userModules as $um)

                                @php

                                    $module = $um->module;

                                    $daysLeft = $um->days_left;

                                    $endsAt = $um->ends_at_local;

                                    $statusLabel = $um->status;

                                    if ($endsAt && is_int($daysLeft)) {

                                        $statusLabel = $daysLeft < 0 ? 'expired' : $um->status;

                                    }

                                @endphp

                                <tr>

                                    <td class="py-3 pr-4">

                                        <div class="font-semibold text-slate-900">{{ $module?->name ?? '-' }}</div>

                                        <div class="text-xs text-slate-500 font-mono">{{ $module?->code ?? '' }}</div>

                                    </td>

                                    <td class="py-3 pr-4">

                                        @php

                                            $badge = match ($statusLabel) {

                                                'active' => 'bg-emerald-50 text-emerald-700 border-emerald-200',

                                                'inactive' => 'bg-slate-50 text-slate-700 border-slate-200',

                                                'expired' => 'bg-rose-50 text-rose-700 border-rose-200',

                                                default => 'bg-slate-50 text-slate-700 border-slate-200',

                                            };

                                        @endphp

                                        <span class="inline-flex items-center px-2 py-1 rounded-lg border {{ $badge }}">

                                            @php

                                                $statusText = match ($statusLabel) {

                                                    'active' => 'Aktif',

                                                    'inactive' => 'Pasif',

                                                    'expired' => 'Süresi Doldu',

                                                    default => $statusLabel,

                                                };

                                            @endphp

                                            {{ $statusText }}

                                        </span>

                                    </td>

                                    <td class="py-3 pr-4 text-slate-700">

                                        {{ $endsAt ? $endsAt->format('d.m.Y') : 'Süresiz' }}

                                    </td>

                                    <td class="py-3 pr-4 text-slate-700">

                                        @if(is_int($daysLeft))

                                            @if($daysLeft > 0)

                                                {{ $daysLeft }} gün

                                            @elseif($daysLeft === 0)

                                                Bugün

                                            @else

                                                {{ abs($daysLeft) }} gün geçti

                                            @endif

                                        @else

                                            -

                                        @endif

                                    </td>

                                    <td class="py-3 pr-4">

                                        @if($module?->code)

                                            <div class="flex flex-wrap gap-2 items-center justify-end">

                                                <form method="POST" action="{{ route('portal.my-modules.renew', $module) }}" class="flex items-center gap-2">

                                                    @csrf

                                                    <select name="period" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-sm">

                                                        <option value="monthly">Aylık</option>

                                                        <option value="yearly">Yıllık</option>

                                                    </select>

                                                    <button type="submit" class="btn btn-solid-accent">

                                                        {{ ($daysLeft !== null && is_int($daysLeft) && $daysLeft < 0) ? 'Tekrar Aktif Et' : 'Yenile' }}

                                                    </button>

                                                </form>

                                                <a href="{{ route('portal.modules.upsell', ['code' => $module->code]) }}" class="btn btn-outline">

                                                    Detay

                                                </a>

                                            </div>

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="5" class="py-8 text-center text-slate-500">

                                        Henüz bir modülünüz yok.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

@endsection





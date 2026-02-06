@extends('layouts.super-admin')



@section('header', 'Support View Oturumları')



@section('content')

    <div class="panel-card p-4 mb-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">

            <h3 class="text-sm font-semibold text-slate-800">Filtreler</h3>

        </div>

        <form method="GET" action="{{ route('super-admin.support-view-sessions.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Durum</label>

                <select name="status">

                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Aktif</option>

                    <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Süresi Doldu</option>

                    <option value="ended" {{ $status === 'ended' ? 'selected' : '' }}>Kapatıldı</option>

                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Tümü</option>

                </select>

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Kaynak</label>

                <select name="source_type">

                    <option value="">Tümü</option>

                    <option value="manual" {{ $sourceType === 'manual' ? 'selected' : '' }}>Manual</option>

                    <option value="ticket" {{ $sourceType === 'ticket' ? 'selected' : '' }}>Ticket</option>

                </select>

            </div>

            <div class="flex-1 flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Ara</label>

                <input type="text" name="q" value="{{ $search }}" placeholder="Actor/Target e-posta veya Ticket #">

            </div>

            <div>

                <button type="submit" class="btn btn-solid-accent">Uygula</button>

            </div>

            <div>

                <a href="{{ route('super-admin.support-view-sessions.index') }}" class="text-slate-500 hover:text-slate-700">Sıfırla</a>

            </div>

        </form>

    </div>



    <div class="panel-card p-0">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr>

                        <th class="text-left">Actor</th>

                        <th class="text-left">Target</th>

                        <th class="text-left">Kaynak</th>

                        <th class="text-left">Ticket #</th>

                        <th class="text-left">Başlangıç</th>

                        <th class="text-left">Bitiş</th>

                        <th class="text-left">Kalan</th>

                        <th class="text-left">Reason</th>

                        <th class="text-left">IP</th>

                        <th class="text-left">User Agent</th>

                        <th class="text-left">Aksiyon</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($logs as $log)

                        @php

                            $actorName = $log->actor?->name ?? '-';

                            $actorEmail = $log->actor?->email ?? '';

                            $targetName = $log->targetUser?->name ?? '-';

                            $targetEmail = $log->targetUser?->email ?? '';

                            $expiresAt = $log->expires_at;

                            $remaining = null;

                            if ($log->ended_at) {

                                $remaining = 'Kapatıldı';

                            } elseif (!$expiresAt) {

                                $remaining = 'Süre yok';

                            } elseif ($expiresAt && $expiresAt->isPast()) {

                                $remaining = 'Süresi doldu';

                            } elseif ($expiresAt) {

                                $remaining = $expiresAt->diffForHumans(null, true).' kaldı';

                            }

                        @endphp

                        <tr>

                            <td>

                                <div class="font-semibold text-slate-800">{{ $actorName }}</div>

                                <div class="text-xs text-slate-500">{{ $actorEmail }}</div>

                            </td>

                            <td>

                                <div class="font-semibold text-slate-800">{{ $targetName }}</div>

                                <div class="text-xs text-slate-500">{{ $targetEmail }}</div>

                            </td>

                            <td class="uppercase text-xs text-slate-600">{{ $log->source_type ?? '-' }}</td>

                            <td class="text-slate-700">

                                {{ $log->source_type === 'ticket' ? ($log->source_id ?? '-') : '-' }}

                            </td>

                            <td class="text-slate-700">{{ optional($log->started_at)->format('d.m.Y H:i') }}</td>

                            <td class="text-slate-700">

                                {{ $expiresAt ? $expiresAt->format('d.m.Y H:i') : 'Süre yok' }}

                            </td>

                            <td class="text-slate-700">{{ $remaining ?? '-' }}</td>

                            <td class="text-slate-700">{{ \Illuminate\Support\Str::limit($log->reason ?? '-', 40) }}</td>

                            <td class="text-slate-700">{{ \Illuminate\Support\Str::limit($log->ip ?? '-', 18) }}</td>

                            <td class="text-slate-700">{{ \Illuminate\Support\Str::limit($log->user_agent ?? '-', 28) }}</td>

                            <td>

                                @if($log->ended_at)

                                    <span class="text-xs font-semibold text-slate-500">Kapatıldı</span>

                                @elseif($expiresAt && $expiresAt->isPast())

                                    <span class="text-xs font-semibold text-amber-600">Süresi doldu</span>

                                @elseif(!$expiresAt)

                                    <span class="text-xs font-semibold text-slate-500">Süre yok</span>

                                @else

                                    <form method="POST" action="{{ route('super-admin.support-view-sessions.end', $log) }}">

                                        @csrf

                                        <button type="submit" class="btn btn-outline-accent" onclick="return confirm('Oturumu kapatmak istiyor musunuz?')">

                                            Oturumu Kapat

                                        </button>

                                    </form>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="11" class="text-center text-slate-500 py-6">Kayit bulunamadi.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-4">

            {{ $logs->links() }}

        </div>

    </div>

@endsection








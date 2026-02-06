@extends('layouts.super-admin')



@section('header', 'Bildirimler')



@section('content')

    <div class="panel-card p-4 mb-4">

        <form method="GET" action="{{ route('super-admin.notification-hub.notifications.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Tenant</label>

                <select name="user_id">

                    <option value="">Kendi hesabım</option>

                    @foreach($tenants as $tenant)

                        <option value="{{ $tenant->id }}" {{ (string) request('user_id') === (string) $tenant->id ? 'selected' : '' }}>

                            {{ $tenant->name }} ({{ $tenant->email }})

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Tür</label>

                <select name="type">

                    <option value="">Tümü</option>

                    <option value="critical" {{ request('type') === 'critical' ? 'selected' : '' }}>critical</option>

                    <option value="operational" {{ request('type') === 'operational' ? 'selected' : '' }}>operational</option>

                    <option value="info" {{ request('type') === 'info' ? 'selected' : '' }}>info</option>

                </select>

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Pazaryeri</label>

                <select name="marketplace">

                    <option value="">Tümü</option>

                    @foreach($marketplaces as $code => $name)

                        <option value="{{ $code }}" {{ request('marketplace') === $code ? 'selected' : '' }}>{{ $name }}</option>

                    @endforeach

                </select>

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Durum</label>

                <select name="read">

                    <option value="">Tümü</option>

                    <option value="unread" {{ request('read') === 'unread' ? 'selected' : '' }}>Okunmayan</option>

                    <option value="read" {{ request('read') === 'read' ? 'selected' : '' }}>Okunan</option>

                </select>

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Başlangıç</label>

                <input type="date" name="from" value="{{ request('from', $defaultFrom ?? '') }}">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Bitiş</label>

                <input type="date" name="to" value="{{ request('to', $defaultTo ?? '') }}">

            </div>

            <div class="flex items-end gap-2">

                <button type="submit" class="btn btn-solid-accent">Uygula</button>

                <a href="{{ route('super-admin.notification-hub.notifications.index') }}" class="btn btn-outline-accent">Sıfırla</a>

            </div>

        </form>

    </div>



    <div class="panel-card p-4">

        <div class="space-y-3">

            @forelse($notifications as $notification)

                @include('admin.notification-hub.partials._item', ['notification' => $notification])

            @empty

                <div class="text-sm text-slate-500 text-center py-6">

                    Henüz bildirim yok.

                </div>

            @endforelse

        </div>



        <div class="mt-4">

            {{ $notifications->links() }}

        </div>

    </div>

@endsection


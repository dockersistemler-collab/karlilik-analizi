@extends('layouts.admin')



@section('header', 'Bildirimler')



@section('content')

    <div class="panel-card p-4 mb-4">

        @include('admin.notification-hub.partials._filters', [

            'marketplaces' => $marketplaces,

            'defaultFrom' => $defaultFrom ?? null,

            'defaultTo' => $defaultTo ?? null,

        ])

    </div>



    <div class="panel-card p-4">

        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">

            <div>

                <a href="{{ route('portal.notification-hub.preferences.index') }}" class="btn btn-outline-accent">

                    Bildirim Tercihleri

                </a>

                <a href="{{ route('portal.notification-hub.suppressions.index') }}" class="btn btn-outline-accent ml-2">

                    Email Suppression

                </a>

            </div>

            <form method="POST" action="{{ route('portal.notification-hub.notifications.read-all') }}">

                @csrf

                <button type="submit" class="btn btn-solid-accent">Tümünü Okundu İşaretle</button>

            </form>

        </div>



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





@extends('layouts.super-admin')



@section('header')

    Ticketlar

@endsection



@section('content')

    <div class="panel-card p-4 mb-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">

            <h3 class="text-sm font-semibold text-slate-800">Filtreler</h3>

        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">

            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Konu ara..." class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">

            <select name="status" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">

                <option value="">Tüm Durumlar</option>

                <option value="open" @selected(($filters['status'] ?? '') === 'open')>Açık</option>

                <option value="waiting_admin" @selected(($filters['status'] ?? '') === 'waiting_admin')>Destek Bekleniyor</option>

                <option value="waiting_customer" @selected(($filters['status'] ?? '') === 'waiting_customer')>Müşteri Bekleniyor</option>

                <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>Çözüldü</option>

                <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Kapatıldı</option>

            </select>

            <select name="assigned_to_id" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">

                <option value="">Atanan</option>

                @foreach($admins as $admin)

                    <option value="{{ $admin->id }}" @selected(($filters['assigned_to_id'] ?? '') == $admin->id)>

                        {{ $admin->name }}

                    </option>

                @endforeach

            </select>

            <input type="number" name="customer_id" value="{{ $filters['customer_id'] ?? '' }}" placeholder="Müşteri ID" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">

            <div class="flex items-center gap-3">

                <button class="btn btn-solid-accent">Filtrele</button>

                <a href="{{ route('super-admin.tickets.index') }}" class="text-slate-500 hover:text-slate-700">Sıfırla</a>

            </div>

        </form>

    </div>



    @if($errors->any())

        <div class="panel-card p-4 mb-6 border border-red-200 text-red-700">

            <ul class="list-disc list-inside text-sm">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif



    <div class="panel-card p-0 overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">

                <tr>

                    <th class="px-4 py-3 text-left">Konu</th>

                    <th class="px-4 py-3 text-left">Müşteri</th>

                    <th class="px-4 py-3 text-left">Durum</th>

                    <th class="px-4 py-3 text-left">Atanan</th>

                    <th class="px-4 py-3 text-left">Son Aktivite</th>

                    <th class="px-4 py-3"></th>

                </tr>

            </thead>

            <tbody class="divide-y">

                @forelse($tickets as $ticket)

                    @php

                        $statusClasses = [

                            'open' => 'bg-slate-100 text-slate-600',

                            'waiting_admin' => 'bg-slate-200 text-slate-700',

                            'waiting_customer' => 'bg-slate-200 text-slate-700',

                            'resolved' => 'bg-slate-300 text-slate-800',

                            'closed' => 'bg-slate-300 text-slate-800',

                        ];

                    @endphp

                    <tr>

                        <td class="px-4 py-3 font-medium text-slate-900">{{ $ticket->subject }}</td>

                        <td class="px-4 py-3">{{ $ticket->customer?->name ?? '-' }}</td>

                        <td class="px-4 py-3">

                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$ticket->status] ?? 'bg-slate-100 text-slate-700' }}">

                                {{ str_replace('_', ' ', $ticket->status) }}

                            </span>

                        </td>

                        <td class="px-4 py-3">{{ $ticket->assignedTo?->name ?? '-' }}</td>

                        <td class="px-4 py-3">{{ optional($ticket->last_activity_at)->diffForHumans() }}</td>

                        <td class="px-4 py-3 text-right">

                            <a href="{{ route('super-admin.tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-900">Görüntüle</a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Ticket bulunamadı.</td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>



    <div class="mt-4">

        {{ $tickets->links() }}

    </div>

@endsection













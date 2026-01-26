@extends('layouts.admin')

@section('header')
    Destek Merkezi
@endsection

@section('content')
    <div class="panel-card p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Destek Merkezi</p>
                <h3 class="text-2xl font-semibold text-slate-900 mt-2">Sorunlar?n? h?zl?ca ??zelim.</h3>
                <p class="text-sm text-slate-500 mt-2 max-w-2xl">
                    Destek taleplerini buradan y?netebilir, ekibimizle do?rudan ileti?ime ge?ebilirsin.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('admin.tickets.create') }}" class="btn btn-solid-accent">
                    Destek Talebi Olu?tur
                </a>
                <a href="{{ route('admin.help.support') }}" class="btn btn-outline-accent">
                    Destek Merkezi Rehberi
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
            <div class="panel-card p-4 border-dashed border-slate-200">
                <p class="text-xs uppercase text-slate-400">1. Talep Olu?tur</p>
                <p class="text-sm text-slate-600 mt-2">Konu, ?ncelik ve mesaj?n? yaz. Gerekirse dosya ekle.</p>
            </div>
            <div class="panel-card p-4 border-dashed border-slate-200">
                <p class="text-xs uppercase text-slate-400">2. Takip Et</p>
                <p class="text-sm text-slate-600 mt-2">Yan?tlar? tek bir ekrandan takip et ve geri bildirim ver.</p>
            </div>
            <div class="panel-card p-4 border-dashed border-slate-200">
                <p class="text-xs uppercase text-slate-400">3. ??z?m</p>
                <p class="text-sm text-slate-600 mt-2">Sorun ??z?ld???nde talep kapan?r, ge?mi?te saklan?r.</p>
            </div>
        </div>
    </div>

    <div class="panel-card p-6 mb-6">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="text-sm font-semibold text-slate-800">Taleplerim</h3>
            <a href="{{ route('admin.tickets.create') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">Yeni Talep</a>
        </div>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Konu ara..." class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-full text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
            <select name="status" class="w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-full text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                <option value="">T?m?</option>
                <option value="open" @selected(($filters['status'] ?? '') === 'open')>A??k</option>
                <option value="waiting_admin" @selected(($filters['status'] ?? '') === 'waiting_admin')>Destek Bekleniyor</option>
                <option value="waiting_customer" @selected(($filters['status'] ?? '') === 'waiting_customer')>M??teri Bekleniyor</option>
                <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>??z?ld?</option>
                <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Kapat?ld?</option>
            </select>
            <button class="btn btn-solid-accent">Filtrele</button>
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-accent">Temizle</a>
        </form>
    </div>

    @if($errors->any())
        <div class="panel-card p-4 mb-6 border-red-200 text-red-700">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="panel-card overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Konu</th>
                    <th class="px-4 py-3 text-left">?ncelik</th>
                    <th class="px-4 py-3 text-left">Durum</th>
                    <th class="px-4 py-3 text-left">Son Aktivite</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($tickets as $ticket)
                    @php
                        $priorityClasses = [
                            'low' => 'bg-slate-100 text-slate-600',
                            'medium' => 'bg-slate-200 text-slate-700',
                            'high' => 'bg-slate-300 text-slate-800',
                            'urgent' => 'bg-slate-400 text-slate-900',
                        ];
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
                        <td class="px-4 py-3">
                            <span class="panel-pill text-xs font-semibold {{ $priorityClasses[$ticket->priority] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="panel-pill text-xs font-semibold {{ $statusClasses[$ticket->status] ?? 'bg-slate-100 text-slate-700' }}">
                                {{ str_replace('_', ' ', $ticket->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ optional($ticket->last_activity_at)->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-800">G?r?nt?le</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Hen?z destek talebi yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
@endsection

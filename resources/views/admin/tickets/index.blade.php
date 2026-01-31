@extends('layouts.admin')

@section('header')
    Destek Merkezi
@endsection

@section('content')
    <div class="panel-card p-6 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-rose-50 via-white to-sky-50"></div>
        <div class="absolute -top-20 -left-28 h-72 w-72 rounded-full bg-rose-200/40 blur-3xl"></div>
        <div class="absolute -bottom-24 -right-24 h-80 w-80 rounded-full bg-sky-200/35 blur-3xl"></div>
        <div class="absolute top-10 right-12 h-14 w-14 rotate-12 rounded-2xl bg-amber-200/35 blur-sm"></div>
        <div class="absolute bottom-10 left-16 h-10 w-10 -rotate-12 rounded-full bg-emerald-200/35 blur-sm"></div>

        <div class="relative">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Destek</p>
                    <h3 class="text-2xl font-semibold text-slate-900 mt-2">Sorunlarını hızlıca çözelim.</h3>
                    <p class="text-sm text-slate-600 mt-2 max-w-2xl">
                        Taleplerini buradan takip edebilir, yanıtları tek bir akışta görebilirsin.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('admin.tickets.create') }}" class="btn btn-solid-accent">
                        <i class="fa-regular fa-life-ring"></i>
                        Destek Talebi Oluştur
                    </a>
                    <a href="{{ route('admin.help.support') }}" class="btn btn-outline-accent">
                        <i class="fa-regular fa-circle-question"></i>
                        Rehberi Aç
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                <div class="rounded-xl border border-rose-200/70 bg-rose-50/60 p-4">
                    <p class="text-xs uppercase tracking-[0.22em] text-slate-500">1. Talep Oluştur</p>
                    <p class="text-sm text-slate-700 mt-2">Konu, öncelik ve mesajını yaz. Gerekirse dosya ekle.</p>
                </div>
                <div class="rounded-xl border border-sky-200/70 bg-sky-50/60 p-4">
                    <p class="text-xs uppercase tracking-[0.22em] text-slate-500">2. Takip Et</p>
                    <p class="text-sm text-slate-700 mt-2">Yanıtları tek ekrandan takip et ve geri bildirim ver.</p>
                </div>
                <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/60 p-4">
                    <p class="text-xs uppercase tracking-[0.22em] text-slate-500">3. Çözüm</p>
                    <p class="text-sm text-slate-700 mt-2">Çözülen talepler kapanır ve geçmişte saklanır.</p>
                </div>
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
                <option value="">Tümü</option>
                <option value="open" @selected(($filters['status'] ?? '') === 'open')>Açık</option>
                <option value="waiting_admin" @selected(($filters['status'] ?? '') === 'waiting_admin')>Destek Bekleniyor</option>
                <option value="waiting_customer" @selected(($filters['status'] ?? '') === 'waiting_customer')>Müşteri Bekleniyor</option>
                <option value="resolved" @selected(($filters['status'] ?? '') === 'resolved')>Çözüldü</option>
                <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Kapatıldı</option>
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
                    <th class="px-4 py-3 text-left">Öncelik</th>
                    <th class="px-4 py-3 text-left">Durum</th>
                    <th class="px-4 py-3 text-left">Son Aktivite</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($tickets as $ticket)
                    @php
                        $priorityClasses = [
                            'low' => 'bg-sky-50 text-sky-700 border border-sky-100',
                            'medium' => 'bg-amber-50 text-amber-800 border border-amber-100',
                            'high' => 'bg-orange-50 text-orange-800 border border-orange-100',
                            'urgent' => 'bg-rose-50 text-rose-700 border border-rose-100',
                        ];
                        $statusClasses = [
                            'open' => 'bg-emerald-50 text-emerald-700 border border-emerald-100',
                            'waiting_admin' => 'bg-sky-50 text-sky-700 border border-sky-100',
                            'waiting_customer' => 'bg-amber-50 text-amber-800 border border-amber-100',
                            'resolved' => 'bg-slate-100 text-slate-700 border border-slate-200',
                            'closed' => 'bg-slate-100 text-slate-700 border border-slate-200',
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $ticket->subject }}</td>
                        <td class="px-4 py-3">
                            <span class="panel-pill text-xs font-semibold {{ $priorityClasses[$ticket->priority] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="panel-pill text-xs font-semibold {{ $statusClasses[$ticket->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                {{ str_replace('_', ' ', $ticket->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ optional($ticket->last_activity_at)->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-800 font-semibold">Görüntüle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">Henüz destek talebi yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
@endsection


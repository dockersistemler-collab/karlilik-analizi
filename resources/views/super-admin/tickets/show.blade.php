@extends('layouts.super-admin')

@section('header')
    Ticket Detayı
@endsection

@section('content')
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

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <p class="text-xs uppercase text-slate-400">Ticket #{{ $ticket->id }}</p>
                <h3 class="text-xl font-semibold text-slate-900">{{ $ticket->subject }}</h3>
                <p class="text-sm text-slate-500">Müşteri: {{ $ticket->customer?->name ?? '-' }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$ticket->status] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ str_replace('_', ' ', $ticket->status) }}
                    </span>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $priorityClasses[$ticket->priority] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
            </div>
            <div class="text-sm text-slate-500">
                Son Aktivite: {{ optional($ticket->last_activity_at)->diffForHumans() ?? '-' }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="text-sm font-semibold text-slate-700 mb-3">Atama</h4>
            <form method="POST" action="{{ route('super-admin.tickets.assign', $ticket) }}" class="flex gap-2">
                @csrf
                <select name="assigned_to_id" class="flex-1 px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    @foreach($admins as $admin)
                        <option value="{{ $admin->id }}" @selected($ticket->assigned_to_id === $admin->id)>
                            {{ $admin->name }}
                        </option>
                    @endforeach
                </select>
                <button class="btn btn-solid-accent">Ata</button>
            </form>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="text-sm font-semibold text-slate-700 mb-3">Durum</h4>
            <form method="POST" action="{{ route('super-admin.tickets.status', $ticket) }}" class="flex gap-2">
                @csrf
                <select name="status" class="flex-1 px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                    @foreach(['open' => 'Açık', 'waiting_admin' => 'Destek Bekleniyor', 'waiting_customer' => 'Müşteri Bekleniyor', 'resolved' => 'Çözüldü', 'closed' => 'Kapatıldı'] as $value => $label)
                        <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-solid-accent">Güncelle</button>
            </form>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h4 class="text-sm font-semibold text-slate-700 mb-3">Öncelik</h4>
            <p class="text-slate-500">{{ ucfirst($ticket->priority) }}</p>
        </div>
    </div>

    <div class="space-y-4 mb-6">
        @foreach($ticket->messages as $message)
            <div class="bg-white rounded-lg shadow p-4 {{ $message->sender_type === 'customer' ? 'border-l-4 border-sky-400' : ($message->sender_type === 'system' ? 'border-l-4 border-slate-300' : 'border-l-4 border-emerald-400') }}">
                <div class="flex items-center justify-between mb-2 text-sm text-slate-500">
                    <span>
                        {{ $message->sender_type === 'customer' ? 'Müşteri' : ($message->sender_type === 'system' ? 'Sistem' : 'Destek') }}
                        @if($message->is_internal)
                            <span class="text-xs text-amber-600 ml-2">Internal</span>
                        @endif
                    </span>
                    <span>{{ $message->created_at->format('d.m.Y H:i') }}</span>
                </div>
                <p class="text-slate-700 whitespace-pre-line">{{ $message->body }}</p>

                @if($message->attachments->isNotEmpty())
                    <div class="mt-3 text-sm text-slate-500">
                        Ekler:
                        <ul class="list-disc list-inside">
                            @foreach($message->attachments as $attachment)
                                <li>{{ basename($attachment->path) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        @if($errors->any())
            <div class="bg-white rounded-lg shadow p-4 mb-6 border border-red-200 text-red-700">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('super-admin.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Yanıt</label>
                <textarea name="body" rows="5" class="mt-1 w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>{{ old('body') }}</textarea>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_internal" value="1" class="rounded" @checked(old('is_internal'))>
                <span class="text-sm text-slate-600">Internal not olarak işaretle</span>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Ek Dosyalar</label>
                <input type="file" name="attachments[]" multiple class="mt-1 w-full rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-solid-accent">
                    Yanıtla
                </button>
                <a href="{{ route('super-admin.tickets.index') }}" class="text-slate-500 hover:text-slate-700">Listeye Dön</a>
            </div>
        </form>
    </div>
@endsection



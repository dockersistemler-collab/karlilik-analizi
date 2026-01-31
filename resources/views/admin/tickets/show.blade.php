@extends('layouts.admin')

@section('header')
    Ticket Detayı
@endsection

@section('content')
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
        $statusAccent = match ($ticket->status) {
            'open' => 'from-emerald-50 to-sky-50',
            'waiting_admin' => 'from-sky-50 to-white',
            'waiting_customer' => 'from-amber-50 to-white',
            'resolved', 'closed' => 'from-slate-50 to-white',
            default => 'from-white to-white',
        };
    @endphp

    <div class="panel-card p-6 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br {{ $statusAccent }}"></div>
        <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-sky-200/30 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-emerald-200/25 blur-3xl"></div>

        <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-slate-500">Ticket #{{ $ticket->id }}</p>
                <h3 class="text-xl font-semibold text-slate-900 mt-2">{{ $ticket->subject }}</h3>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    <span class="panel-pill text-xs font-semibold {{ $statusClasses[$ticket->status] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                        {{ str_replace('_', ' ', $ticket->status) }}
                    </span>
                    <span class="panel-pill text-xs font-semibold {{ $priorityClasses[$ticket->priority] ?? 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
            </div>
            <div class="text-sm text-slate-600">
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-clock"></i>
                    Son aktivite: {{ optional($ticket->last_activity_at)->diffForHumans() ?? '-' }}
                </div>
                <div class="mt-3">
                    <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-accent">
                        <i class="fa-solid fa-arrow-left"></i>
                        Listeye Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4 mb-6">
        @foreach($ticket->messages as $message)
            @php
                $isCustomer = $message->sender_type === 'customer';
                $isSystem = $message->sender_type === 'system';
                $senderLabel = $isCustomer ? 'Siz' : ($isSystem ? 'Sistem' : 'Destek');
                $frame = $isCustomer
                    ? 'border-sky-200 bg-sky-50/40'
                    : ($isSystem ? 'border-slate-200 bg-slate-50/60' : 'border-emerald-200 bg-emerald-50/40');
                $icon = $isCustomer ? 'fa-user' : ($isSystem ? 'fa-gear' : 'fa-headset');
                $iconBg = $isCustomer ? 'bg-sky-100 text-sky-700' : ($isSystem ? 'bg-slate-100 text-slate-700' : 'bg-emerald-100 text-emerald-700');
            @endphp
            <div class="rounded-2xl border {{ $frame }} p-4">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex items-center gap-3">
                        <span class="h-9 w-9 rounded-xl {{ $iconBg }} flex items-center justify-center">
                            <i class="fa-solid {{ $icon }}"></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $senderLabel }}</p>
                            <p class="text-xs text-slate-500">{{ $message->created_at->format('d.m.Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <p class="text-slate-700 whitespace-pre-line">{{ $message->body }}</p>

                @if($message->attachments->isNotEmpty())
                    <div class="mt-3 text-sm text-slate-600">
                        <p class="text-xs uppercase tracking-[0.18em] text-slate-500 mb-2">Ekler</p>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($message->attachments as $attachment)
                                <li class="rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                    <i class="fa-regular fa-file-lines text-slate-400 mr-2"></i>
                                    {{ basename($attachment->path) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="panel-card p-6">
        @if($errors->any())
            <div class="panel-card p-4 mb-6 border-red-200 text-red-700">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700">Yanıt</label>
                <textarea name="body" rows="5" class="mt-1 w-full px-4 py-2 bg-white border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>{{ old('body') }}</textarea>
            </div>
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <label class="block text-sm font-medium text-slate-700">Ek Dosyalar</label>
                <input type="file" name="attachments[]" multiple class="mt-2 w-full text-sm text-slate-700">
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-solid-accent">
                    Yanıtla
                </button>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-accent">Listeye Dön</a>
            </div>
        </form>
    </div>
@endsection


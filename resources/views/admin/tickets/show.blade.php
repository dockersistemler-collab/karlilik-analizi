@extends('layouts.admin')

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

    <div class="panel-card p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-xs uppercase text-slate-400">Ticket #{{ $ticket->id }}</p>
                <h3 class="text-xl font-semibold text-slate-900">{{ $ticket->subject }}</h3>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    <span class="panel-pill text-xs font-semibold {{ $statusClasses[$ticket->status] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ str_replace('_', ' ', $ticket->status) }}
                    </span>
                    <span class="panel-pill text-xs font-semibold {{ $priorityClasses[$ticket->priority] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
            </div>
            <div class="text-sm text-slate-500">
                Son Aktivite: {{ optional($ticket->last_activity_at)->diffForHumans() ?? '-' }}
            </div>
        </div>
    </div>

    <div class="space-y-4 mb-6">
        @foreach($ticket->messages as $message)
            <div class="panel-card p-4 {{ $message->sender_type === 'customer' ? 'border-l-4 border-sky-400' : ($message->sender_type === 'system' ? 'border-l-4 border-slate-300' : 'border-l-4 border-emerald-400') }}">
                <div class="flex items-center justify-between mb-2 text-sm text-slate-500">
                    <span>
                        {{ $message->sender_type === 'customer' ? 'Siz' : ($message->sender_type === 'system' ? 'Sistem' : 'Destek') }}
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
                <textarea name="body" rows="5" class="mt-1 w-full px-4 py-2 bg-white border border-slate-200 rounded-lg text-slate-900 placeholder-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200" required>{{ old('body') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Ek Dosyalar</label>
                <input type="file" name="attachments[]" multiple class="mt-1 w-full rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600">
            </div>
            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-solid-accent">
                    Yanıtla
                </button>
                <a href="{{ route('admin.tickets.index') }}" class="text-slate-500 hover:text-slate-700">Listeye Dön</a>
            </div>
        </form>
    </div>
@endsection



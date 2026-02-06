@extends('layouts.super-admin')



@section('header', 'E-Posta Kaydi Detayi')



@section('content')

    <div class="panel-card p-4 mb-4">

        <div class="flex items-center justify-between">

            <div>

                <div class="text-sm text-slate-500">Key</div>

                <div class="text-lg font-semibold text-slate-800">{{ $mailLog->key }}</div>

            </div>

            <a href="{{ route('super-admin.mail-logs.index') }}" class="btn btn-outline-accent">Geri</a>

        </div>

    </div>



    <div class="panel-card p-4">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>

                <div class="text-xs font-semibold text-slate-500">Kullanici</div>

                <div class="text-slate-800 font-semibold">{{ $user?->name ?? '-' }}</div>

                <div class="text-slate-500 text-sm">{{ $user?->email ?? '-' }}</div>

            </div>

            <div>

                <div class="text-xs font-semibold text-slate-500">Status</div>

                <div class="text-slate-800">{{ $mailLog->status ?? '-' }}</div>

            </div>

            <div>

                <div class="text-xs font-semibold text-slate-500">Created At</div>

                <div class="text-slate-800">{{ optional($mailLog->created_at)->format('d.m.Y H:i') }}</div>

            </div>

            <div>

                <div class="text-xs font-semibold text-slate-500">Sent At</div>

                <div class="text-slate-800">{{ optional($mailLog->sent_at)->format('d.m.Y H:i') ?? '-' }}</div>

            </div>

        </div>



        <div class="mt-6">

            <div class="text-xs font-semibold text-slate-500">Error</div>

            <div class="text-slate-700 whitespace-pre-wrap">{{ $mailLog->error ?? '-' }}</div>

        </div>



        <div class="mt-6">

            <div class="text-xs font-semibold text-slate-500">Metadata</div>

            <pre class="mt-2 text-xs bg-slate-900 text-slate-100 rounded-md p-3 overflow-x-auto">{{ json_encode($mailLog->metadata_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

        </div>

    </div>

@endsection








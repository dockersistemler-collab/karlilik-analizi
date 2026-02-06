@extends('layouts.admin')



@section('header', 'E-Posta Şablonu')



@section('content')

    <div class="panel-card p-4 mb-4">

        <div class="flex items-center justify-between">

            <div>

                <div class="text-sm text-slate-500">Key</div>

                <div class="text-lg font-semibold text-slate-800">{{ $template->key }}</div>

            </div>

            <a href="{{ route($routePrefix.'notifications.mail-templates.index') }}" class="btn btn-outline-accent">Geri</a>

        </div>

    </div>



    @php

        $reasonMap = [

            'blocked_by_plan' => 'Plan kısıtı',

            'template_disabled' => 'Sistem kapalı',

            'limit_reached' => 'Limit aşıldı',

        ];

        $reasonKey = $decision['reason'] ?? null;

        $reasonText = $reasonKey && array_key_exists($reasonKey, $reasonMap) ? $reasonMap[$reasonKey] : null;

    @endphp



    @if(($decision['decision'] ?? null) === 'blocked')

        <div class="panel-card p-4 mb-4 border border-amber-200 bg-amber-50 text-amber-700">

            Planınız bu bildirimi desteklemiyor.

            @if($reasonText)

                <div class="text-xs text-amber-700 mt-1">Neden: {{ $reasonText }}</div>

            @endif

        </div>

    @elseif(($decision['decision'] ?? null) === 'skipped')

        <div class="panel-card p-4 mb-4 border border-slate-200 bg-slate-50 text-slate-700">

            Bu şablon sistemde kapalı.

            @if($reasonText)

                <div class="text-xs text-slate-600 mt-1">Neden: {{ $reasonText }}</div>

            @endif

        </div>

    @endif



    <div class="panel-card p-4">

        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">

            <div>

                <div class="text-xs font-semibold text-slate-500">Konu</div>

                <div class="text-slate-800 font-semibold">{{ $template->subject ?? '-' }}</div>

                <div class="text-xs text-slate-500 mt-2">Kategori: {{ $template->category ?? '-' }}</div>

            </div>

            <div>

                <form method="POST" action="{{ route($routePrefix.'notifications.mail-templates.toggle', $template) }}">

                    @csrf

                    @method('PATCH')

                    <button type="submit" class="btn btn-outline-accent" {{ in_array(($decision['decision'] ?? null), ['blocked', 'skipped'], true) ? 'disabled' : '' }}>

                        {{ $template->enabled ? 'Pasifleştir' : 'Aktifleştir' }}

                    </button>

                </form>

            </div>

        </div>



        @if($routePrefix === 'super-admin.')

            <div class="mt-6">

                <div class="text-xs font-semibold text-slate-500">Test Mail</div>

                <form method="POST" action="{{ route($routePrefix.'notifications.mail-templates.test', $template) }}" class="mt-2 flex items-center gap-3">

                    @csrf

                    <button type="submit" class="btn btn-solid-accent" {{ in_array(($decision['decision'] ?? null), ['blocked', 'skipped'], true) ? 'disabled' : '' }}>

                        Test mail gönder

                    </button>

                    @if(in_array(($decision['decision'] ?? null), ['blocked', 'skipped'], true))

                        <span class="text-xs text-amber-700">Bu şablon için gönderim engelli.</span>

                    @endif

                </form>

            </div>



            <div class="mt-6">

                <div class="text-xs font-semibold text-slate-500">Önizleme - Subject</div>

                <div class="text-slate-800 font-semibold mt-1">{{ $previewSubject !== '' ? $previewSubject : '-' }}</div>

            </div>



            <div class="mt-4">

                <div class="text-xs font-semibold text-slate-500">Önizleme - Body</div>

                <div class="prose max-w-none mt-2">

                    {!! $previewBody !== '' ? $previewBody : '-' !!}

                </div>

            </div>

        @endif



        <div class="mt-6">

            <div class="text-xs font-semibold text-slate-500">Kullanılan Değişkenler</div>

            @if(empty($variables))

                <div class="text-slate-500 text-sm mt-2">Değişken bulunamadı.</div>

            @else

                <div class="flex flex-wrap gap-2 mt-2">

                    @foreach($variables as $variable)

                        <span class="badge badge-muted">{{ $variable }}</span>

                    @endforeach

                </div>

            @endif

        </div>



        <div class="mt-6">

            <div class="text-xs font-semibold text-slate-500">İçerik</div>

            <div class="prose max-w-none mt-2">

                {!! $template->body_html !!}

            </div>

        </div>

    </div>

@endsection



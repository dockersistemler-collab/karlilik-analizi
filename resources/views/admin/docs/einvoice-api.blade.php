@extends('layouts.admin')



@section('header')

    {{ $title ?? 'E-Fatura API Dokümantasyonu' }}

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-5xl mx-auto space-y-4">

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

                <div class="text-sm text-slate-600">

                    Token üretmek için <span class="font-semibold">API Erişimi</span> modülünü satın almalısınız.

                </div>

                <div class="flex items-center gap-2">

                    <a href="{{ route('portal.docs.einvoice.openapi') }}" class="btn btn-outline">OpenAPI indir</a>

                    <a href="{{ route('portal.docs.einvoice.postman') }}" class="btn btn-outline">Postman indir</a>

                </div>

            </div>



            @if(!empty($error))

                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">

                    {{ $error }}

                </div>

            @endif



            <div class="rounded-xl border border-slate-200 bg-white p-6">

                <div class="markdown-body rich-content max-w-none">

                    @if(!empty($html))

                        {!! $html !!}

                    @else

                        <div class="text-sm text-slate-600">

                            Doküman içeriği şu an görüntülenemiyor.

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>



    <style>

        .markdown-body h1 { font-size: 1.6rem; font-weight: 700; margin: 0 0 1rem; }

        .markdown-body h2 { font-size: 1.25rem; font-weight: 700; margin: 1.5rem 0 0.75rem; }

        .markdown-body h3 { font-size: 1.05rem; font-weight: 700; margin: 1.25rem 0 0.5rem; }

        .markdown-body p { margin: 0 0 0.75rem; color: #334155; }

        .markdown-body ul, .markdown-body ol { padding-left: 1.25rem; margin: 0 0 0.75rem; color: #334155; }

        .markdown-body li { margin: 0.25rem 0; }

        .markdown-body a { color: #2563eb; text-decoration: underline; }

        .markdown-body pre { background: #0b1220; color: #e2e8f0; padding: 0.9rem; border-radius: 12px; overflow-x: auto; margin: 0.75rem 0; }

        .markdown-body code { background: #f1f5f9; padding: 0.15rem 0.35rem; border-radius: 8px; font-size: 0.9em; }

        .markdown-body pre code { background: transparent; padding: 0; color: inherit; }

        .markdown-body table { width: 100%; border-collapse: collapse; margin: 0.75rem 0; font-size: 0.95rem; }

        .markdown-body th, .markdown-body td { border: 1px solid #e2e8f0; padding: 0.5rem 0.6rem; vertical-align: top; }

        .markdown-body th { background: #f8fafc; font-weight: 700; }

        .markdown-body blockquote { border-left: 3px solid #e2e8f0; padding-left: 0.75rem; color: #475569; margin: 0.75rem 0; }

    </style>

@endsection







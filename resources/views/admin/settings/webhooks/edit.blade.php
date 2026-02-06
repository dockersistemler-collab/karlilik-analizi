@extends('layouts.admin')



@section('header')

    Webhook Düzenle

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-4xl mx-auto space-y-6">

            <div class="flex items-center justify-end gap-2">

                <a href="{{ route('portal.webhooks.deliveries', $endpoint) }}" class="btn btn-outline">Teslimat Logları</a>

                <form method="POST" action="{{ route('portal.webhooks.test', $endpoint) }}">

                    @csrf

                    <button type="submit" class="btn btn-outline">Test Gönder</button>

                </form>

            </div>



            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">

                    {{ session('success') }}

                </div>

            @endif

            @if(session('created_webhook_secret'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">

                    <div class="text-sm font-semibold text-emerald-800">Webhook secret (bir kere gösterilir)</div>

                    <div class="mt-2 font-mono text-xs break-all text-emerald-900">{{ session('created_webhook_secret') }}</div>

                </div>

            @endif

            @if ($errors->any())

                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">

                    <div class="font-semibold">Lütfen bilgileri kontrol edin.</div>

                    <ul class="mt-2 list-disc list-inside space-y-1">

                        @foreach ($errors->all() as $e)

                            <li>{{ $e }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif



            <div class="bg-white rounded-xl border border-slate-100 p-6 space-y-4">

                <div class="flex items-center justify-between">

                    <div>

                        <div class="text-lg font-semibold text-slate-900">{{ $endpoint->name }}</div>

                        <div class="text-xs text-slate-500">ID: {{ $endpoint->id }}</div>

                    </div>

                    <form method="POST" action="{{ route('portal.webhooks.secret.rotate', $endpoint) }}">

                        @csrf

                        <button type="submit" class="btn btn-outline">Secret Yenile</button>

                    </form>

                </div>



                <form method="POST" action="{{ route('portal.webhooks.update', $endpoint) }}" class="space-y-4">

                    @csrf

                    @method('PUT')



                    <div>

                        <label class="block text-sm font-medium text-slate-700">Ad</label>

                        <input name="name" value="{{ old('name', $endpoint->name) }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2" required />

                    </div>



                    <div>

                        <label class="block text-sm font-medium text-slate-700">URL</label>

                        <input name="url" value="{{ old('url', $endpoint->url) }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-sm" required />

                    </div>



                    <div>

                        <div class="text-sm font-medium text-slate-700">Eventler</div>

                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">

                            @php

                                $currentEvents = old('events', is_array($endpoint->events) ? $endpoint->events : []);

                            @endphp

                            @foreach($availableEvents as $key => $label)

                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">

                                    <input type="checkbox" name="events[]" value="{{ $key }}" @checked(in_array($key, $currentEvents, true)) />

                                    <span>{{ $label }}</span>

                                </label>

                            @endforeach

                        </div>

                    </div>



                    <div>

                        <label class="block text-sm font-medium text-slate-700">Timeout (saniye)</label>

                        <select name="timeout_seconds" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2">

                            @php

                                $timeout = (int) old('timeout_seconds', $endpoint->timeout_seconds ?? 10);

                            @endphp

                            @foreach([5, 10, 20, 30, 60] as $t)

                                <option value="{{ $t }}" @selected($timeout === $t)>{{ $t }}</option>

                            @endforeach

                        </select>

                    </div>



                    <div>

                        <label class="block text-sm font-medium text-slate-700">Ek Headerlar (JSON)</label>

                        <textarea name="headers_json" rows="4" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-xs" placeholder='{"X-Customer":"abc"}'>{{ old('headers_json', $endpoint->headers_json ? json_encode($endpoint->headers_json, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : '') }}</textarea>

                    </div>



                    <div class="flex items-center gap-2">

                        <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', (bool) $endpoint->is_active)) />

                        <label for="is_active" class="text-sm text-slate-700">Aktif</label>

                    </div>



                    <div class="flex items-center justify-between">

                        <a href="{{ route('portal.webhooks.index') }}" class="btn btn-outline">Geri</a>

                        <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection







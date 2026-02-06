@extends('layouts.admin')

@section('header')
    Webhook Oluştur
@endsection

@section('content')
    <div class="panel-card p-6">
        <div class="max-w-4xl mx-auto space-y-6">
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

            <div class="bg-white rounded-xl border border-slate-100 p-6">
                <form method="POST" action="{{ route('portal.webhooks.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Ad</label>
                        <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2" required />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">URL</label>
                        <input name="url" value="{{ old('url') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-sm" placeholder="https://example.com/webhooks/einvoice" required />
                        <div class="mt-2 text-xs text-slate-500">Öneri: HTTPS kullanın.</div>
                    </div>

                    <div>
                        <div class="text-sm font-medium text-slate-700">Eventler</div>
                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($availableEvents as $key => $label)
                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                    <input type="checkbox" name="events[]" value="{{ $key }}" @checked(in_array($key, old('events', ['einvoice.*']), true)) />
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Timeout (saniye)</label>
                        <select name="timeout_seconds" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2">
                            @php
                                $timeout = (int) old('timeout_seconds', 10);
                            @endphp
                            @foreach([5, 10, 20, 30, 60] as $t)
                                <option value="{{ $t }}" @selected($timeout === $t)>{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Ek Headerlar (JSON)</label>
                        <textarea name="headers_json" rows="4" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-xs" placeholder='{"X-Customer":"abc"}'>{{ old('headers_json') }}</textarea>
                        <div class="mt-2 text-xs text-slate-500">İsteğe bağlı. JSON object olmalı.</div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) />
                        <label for="is_active" class="text-sm text-slate-700">Aktif</label>
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('portal.webhooks.index') }}" class="btn btn-outline">Geri</a>
                        <button type="submit" class="btn btn-solid-accent">Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection



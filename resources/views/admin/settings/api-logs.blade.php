@extends('layouts.admin')



@section('header')

    API Logları

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-6xl mx-auto space-y-6">

            <div class="flex items-center justify-between gap-3">

                <div class="text-sm text-slate-600">

                    Son 30 gün API erişim kayıtları (güvenli özet; response body/PII tutulmaz).

                </div>

                <a href="{{ route('portal.settings.api') }}" class="btn btn-outline">API Ayarları</a>

            </div>



            <div class="bg-white rounded-xl border border-slate-100 p-5">

                <form method="GET" action="{{ route('portal.settings.api.logs') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">

                    <div>

                        <label class="block text-xs font-semibold text-slate-600">Status</label>

                        <input name="status_code" value="{{ request('status_code') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="200" />

                    </div>

                    <div>

                        <label class="block text-xs font-semibold text-slate-600">Token</label>

                        <select name="token_id" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">

                            <option value="">Tümü</option>

                            @foreach($tokens as $t)

                                <option value="{{ $t->id }}" @selected((string) $t->id === (string) request('token_id'))>

                                    {{ $t->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div>

                        <label class="block text-xs font-semibold text-slate-600">Başlangıç</label>

                        <input type="date" name="from" value="{{ request('from') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" />

                    </div>

                    <div>

                        <label class="block text-xs font-semibold text-slate-600">Bitiş</label>

                        <input type="date" name="to" value="{{ request('to') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" />

                    </div>

                    <div class="flex items-end gap-2">

                        <button type="submit" class="btn btn-solid-accent">Filtrele</button>

                        <a href="{{ route('portal.settings.api.logs') }}" class="btn btn-outline">Sıfırla</a>

                    </div>

                </form>

            </div>



            <div class="bg-white rounded-xl border border-slate-100 p-0 overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="text-left text-slate-500">

                        <tr>

                            <th class="py-3 px-4">Tarih</th>

                            <th class="py-3 px-4">Method</th>

                            <th class="py-3 px-4">Path</th>

                            <th class="py-3 px-4">Status</th>

                            <th class="py-3 px-4">Süre</th>

                            <th class="py-3 px-4">IP</th>

                            <th class="py-3 px-4">Token</th>

                            <th class="py-3 px-4">Request ID</th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse($logs as $row)

                            <tr>

                                <td class="py-3 px-4 text-slate-700 whitespace-nowrap">

                                    {{ $row->created_at?->format('d.m.Y H:i:s') ?? '-' }}

                                </td>

                                <td class="py-3 px-4 font-mono text-xs text-slate-700">

                                    {{ strtoupper((string) $row->method) }}

                                </td>

                                <td class="py-3 px-4 text-slate-700 font-mono text-xs">

                                    {{ $row->path }}

                                </td>

                                <td class="py-3 px-4">

                                    @php

                                        $status = (int) $row->status_code;

                                        $statusClass = $status >= 500 ? 'bg-rose-50 text-rose-700 border-rose-200'

                                            : ($status >= 400 ? 'bg-amber-50 text-amber-700 border-amber-200'

                                                : 'bg-emerald-50 text-emerald-700 border-emerald-200');

                                    @endphp

                                    <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs {{ $statusClass }}">

                                        {{ $status }}

                                    </span>

                                </td>

                                <td class="py-3 px-4 text-slate-700 whitespace-nowrap">

                                    {{ $row->duration_ms !== null ? ((int) $row->duration_ms).' ms' : '-' }}

                                </td>

                                <td class="py-3 px-4 text-slate-700 font-mono text-xs whitespace-nowrap">

                                    {{ $row->ip ?? '-' }}

                                </td>

                                <td class="py-3 px-4 text-slate-700">

                                    {{ $row->token_name ?? '-' }}

                                </td>

                                <td class="py-3 px-4 text-slate-700 font-mono text-xs whitespace-nowrap">

                                    {{ $row->request_id ?? '-' }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="py-10 text-center text-slate-500">Kayıt yok.</td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>



            <div>

                {{ $logs->links() }}

            </div>



            <div class="text-xs text-slate-500">

                Not (gelecek geliştirme): “Şüpheli erişim” (aynı token farklı IP’ler) uyarıları eklenecek.

            </div>

        </div>

    </div>

@endsection







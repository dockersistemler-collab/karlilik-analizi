@extends('layouts.super-admin')



@section('header')

    Kargo Mapping

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-6xl space-y-6">

            <div class="flex items-center justify-between gap-3">

                <form method="GET" class="flex flex-wrap items-center gap-3 text-sm">

                    <select name="marketplace_code">

                        <option value="">Pazaryeri</option>

                        @foreach($marketplaces as $mp)

                            <option value="{{ $mp->code }}" @selected(request('marketplace_code') === $mp->code)>{{ $mp->name }} ({{ $mp->code }})</option>

                        @endforeach

                    </select>

                    <select name="provider_key">

                        <option value="">SaÄŸlayıcı</option>

                        @foreach($providers as $key => $meta)

                            <option value="{{ $key }}" @selected(request('provider_key') === $key)>{{ $meta['label'] ?? $key }}</option>

                        @endforeach

                    </select>

                    <select name="is_active">

                        <option value="">Durum</option>

                        <option value="1" @selected(request('is_active') === '1')>Aktif</option>

                        <option value="0" @selected(request('is_active') === '0')>Pasif</option>

                    </select>

                    <button type="submit" class="btn btn-outline">Filtrele</button>

                </form>

                <a href="{{ route('super-admin.cargo.mappings.create') }}" class="btn btn-solid-accent">Yeni Mapping</a>

            </div>



            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="text-left text-slate-500">

                        <tr>

                            <th class="py-2 pr-4">Pazaryeri</th>

                            <th class="py-2 pr-4">Carrier Code</th>

                            <th class="py-2 pr-4">Provider</th>

                            <th class="py-2 pr-4">Öncelik</th>

                            <th class="py-2 pr-4">Durum</th>

                            <th class="py-2 pr-4"></th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse($mappings as $mapping)

                            <tr>

                                <td class="py-3 pr-4 text-slate-800">{{ $mapping->marketplace_code }}</td>

                                <td class="py-3 pr-4">

                                    <div class="font-mono text-xs text-slate-700">{{ $mapping->external_carrier_code }}</div>

                                    @if($mapping->external_carrier_code_normalized)

                                        <div class="mt-1 inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-[11px] text-slate-500">

                                            {{ $mapping->external_carrier_code_normalized }}

                                        </div>

                                    @endif

                                </td>

                                <td class="py-3 pr-4 text-slate-700">

                                    <div class="font-semibold">{{ $providers[$mapping->provider_key]['label'] ?? $mapping->provider_key }}</div>

                                    <div class="text-xs text-slate-500">integration.cargo.{{ $mapping->provider_key }}</div>

                                </td>

                                <td class="py-3 pr-4 text-slate-700">{{ $mapping->priority ?? '-' }}</td>

                                <td class="py-3 pr-4">

                                    @if($mapping->is_active)

                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">Aktif</span>

                                    @else

                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-700">Pasif</span>

                                    @endif

                                </td>

                                <td class="py-3 pr-4 text-right whitespace-nowrap">

                                    <a href="{{ route('super-admin.cargo.mappings.edit', $mapping) }}" class="btn btn-outline">Düzenle</a>

                                    <form method="POST" action="{{ route('super-admin.cargo.mappings.destroy', $mapping) }}" class="inline" onsubmit="return confirm('Mapping silinsin mi?')">

                                        @csrf

                                        @method('DELETE')

                                        <button type="submit" class="btn btn-outline">Sil</button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="py-6 text-center text-slate-500">Mapping bulunamadı.</td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>



            {{ $mappings->links() }}

        </div>

    </div>

@endsection








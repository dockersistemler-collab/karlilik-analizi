@extends('layouts.super-admin')



@section('header')

    Kargo SaÄŸlayıcıları

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-5xl space-y-6">

            <div class="text-sm text-slate-600">

                SaÄŸlayıcı modül durumlarını buradan aktif/pasif edebilirsiniz.

            </div>



            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead class="text-left text-slate-500">

                        <tr>

                            <th class="py-2 pr-4">SaÄŸlayıcı</th>

                            <th class="py-2 pr-4">Module Code</th>

                            <th class="py-2 pr-4">Durum</th>

                            <th class="py-2 pr-4"></th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse($providers as $key => $meta)

                            @php

                                $moduleCode = "integration.cargo.{$key}";

                                $module = $modules[$moduleCode] ?? null;

                            @endphp

                            <tr>

                                <td class="py-3 pr-4 font-semibold text-slate-800">{{ $meta['label'] ?? $key }}</td>

                                <td class="py-3 pr-4 font-mono text-xs text-slate-700">{{ $moduleCode }}</td>

                                <td class="py-3 pr-4">

                                    @if($module?->is_active)

                                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">Aktif</span>

                                    @else

                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-700">Pasif</span>

                                    @endif

                                </td>

                                <td class="py-3 pr-4 text-right">

                                    <form method="POST" action="{{ route('super-admin.cargo.providers.toggle', $key) }}">

                                        @csrf

                                        <button type="submit" class="btn btn-outline">

                                            {{ $module?->is_active ? 'Pasif Yap' : 'Aktif Et' }}

                                        </button>

                                    </form>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="4" class="py-6 text-center text-slate-500">SaÄŸlayıcı bulunamadı.</td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection








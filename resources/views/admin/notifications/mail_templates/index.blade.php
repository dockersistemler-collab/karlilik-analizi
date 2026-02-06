@extends('layouts.admin')



@section('header', 'E-Posta Şablonları')



@section('content')

    <div class="panel-card p-4 mb-4">

        <form method="GET" action="{{ route($routePrefix.'notifications.mail-templates.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Key</label>

                <input type="text" name="key" value="{{ $key }}" placeholder="subscription.started">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Kategori</label>

                <input type="text" name="category" value="{{ $category }}" placeholder="billing">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Durum</label>

                <select name="enabled">

                    <option value="">Tümü</option>

                    <option value="1" {{ (string) $enabled === '1' ? 'selected' : '' }}>Aktif</option>

                    <option value="0" {{ (string) $enabled === '0' ? 'selected' : '' }}>Pasif</option>

                </select>

            </div>

            <div>

                <button type="submit" class="btn btn-solid-accent">Uygula</button>

            </div>

        </form>

    </div>



    <div class="panel-card p-4">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr>

                        <th class="text-left">Key</th>

                        <th class="text-left">Kategori</th>

                        <th class="text-left">Konu</th>

                        <th class="text-left">Durum</th>

                        <th class="text-left">Detay</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($templates as $template)

                        @php

                            $statusLabel = $template->enabled ? 'aktif' : 'pasif';

                            $badgeClass = $template->enabled ? 'badge badge-success' : 'badge badge-muted';

                        @endphp

                        <tr>

                            <td class="text-slate-700">{{ $template->key }}</td>

                            <td class="text-slate-700">{{ $template->category ?? '-' }}</td>

                            <td class="text-slate-700">{{ \Illuminate\Support\Str::limit($template->subject ?? '-', 60) }}</td>

                            <td>

                                <span class="{{ $badgeClass }}">{{ $statusLabel }}</span>

                            </td>

                            <td>

                                <a class="btn btn-outline-accent" href="{{ route($routePrefix.'notifications.mail-templates.show', $template) }}">

                                    İncele

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5" class="text-center text-slate-500 py-6">Kayıt bulunamadı.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-4">

            {{ $templates->links() }}

        </div>

    </div>

@endsection


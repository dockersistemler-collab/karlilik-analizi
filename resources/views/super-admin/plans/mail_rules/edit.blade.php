@extends('layouts.super-admin')



@section('header', 'Plan Mail Yetkileri')



@section('content')

    <div class="panel-card p-4 mb-4">

        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">

            <div>

                <div class="text-sm text-slate-500">Plan</div>

                <div class="text-lg font-semibold text-slate-800">{{ $plan->name }}</div>

            </div>

            <a href="{{ route('super-admin.plans.index') }}" class="btn btn-outline-accent">Geri</a>

        </div>

    </div>



    <div class="panel-card p-4 mb-4">

        <form method="GET" action="{{ route('super-admin.plans.mail-rules.edit', $plan) }}" class="flex flex-col gap-3 md:flex-row md:items-end">

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Key</label>

                <input type="text" name="key" value="{{ $key }}" placeholder="subscription.started">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Kategori</label>

                <input type="text" name="category" value="{{ $category }}" placeholder="billing">

            </div>

            <div>

                <button type="submit" class="btn btn-solid-accent">Ara</button>

            </div>

        </form>

    </div>



    <form method="POST" action="{{ route('super-admin.plans.mail-rules.update', $plan) }}">

        @csrf

        @method('PUT')



        <div class="panel-card p-4">

            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead>

                        <tr>

                            <th class="text-left">Key</th>

                            <th class="text-left">Kategori</th>

                            <th class="text-left">Template Durumu</th>

                            <th class="text-left">Plan Kuralı</th>

                            <th class="text-left">Plan İzni</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($templates as $template)

                            @php

                                $templateEnabled = (bool) $template->enabled;

                                $templateBadge = $templateEnabled ? 'badge badge-success' : 'badge badge-muted';

                                $hasRule = array_key_exists($template->key, $allowedMap);

                                $allowed = $allowedMap[$template->key] ?? true;

                                $ruleLabel = $hasRule ? ($allowed ? 'Allowed' : 'Blocked') : 'Default (Allow)';

                                $ruleBadge = $hasRule

                                    ? ($allowed ? 'badge badge-success' : 'badge badge-danger')

                                    : 'badge badge-muted';

                            @endphp

                            <tr>

                                <td class="text-slate-700">{{ $template->key }}</td>

                                <td class="text-slate-700">{{ $template->category ?? '-' }}</td>

                                <td>

                                    <span class="{{ $templateBadge }}">{{ $templateEnabled ? 'aktif' : 'pasif' }}</span>

                                </td>

                                <td>

                                    <span class="{{ $ruleBadge }}">{{ $ruleLabel }}</span>

                                </td>

                                <td>

                                    <label class="inline-flex items-center gap-2">

                                        <input type="checkbox" name="allowed[]" value="{{ $template->key }}" {{ $allowed ? 'checked' : '' }}>

                                        <span class="text-slate-700">İzinli</span>

                                    </label>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="4" class="text-center text-slate-500 py-6">Kayıt bulunamadı.</td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>



        <div class="mt-4">

            <button type="submit" class="btn btn-solid-accent">Kaydet</button>

        </div>

    </form>

@endsection








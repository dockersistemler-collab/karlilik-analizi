@extends('layouts.admin')



@section('header')

    Kargo Entegrasyonları

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-5xl mx-auto space-y-6">

            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">

                    {{ session('success') }}

                </div>

            @endif

            @if (session('error'))

                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">

                    {{ session('error') }}

                </div>

            @endif



            @if(!$hasAccess)

                @php

                    $featureModule = $modules['feature.cargo_tracking'] ?? null;

                @endphp

                <div class="bg-white rounded-xl border border-slate-100 p-6">

                    <div class="text-lg font-semibold text-slate-900">{{ $featureModule?->name ?? 'Kargo Takip' }}</div>

                    <p class="text-sm text-slate-600 mt-2">

                        Kargo entegrasyonlarını kullanmak için bu modülü satın almanız gerekir.

                    </p>



                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                        <div>

                            <div class="text-sm font-semibold text-slate-800">{{ $featureModule?->name ?? 'Kargo Takip' }}</div>

                            <div class="text-xs text-slate-500 mt-1">Yıllık kullanım</div>

                        </div>

                        @if($featureModule)

                            <form method="POST" action="{{ route('portal.my-modules.renew', $featureModule) }}" class="flex items-center gap-2">

                                @csrf

                                <input type="hidden" name="period" value="yearly" />

                                <button type="submit" class="btn btn-solid-accent">Yıllık Satın Al</button>

                            </form>

                        @else

                            <div class="text-sm text-rose-600">Modül kataloÄŸu kaydı bulunamadı (feature.cargo_tracking).</div>

                        @endif

                    </div>

                </div>

            @endif



            <div class="space-y-4">

                @forelse($providers as $key => $meta)

                    @php

                        $moduleCode = "integration.cargo.{$key}";

                        $module = $modules[$moduleCode] ?? null;

                        $installation = $installations[$key] ?? null;

                        $canUse = ($providerAccess[$key] ?? false) && $hasAccess;

                        $credentials = is_array($installation?->credentials_json) ? $installation->credentials_json : [];

                        $fields = is_array($meta['credentials'] ?? null) ? $meta['credentials'] : [];

                    @endphp

                    <div class="bg-white rounded-xl border border-slate-100 p-6 space-y-4">

                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">

                            <div>

                                <div class="text-lg font-semibold text-slate-900">{{ $meta['label'] ?? $key }}</div>

                                <div class="text-xs text-slate-500">{{ $moduleCode }}</div>

                            </div>

                            @if($canUse)

                                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">EriÅŸim Var</span>

                            @else

                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-700">EriÅŸim Yok</span>

                            @endif

                        </div>



                        @if(!$canUse)

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                                <div class="text-sm text-slate-700">

                                    Bu saÄŸlayıcıyı kullanmak için ilgili modülü satın almanız gerekir.

                                </div>

                                <div class="mt-3">

                                    @if($module)

                                        <form method="POST" action="{{ route('portal.my-modules.renew', $module) }}" class="inline">

                                            @csrf

                                            <input type="hidden" name="period" value="yearly" />

                                            <button type="submit" class="btn btn-outline">Satın Al</button>

                                        </form>

                                    @else

                                        <div class="text-xs text-rose-600">Modül kataloÄŸu kaydı bulunamadı.</div>

                                    @endif

                                </div>

                            </div>

                        @endif



                        <form id="cargo-update-{{ $key }}" method="POST" action="{{ route('portal.settings.cargo.update', $key) }}" class="space-y-3">

                            @csrf

                            @method('PUT')



                            @foreach($fields as $fieldKey => $field)

                                <div>

                                    <label class="block text-sm font-medium text-slate-700 mb-1">{{ $field['label'] ?? $fieldKey }}</label>

                                    <input

                                        type="{{ $field['type'] ?? 'text' }}"

                                        name="{{ $fieldKey }}"

                                        value="{{ old($fieldKey, $credentials[$fieldKey] ?? '') }}"

                                        class="w-full"

                                        @if($field['required'] ?? true) required @endif

                                        @disabled(!$canUse)

                                    >

                                </div>

                            @endforeach



                            <div class="flex items-center gap-2">

                                <input type="hidden" name="is_active" value="0">

                                <input type="checkbox" name="is_active" value="1" class="h-4 w-4" @checked($installation?->is_active ?? true) @disabled(!$canUse)>

                                <label class="text-sm text-slate-700">Aktif</label>

                            </div>



                        </form>

                        <form id="cargo-test-{{ $key }}" method="POST" action="{{ route('portal.settings.cargo.test', $key) }}">

                            @csrf

                        </form>

                        <div class="flex flex-wrap items-center gap-2">

                            <button type="submit" form="cargo-update-{{ $key }}" class="btn btn-solid-accent" @disabled(!$canUse)>Kaydet</button>

                            <button type="submit" form="cargo-test-{{ $key }}" class="btn btn-outline" @disabled(!$canUse)>Test Et</button>

                        </div>

                    </div>

                @empty

                    <div class="text-sm text-slate-500">Sağlayıcı bulunamadı.</div>

                @endforelse

            </div>

        </div>

    </div>

@endsection








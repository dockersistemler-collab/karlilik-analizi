@extends('layouts.admin')



@section('header')

    E-Fatura Ayarları

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-3xl mx-auto">

            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <form method="POST" action="{{ route('portal.settings.einvoice.update') }}" class="space-y-5">

                    @csrf

                    @method('PUT')



                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div class="md:col-span-2">

                            <label class="block text-sm font-medium text-slate-700">Aktif Sağlayıcı</label>

                            <select name="active_provider_key" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2">

                                @foreach($providers as $key => $meta)

                                    <option value="{{ $key }}" @selected(old('active_provider_key', $setting->active_provider_key ?? 'null') === $key)>

                                        {{ $meta['label'] ?? $key }}

                                    </option>

                                @endforeach

                            </select>

                            @error('active_provider_key')

                                <div class="mt-2 text-sm text-red-600">{{ $message }}</div>

                            @enderror

                        </div>



                        @if(array_key_exists('custom', $providers))

                            @php

                                $custom = $installations['custom'] ?? null;

                                $customCreds = is_array($custom?->credentials) ? $custom->credentials : [];

                            @endphp

                            <div class="md:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">

                                <div class="text-sm font-semibold text-slate-800">Custom HTTP (Push) Kurulum</div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">

                                    <div>

                                        <label class="block text-sm font-medium text-slate-700">Base URL</label>

                                        <input name="custom_base_url" value="{{ old('custom_base_url', $customCreds['base_url'] ?? '') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2" placeholder="https://provider.example.com" />

                                        @error('custom_base_url')

                                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>

                                        @enderror

                                    </div>

                                    <div>

                                        <label class="block text-sm font-medium text-slate-700">API Key</label>

                                        <input name="custom_api_key" value="{{ old('custom_api_key', $customCreds['api_key'] ?? '') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2" />

                                        @error('custom_api_key')

                                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>

                                        @enderror

                                    </div>

                                </div>

                                <div class="text-xs text-slate-500 mt-2">Not: Bilgiler şifreli saklanır.</div>

                            </div>

                        @endif



                        <div class="md:col-span-2">

                            <label class="inline-flex items-center gap-3">

                                <input type="checkbox" name="auto_draft_enabled" value="1" @checked(old('auto_draft_enabled', $setting->auto_draft_enabled)) />

                                <span class="text-sm text-slate-700 font-medium">Otomatik taslak oluştur</span>

                            </label>

                        </div>



                        <div class="md:col-span-2">

                            <label class="inline-flex items-center gap-3">

                                <input type="checkbox" name="auto_issue_enabled" value="1" @checked(old('auto_issue_enabled', $setting->auto_issue_enabled)) />

                                <span class="text-sm text-slate-700 font-medium">Otomatik düzenle (issue)</span>

                            </label>

                        </div>



                        <div>

                            <label class="block text-sm font-medium text-slate-700">Taslak oluşturma statüsü</label>

                            <select name="draft_on_status" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2">

                                @foreach(['approved' => 'Onaylandı', 'shipped' => 'Kargoda', 'delivered' => 'Teslim'] as $k => $v)

                                    <option value="{{ $k }}" @selected(old('draft_on_status', $setting->draft_on_status) === $k)>{{ $v }}</option>

                                @endforeach

                            </select>

                        </div>



                        <div>

                            <label class="block text-sm font-medium text-slate-700">Düzenleme (issue) statüsü</label>

                            <select name="issue_on_status" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2">

                                @foreach(['shipped' => 'Kargoda', 'delivered' => 'Teslim'] as $k => $v)

                                    <option value="{{ $k }}" @selected(old('issue_on_status', $setting->issue_on_status) === $k)>{{ $v }}</option>

                                @endforeach

                            </select>

                        </div>



                        <div>

                            <label class="block text-sm font-medium text-slate-700">Prefix</label>

                            <input name="prefix" value="{{ old('prefix', $setting->prefix) }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2" />

                        </div>



                        <div>

                            <label class="block text-sm font-medium text-slate-700">Varsayılan KDV (%)</label>

                            <input name="default_vat_rate" type="number" step="0.01" min="0" max="100" value="{{ old('default_vat_rate', $setting->default_vat_rate) }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2" />

                        </div>

                    </div>



                    <div class="flex items-center gap-3">

                        <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                        <a href="{{ route('portal.settings.index') }}" class="btn btn-outline">Genel Ayarlar</a>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endsection





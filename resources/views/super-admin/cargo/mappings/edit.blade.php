@extends('layouts.super-admin')



@section('header')

    Kargo Mapping Düzenle

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-3xl space-y-6">

            <form method="POST" action="{{ route('super-admin.cargo.mappings.update', $mapping) }}" class="space-y-4">

                @csrf

                @method('PUT')



                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Pazaryeri</label>

                    <select name="marketplace_code" class="w-full">

                        @foreach($marketplaces as $mp)

                            <option value="{{ $mp->code }}" @selected($mapping->marketplace_code === $mp->code)>{{ $mp->name }} ({{ $mp->code }})</option>

                        @endforeach

                    </select>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Carrier Code</label>

                    <input type="text" name="external_carrier_code" class="w-full" value="{{ $mapping->external_carrier_code }}" required>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Sağlayıcı</label>

                    <select name="provider_key" class="w-full">

                        @foreach($providers as $key => $meta)

                            <option value="{{ $key }}" @selected($mapping->provider_key === $key)>{{ $meta['label'] ?? $key }}</option>

                        @endforeach

                    </select>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Öncelik</label>

                    <input type="number" name="priority" class="w-full" min="0" step="1" value="{{ $mapping->priority }}">

                </div>



                <div class="flex items-center gap-2">

                    <input type="hidden" name="is_active" value="0">

                    <input type="checkbox" name="is_active" value="1" @checked($mapping->is_active) class="h-4 w-4">

                    <label class="text-sm text-slate-700">Aktif</label>

                </div>



                <div class="flex items-center gap-3">

                    <button type="submit" class="btn btn-solid-accent">Güncelle</button>

                    <a href="{{ route('super-admin.cargo.mappings.index') }}" class="btn btn-outline">Vazgeç</a>

                </div>

            </form>

        </div>

    </div>

@endsection









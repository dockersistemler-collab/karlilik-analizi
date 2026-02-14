@extends('layouts.super-admin')



@section('header')

    Yeni Kargo Mapping

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-3xl space-y-6">

            <form method="POST" action="{{ route('super-admin.cargo.mappings.store') }}" class="space-y-4">

                @csrf



                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Pazaryeri</label>

                    <select name="marketplace_code" class="w-full">

                        @foreach($marketplaces as $mp)

                            <option value="{{ $mp->code }}">{{ $mp->name }} ({{ $mp->code }})</option>

                        @endforeach

                    </select>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Carrier Code</label>

                    <input type="text" name="external_carrier_code" class="w-full" required>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Sağlayıcı</label>

                    <select name="provider_key" class="w-full">

                        @foreach($providers as $key => $meta)

                            <option value="{{ $key }}">{{ $meta['label'] ?? $key }}</option>

                        @endforeach

                    </select>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Öncelik</label>

                    <input type="number" name="priority" class="w-full" min="0" step="1">

                </div>



                <div class="flex items-center gap-2">

                    <input type="hidden" name="is_active" value="0">

                    <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4">

                    <label class="text-sm text-slate-700">Aktif</label>

                </div>



                <div class="flex items-center gap-3">

                    <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                    <a href="{{ route('super-admin.cargo.mappings.index') }}" class="btn btn-outline">Vazgeç</a>

                </div>

            </form>

        </div>

    </div>

@endsection









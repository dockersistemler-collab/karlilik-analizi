@extends('layouts.super-admin')



@section('header')

    Yeni Pazaryeri

@endsection



@section('content')

    <div class="panel-card p-6 max-w-2xl">

        <form method="POST" action="{{ route('super-admin.marketplaces.store') }}" class="space-y-4" enctype="multipart/form-data">

            @csrf



            <div>

                <label class="block text-sm font-medium text-slate-700">Ad</label>

                <input type="text" name="name" value="{{ old('name') }}" class="mt-1 w-full border-slate-300 rounded-md" required>

            </div>



            <div>

                <label class="block text-sm font-medium text-slate-700">Kod</label>

                <input type="text" name="code" value="{{ old('code') }}" class="mt-1 w-full border-slate-300 rounded-md" required>

            </div>



            <div>

                <label class="block text-sm font-medium text-slate-700">API URL</label>

                <input type="url" name="api_url" value="{{ old('api_url') }}" class="mt-1 w-full border-slate-300 rounded-md">

            </div>



            <div>

                <label class="block text-sm font-medium text-slate-700">Logo URL</label>

                <input type="text" name="logo_url" value="{{ old('logo_url') }}"

                       placeholder="https://... veya /storage/marketplaces/trendyol.svg"

                       class="mt-1 w-full border-slate-300 rounded-md">

                <p class="text-xs text-slate-500 mt-1">Tam URL veya / ile başlayan yerel yol girebilirsiniz.</p>

                @error('logo_url')

                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>

                @enderror

            </div>



            <div>

                <label class="block text-sm font-medium text-slate-700">Logo Yükle</label>

                <input type="file" name="logo_file" accept="image/*" class="mt-1 w-full border-slate-300 rounded-md">

                <p class="text-xs text-slate-500 mt-1">Yüklenen logo, URL alanının üzerine yazılır.</p>

                @error('logo_file')

                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>

                @enderror

            </div>



            <label class="inline-flex items-center gap-2">

                <input type="checkbox" name="is_active" value="1" class="rounded" @checked(old('is_active', true))>

                <span class="text-sm text-slate-700">Aktif</span>

            </label>



            <div class="flex items-center gap-3">

                <button type="submit" class="btn btn-solid-accent">

                    Kaydet

                </button>

                <a href="{{ route('super-admin.marketplaces.index') }}" class="btn btn-outline-accent">

                    Vazgeç

                </a>

            </div>

        </form>

    </div>

@endsection














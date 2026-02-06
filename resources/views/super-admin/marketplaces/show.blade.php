@extends('layouts.super-admin')



@section('header')

    Pazaryeri Detayı

@endsection



@section('content')

    <div class="panel-card p-6 max-w-2xl space-y-3">

        <div>

            <p class="text-xs text-slate-500">Ad</p>

            <p class="text-sm font-medium text-slate-800">{{ $marketplace->name }}</p>

        </div>

        <div>

            <p class="text-xs text-slate-500">Kod</p>

            <p class="text-sm text-slate-700">{{ $marketplace->code }}</p>

        </div>

        <div>

            <p class="text-xs text-slate-500">API URL</p>

            <p class="text-sm text-slate-700">{{ $marketplace->api_url ?? '-' }}</p>

        </div>

        <div>

            <p class="text-xs text-slate-500">Durum</p>

            <span class="text-xs px-2 py-1 rounded {{ $marketplace->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">

                {{ $marketplace->is_active ? 'Aktif' : 'Pasif' }}

            </span>

        </div>

        <div class="pt-4">

            <a href="{{ route('super-admin.marketplaces.edit', $marketplace) }}" class="text-blue-600 hover:text-blue-900 mr-3">

                Düzenle

            </a>

            <a href="{{ route('super-admin.marketplaces.index') }}" class="btn btn-outline-accent">

                Geri Dön

            </a>

        </div>

    </div>

@endsection














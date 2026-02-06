@extends('layouts.admin')

@section('header', 'Entegrasyon Sagligi')

@section('content')
    @php
        $totalCount = count($healthSummary);
        $downCount = collect($healthSummary)->where('status', 'DOWN')->count();
        $degradedCount = collect($healthSummary)->where('status', 'DEGRADED')->count();
        $okCount = collect($healthSummary)->where('status', 'OK')->count();
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
        <div class="panel-card p-3 text-xs">
            <div class="text-slate-500">Toplam</div>
            <div class="text-lg font-semibold text-slate-800">{{ $totalCount }}</div>
        </div>
        <div class="panel-card p-3 text-xs border border-rose-100 bg-rose-50">
            <div class="text-rose-700">DOWN</div>
            <div class="text-lg font-semibold text-rose-800">{{ $downCount }}</div>
        </div>
        <div class="panel-card p-3 text-xs border border-amber-100 bg-amber-50">
            <div class="text-amber-700">DEGRADED</div>
            <div class="text-lg font-semibold text-amber-800">{{ $degradedCount }}</div>
        </div>
        <div class="panel-card p-3 text-xs border border-emerald-100 bg-emerald-50">
            <div class="text-emerald-700">OK</div>
            <div class="text-lg font-semibold text-emerald-800">{{ $okCount }}</div>
        </div>
    </div>

    <div class="panel-card p-4 mb-4">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm text-slate-600">
                    Pazaryeri entegrasyonlarinin saglik durumunu takip edin.
                </div>
                <div class="text-xs text-slate-500">
                    Son guncelleme: {{ $generatedAt->format('d.m.Y H:i') }}
                </div>
                <div class="text-xs text-slate-400">
                    Veriler bildirimler/senkron kayitlarindan turetilir; anlik degisimler icin sayfayi yenileyin.
                </div>
                @if(!empty($selectedMarketplace))
                    <div class="text-xs text-slate-500 mt-1">
                        Filtre: {{ $selectedMarketplace }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if(empty($healthSummary))
        <div class="panel-card p-6 text-center text-slate-500">
            Aktif entegrasyon bulunamadi.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach($healthSummary as $marketplace)
                @include('admin.integrations.health.partials._marketplace_card', ['marketplace' => $marketplace])
            @endforeach
        </div>
    @endif
@endsection

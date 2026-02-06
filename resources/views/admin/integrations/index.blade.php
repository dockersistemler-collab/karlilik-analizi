@extends('layouts.admin')

@section('header')
    Entegrasyonlar
@endsection

@section('content')
    <div class="panel-card p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Pazaryeri Bağlantıları</p>
                <h3 class="text-xl font-semibold text-slate-900 mt-2">Mağazalar</h3>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($marketplaces as $marketplace)
                @php
                    $credential = $marketplace->credentials->first();
                @endphp
                <div class="border border-slate-200 rounded-2xl p-4 bg-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $marketplace->name }}</p>
                            <p class="text-xs text-slate-500">Kod: {{ $marketplace->code }}</p>
                        </div>
                        <span class="panel-pill text-xs {{ $credential && $credential->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $credential && $credential->is_active ? 'Bağlı' : 'Kapalı' }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                        <span>{{ $credential ? 'API bilgileri girildi' : 'API bilgisi yok' }}</span>
                        <a href="{{ route('portal.integrations.edit', $marketplace) }}" class="text-blue-600 font-semibold">
                            Yönet
                        </a>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-400">
                        Kurulum rehberi: Yakında
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-slate-500">
                    Aktif pazaryeri bulunamadı.
                </div>
            @endforelse
        </div>
    </div>
@endsection


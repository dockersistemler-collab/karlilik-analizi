@extends('layouts.admin')

@section('header', 'Kart Guncelleme Sonucu')

@section('content')
    <div class="panel-card p-6 max-w-2xl">
        @if(session('success'))
            <div class="panel-card px-4 py-3 mb-4 border-green-200 text-green-700 bg-green-50/60">
                {{ session('success') }}
            </div>
        @elseif(session('error'))
            <div class="panel-card px-4 py-3 mb-4 border-rose-200 text-rose-700 bg-rose-50/60">
                {{ session('error') }}
            </div>
        @elseif($checkout && $checkout->status === 'completed')
            <div class="panel-card px-4 py-3 mb-4 border-green-200 text-green-700 bg-green-50/60">
                Kart guncellendi, odeme tekrar denenecek.
            </div>
        @elseif($checkout && $checkout->status === 'failed')
            <div class="panel-card px-4 py-3 mb-4 border-rose-200 text-rose-700 bg-rose-50/60">
                Kart guncellenemedi.
            </div>
        @else
            <div class="panel-card px-4 py-3 mb-4 border-slate-200 text-slate-600 bg-slate-50/60">
                Sonuc bilgisi bulunamadi.
            </div>
        @endif

        <a href="{{ route('portal.billing') }}" class="btn btn-outline-accent">Billing ekranina don</a>
    </div>
@endsection


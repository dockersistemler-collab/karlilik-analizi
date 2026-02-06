@extends('layouts.admin')

@section('header', 'Plan Yukseltme')

@section('content')
    <div class="panel-card p-6 max-w-2xl">
        @if($checkout?->status === 'completed')
            <h3 class="text-lg font-semibold text-slate-800">Islem basarili</h3>
            <p class="text-sm text-slate-600 mt-2">
                Plan guncelleme islemi tamamlandi. Yeni planiniz aktif edildi.
            </p>
        @else
            <h3 class="text-lg font-semibold text-slate-800">Odeme dogrulaniyor</h3>
            <p class="text-sm text-slate-600 mt-2">
                Odeme dogrulama islemi devam ediyor. Biraz sonra tekrar kontrol edin.
            </p>
        @endif
        <div class="mt-4">
            <a href="{{ route('portal.billing.plans') }}" class="btn btn-solid-accent">Planlara Don</a>
        </div>
    </div>
@endsection


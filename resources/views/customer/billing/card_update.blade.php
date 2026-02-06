@extends('layouts.admin')

@section('header', 'Kart Guncelle')

@section('content')
    <div class="panel-card p-6 max-w-3xl">
        <h3 class="text-lg font-semibold text-slate-800">Kart Guncelleme</h3>
        <p class="text-sm text-slate-600 mt-2">
            Kart dogrulamasi icin 1 TL provizyon alinir ve iade edilir.
        </p>

        @if(!$checkout || empty($checkout->checkout_form_content))
            <form method="POST" action="{{ route('portal.billing.card-update.initialize') }}" class="mt-4">
                @csrf
                <button type="submit" class="btn btn-solid-accent">Kart Guncellemeyi Baslat</button>
            </form>
        @else
            <div class="mt-4">
                {!! $checkout->checkout_form_content !!}
            </div>
        @endif
    </div>
@endsection


@extends('layouts.admin')



@section('header', 'Abonelik Odeme')



@section('content')

    <div class="panel-card p-6 max-w-3xl">

        <h3 class="text-lg font-semibold text-slate-800">Abonelik Odeme</h3>

        <p class="text-sm text-slate-600 mt-2">

            Odeme tamamlaninca otomatik olarak yonlendirileceksiniz.

        </p>

        <div class="mt-4">

            {!! $subscription->iyzico_checkout_form_content ?? '' !!}

        </div>

    </div>

@endsection


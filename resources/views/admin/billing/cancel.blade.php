@extends('layouts.admin')

@section('header', 'Plan Yukseltme')

@section('content')
    <div class="panel-card p-6 max-w-2xl">
        <h3 class="text-lg font-semibold text-slate-800">Islem iptal edildi</h3>
        <p class="text-sm text-slate-600 mt-2">
            Plan yukseltme islemi iptal edildi. Dilerseniz tekrar deneyebilirsiniz.
        </p>
        <div class="mt-4">
            <a href="{{ route('portal.billing.plans') }}" class="btn btn-outline-accent">Planlara Don</a>
        </div>
    </div>
@endsection


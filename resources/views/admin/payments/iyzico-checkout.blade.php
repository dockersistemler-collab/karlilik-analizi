@extends('layouts.admin')

@section('header')
    Ödeme
@endsection

@section('content')
    <div class="panel-card p-6">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-xl border border-slate-100 p-6">
                <div class="text-sm text-slate-500">Modül</div>
                <div class="mt-1 text-xl font-semibold text-slate-900">{{ $module->name }}</div>

                <div class="mt-6">
                    {!! $checkoutFormContent !!}
                </div>

                <div class="mt-6 text-sm text-slate-500">
                    Ödeme ekranı açılmazsa tarayıcı eklentilerini devre dışı bırakıp tekrar deneyin.
                </div>

                <div class="mt-6">
                    <a href="{{ route('portal.addons.index') }}" class="btn btn-outline">
                        Ek Modüllere Dön
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection



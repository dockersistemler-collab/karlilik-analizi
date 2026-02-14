@extends('layouts.super-admin')



@section('header')

    Satış Detayı

@endsection



@section('content')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 panel-card p-6">

            <div class="flex items-start justify-between gap-4">

                <div>

                    <div class="text-sm text-slate-500">Satış</div>

                    <div class="text-xl font-semibold text-slate-900">#{{ $modulePurchase->id }}</div>

                    <div class="mt-2 text-sm text-slate-700">

                        <span class="font-semibold">Durum:</span>

                        <span class="font-mono">{{ $modulePurchase->status }}</span>

                    </div>

                </div>

                <a href="{{ route('super-admin.module-purchases.index', ['status' => $modulePurchase->status]) }}" class="btn btn-outline">Geri</a>

            </div>



            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">

                    <div class="text-slate-500">Müşteri</div>

                    <div class="font-semibold text-slate-900">{{ $modulePurchase->user?->name }}</div>

                    <div class="text-slate-600">{{ $modulePurchase->user?->email }}</div>

                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">

                    <div class="text-slate-500">Modül</div>

                    <div class="font-semibold text-slate-900">{{ $modulePurchase->module?->name }}</div>

                    <div class="font-mono text-xs text-slate-600">{{ $modulePurchase->module?->code }}</div>

                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">

                    <div class="text-slate-500">Provider</div>

                    <div class="font-mono text-xs text-slate-900">{{ $modulePurchase->provider }}</div>

                    <div class="text-slate-600">{{ $modulePurchase->provider_payment_id ?: '-' }}</div>

                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">

                    <div class="text-slate-500">Tutar / Dönem</div>

                    <div class="text-slate-900">

                        @if($modulePurchase->amount !== null)

                            {{ number_format((float) $modulePurchase->amount, 2) }} {{ $modulePurchase->currency }}

                        @else

                            -

                        @endif

                        <span class="text-slate-500">/</span>

                        <span class="font-mono text-xs">{{ $modulePurchase->period }}</span>

                    </div>

                    <div class="text-slate-600 mt-1">

                        {{ $modulePurchase->starts_at?->format('Y-m-d H:i') ?? '-' }}

                        ->

                        {{ $modulePurchase->ends_at?->format('Y-m-d H:i') ?? '-' }}

                    </div>

                </div>

            </div>



            @if(is_array($modulePurchase->meta) && !empty($modulePurchase->meta))

                <div class="mt-6">

                    <div class="text-sm font-semibold text-slate-800 mb-2">Meta</div>

                    <pre class="text-xs bg-slate-900 text-slate-100 rounded-lg p-4 overflow-x-auto">{{ json_encode($modulePurchase->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

                </div>

            @endif

        </div>



        <div class="panel-card p-6">

            <div class="text-lg font-semibold text-slate-900">İşlemler</div>

            <p class="text-sm text-slate-500 mt-1">Durumu güncellemek entitlement'ı otomatik aç/kapatır.</p>



            <div class="mt-4 space-y-2">

                <form method="POST" action="{{ route('super-admin.module-purchases.mark-paid', $modulePurchase) }}">

                    @csrf

                    <button type="submit" class="btn btn-outline-accent w-full">Ödendi Onayla</button>

                </form>

                <form method="POST" action="{{ route('super-admin.module-purchases.mark-cancelled', $modulePurchase) }}">

                    @csrf

                    <button type="submit" class="btn btn-outline w-full">İptal</button>

                </form>

                <form method="POST" action="{{ route('super-admin.module-purchases.mark-refunded', $modulePurchase) }}">

                    @csrf

                    <button type="submit" class="btn btn-outline w-full">İade</button>

                </form>

            </div>

        </div>

    </div>

@endsection










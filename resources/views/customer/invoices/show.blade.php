@extends('layouts.admin')



@section('header')

    Fatura Detayı

@endsection



@section('content')

    @php

        $statusLabels = [
            'paid' => 'Ödendi',
            'pending' => 'Beklemede',
            'failed' => 'Başarısız',
            'refunded' => 'İade',
        ];

    @endphp

    <div class="panel-card p-6 max-w-3xl">

        <div class="flex items-center justify-between">

            <div>

                <p class="text-xs text-slate-500">Fatura No</p>

                <p class="text-lg font-semibold text-slate-800">{{ $invoice->invoice_number }}</p>

            </div>

            <span class="px-3 py-1 text-xs rounded {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">

                {{ $statusLabels[$invoice->status] ?? $invoice->status }}

            </span>

        </div>



        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-slate-600">

            <div>

                <p class="text-xs text-slate-500">Düzenlenme Tarihi</p>

                <p class="text-sm text-slate-800">{{ $invoice->issued_at?->format('d.m.Y') ?? '-' }}</p>

            </div>

            <div>

                <p class="text-xs text-slate-500">Ödeme Tarihi</p>

                <p class="text-sm text-slate-800">{{ $invoice->paid_at?->format('d.m.Y') ?? '-' }}</p>

            </div>

            <div>

                <p class="text-xs text-slate-500">Paket</p>

                <p class="text-sm text-slate-800">{{ $invoice->subscription?->plan?->name ?? '-' }}</p>

            </div>

            <div>

                <p class="text-xs text-slate-500">Tutar</p>

                <p class="text-sm text-slate-800">{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</p>

            </div>

        </div>



        <div class="mt-6 border-t pt-4">

            <p class="text-xs text-slate-500">Fatura Bilgileri</p>

            <p class="text-sm text-slate-800">{{ $invoice->billing_name ?? '-' }}</p>

            <p class="text-sm text-slate-600">{{ $invoice->billing_email ?? '' }}</p>

            <p class="text-sm text-slate-600">{{ $invoice->billing_address ?? '' }}</p>

        </div>



        <div class="mt-6 flex items-center gap-3">

            <a href="{{ route('portal.invoices.index') }}" class="text-slate-500 hover:text-slate-700">

                Listeye Dön

            </a>

            <a href="{{ $downloadUrl }}" class="btn btn-outline-accent">

                PDF İndir

            </a>

        </div>

    </div>

@endsection





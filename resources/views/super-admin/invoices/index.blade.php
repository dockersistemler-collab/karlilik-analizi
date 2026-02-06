@extends('layouts.super-admin')



@section('header')

    Faturalar

@endsection



@section('content')

    <div class="panel-card p-4 mb-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">

            <h3 class="text-sm font-semibold text-slate-800">Filtreler</h3>

            <a href="{{ route('super-admin.invoices.create') }}" class="btn btn-outline-accent">

                Fatura Oluştur

            </a>

        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <div>

                <label class="block text-xs text-slate-500 mb-1">Durum</label>

                <select name="status" class="w-full border-slate-300 rounded-md">

                    <option value="">Tümü</option>

                    <option value="paid" @selected(request('status') === 'paid')>Ödendi</option>

                    <option value="pending" @selected(request('status') === 'pending')>Beklemede</option>

                    <option value="failed" @selected(request('status') === 'failed')>Başarısız</option>

                    <option value="refunded" @selected(request('status') === 'refunded')>İade</option>

                </select>

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">E-posta</label>

                <input type="text" name="email" value="{{ request('email') }}" class="w-full border-slate-300 rounded-md" placeholder="kullanici@email.com">

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">Tarih (min)</label>

                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border-slate-300 rounded-md">

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">Tarih (max)</label>

                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border-slate-300 rounded-md">

            </div>

            <div class="md:col-span-5 flex items-center gap-3">

                <button type="submit" class="btn btn-solid-accent">Filtrele</button>

                <a href="{{ route('super-admin.invoices.index') }}" class="text-slate-500 hover:text-slate-700">Sıfırla</a>

                <a href="{{ route('super-admin.invoices.export', request()->query()) }}" class="btn btn-outline-accent">

                    CSV Çıktı

                </a>

            </div>

        </form>

    </div>



    <div class="panel-card p-0 overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-slate-50">

                <tr>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Fatura No</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Kullanıcı</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tarih</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tutar</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Durum</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">İşlem</th>

                </tr>

            </thead>

            <tbody class="divide-y divide-slate-200">

                @forelse($invoices as $invoice)

                    <tr>

                        <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $invoice->invoice_number }}</td>

                        <td class="px-6 py-4 text-sm text-slate-700">

                            <div class="font-medium text-slate-800">{{ $invoice->user?->name }}</div>

                            <div class="text-xs text-slate-500">{{ $invoice->user?->email }}</div>

                        </td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ $invoice->issued_at?->format('d.m.Y') }}</td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ number_format($invoice->amount, 2) }} ₺</td>

                        <td class="px-6 py-4 text-xs">

                            @php

                                $statusLabels = [

                                    'paid' => 'Ödendi',

                                    'pending' => 'Beklemede',

                                    'failed' => 'Başarısız',

                                    'refunded' => 'İade',

                                ];

                            @endphp

                            <span class="px-2 py-1 rounded {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">

                                {{ $statusLabels[$invoice->status] ?? $invoice->status }}

                            </span>

                        </td>

                        <td class="px-6 py-4 text-sm">

                            <a href="{{ route('super-admin.invoices.show', $invoice) }}" class="text-blue-600 hover:text-blue-900">

                                Görüntüle

                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="px-6 py-4 text-center text-slate-500">Fatura bulunamadı.</td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>



    <div class="mt-4">

        {{ $invoices->links() }}

    </div>

@endsection








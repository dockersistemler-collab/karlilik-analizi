@extends('layouts.admin')



@section('header')

    Faturalar

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

    <div class="panel-card p-0 overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-slate-50">

                <tr>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tarih</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Tutar</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Durum</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Dönem</th>

                </tr>

            </thead>

            <tbody class="divide-y divide-slate-200">

                @forelse($invoices as $invoice)

                    <tr>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ $invoice->issued_at?->format('d.m.Y') ?? '-' }}</td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ number_format($invoice->amount, 2) }} {{ $invoice->currency }}</td>

                        <td class="px-6 py-4 text-xs">

                            <span class="px-2 py-1 rounded {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $statusLabels[$invoice->status] ?? $invoice->status }}
                            </span>

                        </td>

                        <td class="px-6 py-4 text-sm text-slate-600">
                            <div>{{ $invoice->issued_at?->format('m.Y') ?? '-' }}</div>
                            <a href="{{ route('portal.invoices.show', $invoice) }}" class="text-blue-600 hover:text-blue-900">
                                Detay
                            </a>
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4" class="px-6 py-6 text-center text-slate-500">Fatura bulunamadı.</td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>



    <div class="mt-4">

        {{ $invoices->links() }}

    </div>

@endsection





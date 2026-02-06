@extends('layouts.admin')



@section('header')

    E-Faturalar

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-6xl mx-auto">

            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">

                    <div>

                        <label class="block text-xs font-medium text-slate-600 mb-1">Durum</label>

                        <select name="status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm">

                            <option value="">Tümü</option>

                            @foreach(['draft','issued','sent','accepted','rejected','cancelled','refunded'] as $s)

                                <option value="{{ $s }}" @selected(request('status')===$s)>{{ $s }}</option>

                            @endforeach

                        </select>

                    </div>

                    <div>

                        <label class="block text-xs font-medium text-slate-600 mb-1">Pazaryeri</label>

                        <input name="marketplace" value="{{ request('marketplace') }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="trendyol" />

                    </div>

                    <div>

                        <label class="block text-xs font-medium text-slate-600 mb-1">Sipariş No</label>

                        <input name="order_no" value="{{ request('order_no') }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" />

                    </div>

                    <div>

                        <label class="block text-xs font-medium text-slate-600 mb-1">Fatura No</label>

                        <input name="invoice_no" value="{{ request('invoice_no') }}" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" />

                    </div>

                    <div class="md:col-span-4 flex gap-2">

                        <button class="btn btn-solid-accent" type="submit">Filtrele</button>

                        <a class="btn btn-outline" href="{{ route('portal.einvoices.index') }}">Sıfırla</a>

                    </div>

                </form>



                <div class="mt-6 overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="text-left text-slate-500">

                            <tr>

                                <th class="py-2 pr-4">Fatura No</th>

                                <th class="py-2 pr-4">Sipariş</th>

                                <th class="py-2 pr-4">Pazaryeri</th>

                                <th class="py-2 pr-4">Durum</th>

                                <th class="py-2 pr-4">Tutar</th>

                                <th class="py-2 pr-4"></th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($einvoices as $inv)

                                <tr>

                                    <td class="py-3 pr-4 font-mono text-xs text-slate-800">{{ $inv->invoice_no ?? '-' }}</td>

                                    <td class="py-3 pr-4 font-mono text-xs text-slate-800">{{ $inv->marketplace_order_no   $inv->source_id }}</td>

                                    <td class="py-3 pr-4 text-slate-700">{{ $inv->marketplace ?? '-' }}</td>

                                    <td class="py-3 pr-4 text-slate-700">{{ $inv->status }}</td>

                                    <td class="py-3 pr-4 text-slate-700">{{ number_format((float) $inv->grand_total, 2) }} {{ $inv->currency }}</td>

                                    <td class="py-3 pr-4 text-right">

                                        <a class="btn btn-outline" href="{{ route('portal.einvoices.show', $inv) }}">Detay</a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="6" class="py-8 text-center text-slate-500">Kayıt bulunamadı.</td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>



                <div class="mt-6">

                    {{ $einvoices->links() }}

                </div>

            </div>

        </div>

    </div>

@endsection







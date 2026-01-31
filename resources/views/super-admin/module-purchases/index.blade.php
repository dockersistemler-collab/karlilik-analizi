@extends('layouts.super-admin')

@section('header')
    Modül Satışları
@endsection

@section('content')
    <div class="bg-white rounded-xl border border-slate-100 p-6">
        <div class="flex items-center justify-between gap-4 mb-5">
            <div>
                <div class="text-lg font-semibold text-slate-900">Satış Kayıtları</div>
                <p class="text-sm text-slate-500 mt-1">Iyzico webhook veya manuel satışlar tek tabloda toplanır.</p>
            </div>
            <a href="{{ route('super-admin.module-purchases.create') }}" class="btn btn-outline-accent">
                <i class="fa-solid fa-plus"></i>
                Manuel Satış Oluştur
            </a>
        </div>

        <div class="flex flex-wrap gap-2 mb-4">
            @foreach(['pending' => 'Bekleyen', 'paid' => 'Ödendi', 'cancelled' => 'İptal', 'refunded' => 'İade'] as $key => $label)
                <a href="{{ route('super-admin.module-purchases.index', ['status' => $key]) }}"
                   class="inline-flex items-center px-3 py-2 rounded-lg border text-sm {{ $status === $key ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-700 hover:bg-slate-50' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                <tr class="text-left text-slate-500 border-b border-slate-200">
                    <th class="py-2 pr-4">ID</th>
                    <th class="py-2 pr-4">Müşteri</th>
                    <th class="py-2 pr-4">Modül</th>
                    <th class="py-2 pr-4">Provider</th>
                    <th class="py-2 pr-4">Tutar</th>
                    <th class="py-2 pr-4">Dönem</th>
                    <th class="py-2 pr-4">Durum</th>
                    <th class="py-2 pr-4"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($purchases as $purchase)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 pr-4 text-slate-700">#{{ $purchase->id }}</td>
                        <td class="py-3 pr-4 text-slate-800">
                            {{ $purchase->user?->name }}<div class="text-xs text-slate-500">{{ $purchase->user?->email }}</div>
                        </td>
                        <td class="py-3 pr-4 text-slate-800">
                            {{ $purchase->module?->name }}
                            <div class="text-xs font-mono text-slate-500">{{ $purchase->module?->code }}</div>
                        </td>
                        <td class="py-3 pr-4 font-mono text-xs text-slate-700">{{ $purchase->provider }}</td>
                        <td class="py-3 pr-4 text-slate-700">
                            @if($purchase->amount !== null)
                                {{ number_format((float) $purchase->amount, 2) }} {{ $purchase->currency }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="py-3 pr-4 text-slate-700">{{ $purchase->period }}</td>
                        <td class="py-3 pr-4">
                            <span class="inline-flex px-2 py-1 rounded-md text-xs border border-slate-200 bg-slate-50 text-slate-700">{{ $purchase->status }}</span>
                        </td>
                        <td class="py-3 pr-4 text-right">
                            <a class="btn btn-outline" href="{{ route('super-admin.module-purchases.show', $purchase) }}">Detay</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-6 text-center text-slate-500">Kayıt bulunamadı.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $purchases->links() }}
        </div>
    </div>
@endsection


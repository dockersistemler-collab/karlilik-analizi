@extends('layouts.super-admin')

@section('header')
    Abonelik Paketleri
@endsection

@section('content')
    <div class="mb-6">
        <a href="{{ route('super-admin.plans.create') }}" class="btn btn-solid-accent">
            Yeni Paket
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Paket</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Fiyat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Limitler</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($plans as $plan)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $plan->name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ number_format($plan->price, 2) }} ₺</td>
                        <td class="px-6 py-4 text-xs text-slate-600">
                            Ürün: {{ $plan->max_products === 0 ? 'Sınırsız' : $plan->max_products }},
                            Pazaryeri: {{ $plan->max_marketplaces === 0 ? 'Sınırsız' : $plan->max_marketplaces }},
                            Sipariş: {{ $plan->max_orders_per_month === 0 ? 'Sınırsız' : $plan->max_orders_per_month }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded text-xs {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $plan->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="{{ route('super-admin.plans.edit', $plan) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                                Düzenle
                            </a>
                            <form action="{{ route('super-admin.plans.destroy', $plan) }}" method="POST" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                    Sil
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-slate-500">Paket bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

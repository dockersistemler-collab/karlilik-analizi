@extends('layouts.admin')

@section('header')
    Abonelik Geçmişi
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paket</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Başlangıç</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bitiş</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ücret</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($subscriptions as $subscription)
                    <tr>
                        <td class="px-6 py-4 text-sm font-medium text-gray-800">{{ $subscription->plan?->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $subscription->starts_at?->format('d.m.Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $subscription->ends_at?->format('d.m.Y') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($subscription->amount, 2) }} ₺</td>
                        <td class="px-6 py-4 text-xs">
                            <span class="px-2 py-1 rounded {{ $subscription->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Geçmiş abonelik bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $subscriptions->links() }}
    </div>
@endsection

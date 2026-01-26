@extends('layouts.super-admin')

@section('header')
    Genel Özet
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-slate-500">Toplam Kullanıcı</p>
            <p class="text-2xl font-semibold text-slate-800">{{ $stats['total_users'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Aktif: {{ $stats['active_users'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-slate-500">Aktif Abonelik</p>
            <p class="text-2xl font-semibold text-slate-800">{{ $stats['active_subscriptions'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Toplam paket: {{ $stats['total_plans'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs text-slate-500">Toplam Gelir</p>
            <p class="text-2xl font-semibold text-slate-800">{{ number_format($stats['total_revenue'], 2) }} ₺</p>
            <p class="text-xs text-slate-400 mt-1">Toplam sipariş: {{ $stats['total_orders'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Son Kullanıcılar</h3>
            <div class="space-y-3">
                @forelse($latest_users as $user)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->email }}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $user->is_active ? 'Aktif' : 'Pasif' }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Kayıt bulunamadı.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-sm font-semibold text-slate-800 mb-4">Son Abonelikler</h3>
            <div class="space-y-3">
                @forelse($latest_subscriptions as $subscription)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $subscription->user?->name }}</p>
                            <p class="text-xs text-slate-500">{{ $subscription->plan?->name }}</p>
                        </div>
                        <span class="text-xs text-slate-600">{{ number_format($subscription->amount, 2) }} ₺</span>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Abonelik bulunamadı.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

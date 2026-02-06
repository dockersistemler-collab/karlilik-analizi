@extends('layouts.super-admin')



@section('header')

    Genel Özet

@endsection



@section('content')

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 mb-6">

        <div class="panel-card p-5">

            <p class="text-xs text-slate-500">Kullanıcılar</p>

            <p class="text-2xl font-semibold text-slate-800">{{ $stats['total_users'] }}</p>

            <div class="text-xs text-slate-400 mt-2">Aktif: {{ $stats['active_users'] }}</div>

            <div class="text-xs text-slate-400">Son 7 gün: {{ $stats['new_users_7d'] }}</div>

        </div>

        <div class="panel-card p-5">

            <p class="text-xs text-slate-500">Abonelikler</p>

            <p class="text-2xl font-semibold text-slate-800">{{ $stats['active_subscriptions'] }}</p>

            <div class="text-xs text-slate-400 mt-2">Toplam plan: {{ $stats['total_plans'] }}</div>

            <div class="text-xs text-slate-400">Son 7 gün: {{ $stats['new_subscriptions_7d'] }}</div>

        </div>

        <div class="panel-card p-5">

            <p class="text-xs text-slate-500">Gelir</p>

            <p class="text-2xl font-semibold text-slate-800">{{ number_format($stats['total_revenue'], 2) }} ₺</p>

            <div class="text-xs text-slate-400 mt-2">Son 30 gün: {{ number_format($stats['revenue_30d'], 2) }} ₺</div>

        </div>

        <div class="panel-card p-5">

            <p class="text-xs text-slate-500">Siparişler</p>

            <p class="text-2xl font-semibold text-slate-800">{{ $stats['total_orders'] }}</p>

            <div class="text-xs text-slate-400 mt-2">Son 30 gün: {{ $stats['orders_30d'] }}</div>

        </div>

        <div class="panel-card p-5">

            <p class="text-xs text-slate-500">Ürünler</p>

            <p class="text-2xl font-semibold text-slate-800">{{ $stats['total_products'] }}</p>

            <div class="text-xs text-slate-400 mt-2">Toplam ürün kaydı</div>

        </div>

        <div class="panel-card p-5">

            <p class="text-xs text-slate-500">Sistem</p>

            <p class="text-2xl font-semibold text-slate-800">{{ $stats['total_plans'] }}</p>

            <div class="text-xs text-slate-400 mt-2">Aktif plan sayısı</div>

        </div>

    </div>



    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

        <div class="panel-card p-5">

            <h3 class="text-sm font-semibold text-slate-800 mb-4">Hızlı Aksiyonlar</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                <a href="{{ route('super-admin.users.index') }}" class="btn btn-outline-accent">Kullanıcılar</a>

                <a href="{{ route('super-admin.subscriptions.index') }}" class="btn btn-outline-accent">Abonelikler</a>

                <a href="{{ route('super-admin.invoices.index') }}" class="btn btn-outline-accent">Faturalar</a>

                <a href="{{ route('super-admin.mail-logs.index') }}" class="btn btn-outline-accent">Mail Logları</a>

                <a href="{{ route('super-admin.settings.index') }}" class="btn btn-outline-accent">Ayarlar</a>

                <a href="{{ route('super-admin.reports.index') }}" class="btn btn-outline-accent">Raporlar</a>

            </div>

        </div>



        <div class="panel-card p-5">

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



        <div class="panel-card p-5">

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









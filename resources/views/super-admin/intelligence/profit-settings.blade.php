@extends('layouts.super-admin')

@section('header')
    Profit Ayarlari
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="text-lg font-semibold text-slate-900">{{ $module->name }}</div>
            <div class="text-sm text-slate-500">Kod: {{ $module->code }}</div>
        </div>
        <form method="POST" action="{{ route('super-admin.intelligence.modules.toggle', ['code' => $module->code]) }}">
            @csrf
            <button class="btn {{ $module->is_active ? 'btn-outline' : 'btn-solid-accent' }}">{{ $module->is_active ? 'Pasif Yap' : 'Aktif Et' }}</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Toplam Snapshot</div><div class="text-2xl font-semibold">{{ number_format($stats['snapshots']) }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Masraf Profili</div><div class="text-2xl font-semibold">{{ number_format($stats['profiles']) }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Aktif Musteri</div><div class="text-2xl font-semibold">{{ number_format($stats['active_clients']) }}</div></div>
    </div>

    <div class="panel-card p-5">
        <h3 class="font-semibold mb-3">Tenant Bazli Ozet</h3>
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="px-3 py-2">Tenant</th>
                        <th class="px-3 py-2">Siparis</th>
                        <th class="px-3 py-2">Toplam Net Kar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topTenants as $row)
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-2">{{ $row->tenant_id }}</td>
                            <td class="px-3 py-2">{{ number_format((int) $row->total_orders) }}</td>
                            <td class="px-3 py-2">{{ number_format((float) $row->total_profit, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-4 text-center text-slate-500">Kayit yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

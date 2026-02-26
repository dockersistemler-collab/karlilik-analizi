@extends('layouts.super-admin')

@section('header')
    Risk Profilleri
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
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Toplam Profil</div><div class="text-2xl font-semibold">{{ number_format($stats['profiles']) }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Toplam Risk Skor</div><div class="text-2xl font-semibold">{{ number_format($stats['risk_scores']) }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Aktif Musteri</div><div class="text-2xl font-semibold">{{ number_format($stats['active_clients']) }}</div></div>
    </div>

    <div class="panel-card p-5">
        <form method="GET" class="mb-3 flex gap-2">
            <input type="text" name="marketplace" value="{{ request('marketplace') }}" placeholder="marketplace kodu" class="border border-slate-200 rounded px-3 py-2">
            <button class="btn btn-solid-accent">Filtrele</button>
            <a href="{{ route('super-admin.intelligence.risk-profiles') }}" class="btn btn-outline">Temizle</a>
        </form>
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="px-3 py-2">ID</th>
                        <th class="px-3 py-2">Tenant</th>
                        <th class="px-3 py-2">User</th>
                        <th class="px-3 py-2">Marketplace</th>
                        <th class="px-3 py-2">Ad</th>
                        <th class="px-3 py-2">Default</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profiles as $row)
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-2">{{ $row->id }}</td>
                            <td class="px-3 py-2">{{ $row->tenant_id }}</td>
                            <td class="px-3 py-2">{{ $row->user_id }}</td>
                            <td class="px-3 py-2 uppercase">{{ $row->marketplace }}</td>
                            <td class="px-3 py-2">{{ $row->name }}</td>
                            <td class="px-3 py-2">{{ $row->is_default ? 'Evet' : 'Hayir' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">Kayit yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $profiles->links() }}</div>
    </div>
</div>
@endsection

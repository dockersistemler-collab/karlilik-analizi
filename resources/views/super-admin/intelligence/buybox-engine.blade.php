@extends('layouts.super-admin')

@section('header')
    BuyBox Engine (FAZ 1-2)
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

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Toplam Snapshot</div><div class="text-2xl font-semibold">{{ number_format($stats['snapshots']) }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Toplam Score</div><div class="text-2xl font-semibold">{{ number_format($stats['scores']) }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Scoring Profile</div><div class="text-2xl font-semibold">{{ number_format($stats['profiles']) }}</div></div>
        <div class="panel-card p-4"><div class="text-xs text-slate-500">Aktif Musteri</div><div class="text-2xl font-semibold">{{ number_format($stats['active_clients']) }}</div></div>
    </div>

    <div class="panel-card p-5">
        <h3 class="font-semibold mb-3">FAZ Kapsami</h3>
        <ul class="text-sm text-slate-700 space-y-2">
            <li>FAZ 1: BuyBox snapshot toplama + CSV import/export</li>
            <li>FAZ 2: BuyBox score hesaplama + scoring profile</li>
            <li>FAZ 3: Action Engine tarafinda aksiyon onerileri</li>
        </ul>
    </div>

    <div class="panel-card p-5">
        <h3 class="font-semibold mb-3">Son BuyBox Score Kayitlari</h3>
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="px-3 py-2">Tarih</th>
                        <th class="px-3 py-2">Tenant</th>
                        <th class="px-3 py-2">Marketplace</th>
                        <th class="px-3 py-2">SKU</th>
                        <th class="px-3 py-2">Score</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestScores as $row)
                        <tr class="border-b border-slate-100">
                            <td class="px-3 py-2">{{ optional($row->date)->format('d.m.Y') }}</td>
                            <td class="px-3 py-2">{{ $row->tenant_id }}</td>
                            <td class="px-3 py-2 uppercase">{{ $row->marketplace }}</td>
                            <td class="px-3 py-2">{{ $row->sku }}</td>
                            <td class="px-3 py-2">{{ $row->buybox_score }}</td>
                            <td class="px-3 py-2 uppercase">{{ $row->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-4 text-center text-slate-500">Kayit yok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

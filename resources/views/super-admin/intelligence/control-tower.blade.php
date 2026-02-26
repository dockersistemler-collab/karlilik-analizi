@extends('layouts.super-admin')

@section('header')
    Kontrol Kulesi
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <div class="text-xs text-slate-500 uppercase">Modul</div>
            <div class="text-lg font-semibold">{{ $module->name }}</div>
            <div class="text-xs text-slate-500">{{ $module->description }}</div>
        </div>
        <form method="POST" action="{{ route('super-admin.intelligence.modules.toggle', ['code' => $module->code]) }}">
            @csrf
            <button class="btn {{ $module->is_active ? 'btn-outline' : 'btn-solid-accent' }}">
                {{ $module->is_active ? 'Pasif Yap' : 'Aktif Yap' }}
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Aktif Müşteriler</div>
            <div class="text-2xl font-semibold">{{ (int) $stats['active_clients'] }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Snapshotlar</div>
            <div class="text-2xl font-semibold">{{ (int) $stats['snapshots'] }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Sinyaller</div>
            <div class="text-2xl font-semibold">{{ (int) $stats['signals'] }}</div>
        </div>
        <div class="panel-card p-4">
            <div class="text-xs text-slate-500">Açık Kritik</div>
            <div class="text-2xl font-semibold text-rose-600">{{ (int) $stats['critical_open'] }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="panel-card p-4">
            <h3 class="font-semibold mb-2">Client Atama</h3>
            <form method="POST" action="{{ route('super-admin.modules.assign', $module) }}" class="space-y-2">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <select name="user_id" class="border border-slate-200 rounded px-3 py-2" required>
                        <option value="">Client seçin</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->email }})</option>
                        @endforeach
                    </select>
                    <select name="status" class="border border-slate-200 rounded px-3 py-2" required>
                        <option value="active">active</option>
                        <option value="inactive">inactive</option>
                        <option value="expired">expired</option>
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <input type="date" name="starts_at" class="border border-slate-200 rounded px-3 py-2">
                    <input type="date" name="ends_at" class="border border-slate-200 rounded px-3 py-2">
                </div>
                <button class="btn btn-solid-accent">Client'a Modül Ata</button>
            </form>

            <div class="overflow-auto mt-4">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="px-3 py-2">Client</th>
                            <th class="px-3 py-2">Durum</th>
                            <th class="px-3 py-2">Başlangıç</th>
                            <th class="px-3 py-2">Bitiş</th>
                            <th class="px-3 py-2">Aksiyon</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($userModules as $um)
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-2">
                                    <div class="font-medium">{{ $um->user?->name ?? ('#'.$um->user_id) }}</div>
                                    <div class="text-xs text-slate-500">{{ $um->user?->email }}</div>
                                </td>
                                <td class="px-3 py-2 uppercase">{{ $um->status }}</td>
                                <td class="px-3 py-2">{{ optional($um->starts_at)->format('d.m.Y') ?: '-' }}</td>
                                <td class="px-3 py-2">{{ optional($um->ends_at)->format('d.m.Y') ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex gap-1">
                                        <form method="POST" action="{{ route('super-admin.user-modules.toggle', $um) }}">
                                            @csrf
                                            <button class="btn btn-outline px-2 py-1 text-xs">Toggle</button>
                                        </form>
                                        <form method="POST" action="{{ route('super-admin.user-modules.destroy', $um) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline px-2 py-1 text-xs">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-4 text-center text-slate-500">Atama yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-card p-4">
            <h3 class="font-semibold mb-2">Son Snapshotlar</h3>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="px-3 py-2">Tarih</th>
                            <th class="px-3 py-2">Kiracı</th>
                            <th class="px-3 py-2">Aralık</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestSnapshots as $row)
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-2">{{ optional($row->date)->format('d.m.Y') }}</td>
                                <td class="px-3 py-2">{{ (int) $row->tenant_id }}</td>
                                <td class="px-3 py-2">{{ data_get($row->payload, 'meta.range_days', '-') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-4 text-center text-slate-500">Kayit yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel-card p-4">
            <h3 class="font-semibold mb-2">Son Sinyaller</h3>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="px-3 py-2">Tarih</th>
                            <th class="px-3 py-2">Tür</th>
                            <th class="px-3 py-2">Seviye</th>
                            <th class="px-3 py-2">Çözüldü</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestSignals as $row)
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-2">{{ optional($row->date)->format('d.m.Y') }}</td>
                                <td class="px-3 py-2">{{ $row->type }}</td>
                                <td class="px-3 py-2 uppercase">{{ $row->severity }}</td>
                                <td class="px-3 py-2">{{ $row->is_resolved ? 'Evet' : 'Hayır' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-4 text-center text-slate-500">Kayit yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

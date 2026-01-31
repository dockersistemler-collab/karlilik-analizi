@extends('layouts.super-admin')

@section('header')
    Modül Düzenle
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-100 p-6">
            <form method="POST" action="{{ route('super-admin.modules.update', $module) }}" class="space-y-4">
                @csrf
                @method('PUT')
                @include('super-admin.modules.partials.form', ['module' => $module])
                <div class="pt-2 flex gap-3">
                    <button type="submit" class="btn btn-outline-accent">Kaydet</button>
                </div>
            </form>

            <form method="POST" action="{{ route('super-admin.modules.destroy', $module) }}" class="mt-4">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline">Sil</button>
            </form>
        </div>

        <div class="bg-white rounded-xl border border-slate-100 p-6">
            <div class="text-lg font-semibold text-slate-900">Kullanıcıya Ata</div>
            <p class="text-sm text-slate-500 mt-1">Bu modülü seçili kullanıcı için aktif/pasif yapabilirsiniz.</p>

            <form method="POST" action="{{ route('super-admin.modules.assign', $module) }}" class="space-y-3 mt-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kullanıcı</label>
                    <select name="user_id" class="w-full">
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }} ({{ $client->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Durum</label>
                        <select name="status" class="w-full">
                            <option value="active">active</option>
                            <option value="inactive">inactive</option>
                            <option value="expired">expired</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Başlangıç</label>
                        <input type="date" name="starts_at" class="w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Bitiş</label>
                        <input type="date" name="ends_at" class="w-full">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Meta (JSON)</label>
                    <textarea name="meta" rows="3" class="w-full font-mono text-xs" placeholder='{"note":"..."}'></textarea>
                </div>
                <div class="pt-2">
                    <button type="submit" class="btn btn-outline-accent">Ata / Güncelle</button>
                </div>
            </form>

            <div class="mt-8">
                <div class="text-sm font-semibold text-slate-800">Mevcut Atamalar</div>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-4">Kullanıcı</th>
                                <th class="py-2 pr-4">Durum</th>
                                <th class="py-2 pr-4">Bitiş</th>
                                <th class="py-2 pr-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($userModules as $um)
                                <tr class="border-b border-slate-100">
                                    <td class="py-3 pr-4 text-slate-800">{{ $um->user?->name }} ({{ $um->user?->email }})</td>
                                    <td class="py-3 pr-4 font-mono text-xs text-slate-700">{{ $um->status }}</td>
                                    <td class="py-3 pr-4 text-slate-700">{{ $um->ends_at?->format('Y-m-d') ?? '-' }}</td>
                                    <td class="py-3 pr-4 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('super-admin.user-modules.toggle', $um) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline">Toggle</button>
                                        </form>
                                        <form method="POST" action="{{ route('super-admin.user-modules.destroy', $um) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline">Kaldır</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-5 text-center text-slate-500">Henüz atama yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection


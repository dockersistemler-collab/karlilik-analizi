@extends('layouts.admin')

@section('header')
    Marketplace Hesapları
@endsection

@section('content')
    <div class="panel-card p-6 mb-6">
        <form method="POST" action="{{ route('portal.profitability.accounts.store') }}" class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            @csrf
            <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-500 mb-1">Marketplace</label>
                <select name="marketplace" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" required>
                    @foreach($marketplaces as $marketplace)
                        <option value="{{ $marketplace->code }}">{{ $marketplace->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-500 mb-1">Mağaza Adı</label>
                <input type="text" name="store_name" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" placeholder="Örn: Ana mağaza">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-slate-500 mb-1">Credentials (JSON)</label>
                <textarea name="credentials_json" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" placeholder='{"token":"..."}'></textarea>
                <p class="text-xs text-slate-400 mt-1">Örnek (Trendyol): {"api_key":"...","api_secret":"...","supplier_id":"...","is_test":true,"base_url":"https://api.trendyol.com/sapigw/suppliers"}</p>
            </div>
            <div class="lg:col-span-1">
                <label class="block text-xs font-medium text-slate-500 mb-1">Durum</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                    <option value="active">Aktif</option>
                    <option value="inactive">Pasif</option>
                </select>
                <button type="submit" class="btn btn-solid-accent w-full mt-3">Ekle</button>
            </div>
        </form>
    </div>

    <div class="panel-card p-6">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-xs uppercase text-slate-400">
                    <tr>
                        <th class="text-left py-2 pr-4">Marketplace</th>
                        <th class="text-left py-2 pr-4">Mağaza</th>
                        <th class="text-left py-2 pr-4">Durum</th>
                        <th class="text-left py-2 pr-4">Son Senkron</th>
                        <th class="text-right py-2">Aksiyon</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($accounts as $account)
                        <tr>
                            <td class="py-3 pr-4 text-slate-600">{{ strtoupper($account->marketplace) }}</td>
                            <td class="py-3 pr-4 text-slate-600">{{ $account->store_name ?: '-' }}</td>
                            <td class="py-3 pr-4">
                                <span class="panel-pill text-xs {{ $account->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $account->status === 'active' ? 'Aktif' : 'Pasif' }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 text-slate-600">
                                {{ $account->last_synced_at ? $account->last_synced_at->format('d.m.Y H:i') : '-' }}
                            </td>
                            <td class="py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('portal.profitability.accounts.test', $account) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-accent text-xs">Test</button>
                                    </form>
                                    <a href="{{ route('portal.profitability.accounts.edit', $account) }}" class="btn btn-outline text-xs">Düzenle</a>
                                    <form method="POST" action="{{ route('portal.profitability.accounts.destroy', $account) }}" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline text-xs">Sil</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-4 text-center text-slate-500">Kayıt bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

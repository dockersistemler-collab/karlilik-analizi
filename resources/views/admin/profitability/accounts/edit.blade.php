@extends('layouts.admin')

@section('header')
    Marketplace Hesabı Düzenle
@endsection

@section('content')
    <div class="panel-card p-6">
        <form method="POST" action="{{ route('portal.profitability.accounts.update', $account) }}" class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Marketplace</label>
                <select name="marketplace" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" required>
                    @foreach($marketplaces as $marketplace)
                        <option value="{{ $marketplace->code }}" @selected($account->marketplace === $marketplace->code)>
                            {{ $marketplace->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Mağaza Adı</label>
                <input type="text" name="store_name" value="{{ $account->store_name }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
            </div>

            <div class="lg:col-span-2">
                <label class="block text-xs font-medium text-slate-500 mb-1">Credentials (JSON)</label>
                <textarea name="credentials_json" rows="4" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white" placeholder='{"token":"..."}'></textarea>
                <p class="text-xs text-slate-400 mt-1">Boş bırakılırsa mevcut bilgiler korunur.</p>
                <p class="text-xs text-slate-400">Örnek (Trendyol): {"api_key":"...","api_secret":"...","supplier_id":"...","is_test":true,"base_url":"https://api.trendyol.com/sapigw/suppliers"}</p>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Durum</label>
                <select name="status" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">
                    <option value="active" @selected($account->status === 'active')>Aktif</option>
                    <option value="inactive" @selected($account->status === 'inactive')>Pasif</option>
                </select>
            </div>

            <div class="flex items-center gap-2 lg:justify-end">
                <a href="{{ route('portal.profitability.accounts.index') }}" class="btn btn-outline">Vazgeç</a>
                <button type="submit" class="btn btn-solid-accent">Kaydet</button>
            </div>
        </form>
    </div>
@endsection

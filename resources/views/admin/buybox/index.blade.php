@extends('layouts.admin')

@section('header')
    BuyBox Engine
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-3 flex gap-2">
        <a href="{{ route('portal.buybox.index') }}" class="btn btn-outline">Snapshots</a>
        <a href="{{ route('portal.buybox.scores') }}" class="btn btn-outline">Scores</a>
        <a href="{{ route('portal.buybox.profiles') }}" class="btn btn-outline">Profiles</a>
    </div>

    <div class="panel-card p-4">
        <form method="GET" action="{{ route('portal.buybox.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-2">
            <input type="date" name="date" value="{{ request('date') }}" class="border border-slate-200 rounded px-3 py-2">
            <select name="marketplace" class="border border-slate-200 rounded px-3 py-2">
                <option value="">Tum pazaryerleri</option>
                @foreach(['trendyol','hepsiburada','amazon','n11'] as $market)
                    <option value="{{ $market }}" @selected(request('marketplace') === $market)>{{ strtoupper($market) }}</option>
                @endforeach
            </select>
            <input type="text" name="sku" value="{{ request('sku') }}" placeholder="SKU" class="border border-slate-200 rounded px-3 py-2">
            <select name="is_winning" class="border border-slate-200 rounded px-3 py-2">
                <option value="">Hepsi</option>
                <option value="1" @selected(request('is_winning') === '1')>Biz kazaniyoruz</option>
                <option value="0" @selected(request('is_winning') === '0')>Biz kazanmiyoruz</option>
            </select>
            <div class="flex gap-2">
                <button class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('portal.buybox.index') }}" class="btn btn-outline">Temizle</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="panel-card p-4">
            <div class="text-sm font-semibold mb-2">CSV Import</div>
            <form method="POST" action="{{ route('portal.buybox.import-csv') }}" enctype="multipart/form-data" class="space-y-2">
                @csrf
                <input type="file" name="file" accept=".csv,.txt" class="w-full border border-slate-200 rounded px-2 py-2" required>
                <p class="text-xs text-slate-500">Kolonlar: date, marketplace, sku, is_winning, position_rank, our_price, competitor_best_price, store_score, stock_available</p>
                <button class="btn btn-solid-accent w-full">Yukle</button>
            </form>
        </div>
        <div class="panel-card p-4">
            <div class="text-sm font-semibold mb-2">Export CSV</div>
            <a href="{{ route('portal.buybox.export-csv', request()->query()) }}" class="btn btn-outline w-full">Filtreli CSV indir</a>
        </div>
        <div class="panel-card p-4">
            <div class="text-sm font-semibold mb-2">Gunluk Topla</div>
            <form method="POST" action="{{ route('portal.buybox.collect') }}" class="space-y-2">
                @csrf
                <input type="date" name="date" value="{{ request('date', now()->subDay()->toDateString()) }}" class="w-full border border-slate-200 rounded px-2 py-2">
                <button class="btn btn-solid-accent w-full">Job Kuyruga Gonder</button>
            </form>
        </div>
    </div>

    <div class="panel-card p-4">
        <div class="overflow-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b">
                        <th class="px-3 py-2">Tarih</th>
                        <th class="px-3 py-2">Pazaryeri</th>
                        <th class="px-3 py-2">SKU</th>
                        <th class="px-3 py-2">Kazanan</th>
                        <th class="px-3 py-2">Sira</th>
                        <th class="px-3 py-2">Fiyat</th>
                        <th class="px-3 py-2">Rakip En Iyi</th>
                        <th class="px-3 py-2">Store Score</th>
                        <th class="px-3 py-2">Stok</th>
                        <th class="px-3 py-2">Aksiyon</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($snapshots as $row)
                    <tr class="border-b border-slate-100">
                        <td class="px-3 py-2">{{ optional($row->date)->format('d.m.Y') }}</td>
                        <td class="px-3 py-2 uppercase">{{ $row->marketplace }}</td>
                        <td class="px-3 py-2">{{ $row->sku }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex px-2 py-1 rounded text-xs {{ $row->is_winning ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                {{ $row->is_winning ? 'EVET' : 'HAYIR' }}
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ $row->position_rank ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $row->our_price ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $row->competitor_best_price ?? '-' }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex px-2 py-1 rounded text-xs bg-blue-100 text-blue-700">
                                {{ $row->store_score ?? '-' }}
                            </span>
                        </td>
                        <td class="px-3 py-2">{{ $row->stock_available ?? '-' }}</td>
                        <td class="px-3 py-2">
                            <div class="flex flex-wrap gap-2">
                                <a
                                    href="{{ route('portal.buybox.detail', ['marketplace' => $row->marketplace, 'sku' => $row->sku]) }}"
                                    class="btn btn-outline px-2 py-1 text-xs"
                                >Detay</a>
                                <form method="POST" action="{{ route('portal.buybox.suggest-actions') }}">
                                    @csrf
                                    <input type="hidden" name="date" value="{{ optional($row->date)->toDateString() }}">
                                    <input type="hidden" name="marketplace" value="{{ $row->marketplace }}">
                                    <input type="hidden" name="sku" value="{{ $row->sku }}">
                                    <button class="btn btn-solid-accent px-2 py-1 text-xs">Aksiyon Oner</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-3 py-4 text-center text-slate-500">Kayit yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $snapshots->links() }}</div>
    </div>
</div>
@endsection

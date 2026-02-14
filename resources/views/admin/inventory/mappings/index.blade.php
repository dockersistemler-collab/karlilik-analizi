@extends('layouts.admin')

@section('title', 'Stok - Listing Eslestirme')

@section('content')
    <div class="space-y-6">
        <div class="panel-card p-6">
            <h1 class="text-lg font-semibold text-slate-900 mb-4">Stok - Listing Eslestirme</h1>
            <form method="POST" action="{{ route('portal.inventory.admin.mappings.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Marketplace Account</label>
                    <select name="marketplace_account_id" class="w-full" required>
                        <option value="">Seciniz</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->store_name ?: $account->connector_key ?: $account->marketplace }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Urun</label>
                    <select name="product_id" class="w-full" required>
                        <option value="">Seciniz</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">External Listing ID</label>
                    <input type="text" name="external_listing_id" class="w-full" value="{{ old('external_listing_id') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">External SKU</label>
                    <input type="text" name="external_sku" class="w-full" value="{{ old('external_sku') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">External Barkod</label>
                    <input type="text" name="external_barcode" class="w-full" value="{{ old('external_barcode') }}">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Son Bilinen Pazaryeri Stogu</label>
                    <input type="number" min="0" name="last_known_market_stock" class="w-full" value="{{ old('last_known_market_stock') }}">
                </div>
                <div class="md:col-span-2 flex items-center gap-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="sync_enabled" value="1" checked>
                        <span class="text-sm text-slate-700">Sync aktif</span>
                    </label>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="btn btn-primary">Eslestirme Ekle</button>
                </div>
            </form>
        </div>

        <div class="panel-card p-6">
            <h2 class="text-base font-semibold text-slate-900 mb-3">Mevcut Eslestirmeler</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-slate-500 border-b border-slate-200">
                        <tr>
                            <th class="py-2 pr-4">Account</th>
                            <th class="py-2 pr-4">Urun</th>
                            <th class="py-2 pr-4">Listing ID</th>
                            <th class="py-2 pr-4">SKU/Barkod</th>
                            <th class="py-2 pr-4">Sync</th>
                            <th class="py-2 pr-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($listings as $listing)
                            <tr class="border-b border-slate-100">
                                <td class="py-3 pr-4 text-slate-700">{{ $listing->account?->store_name ?: $listing->account?->connector_key ?: $listing->account?->marketplace }}</td>
                                <td class="py-3 pr-4 font-semibold text-slate-900">{{ $listing->product?->name ?: '-' }}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ $listing->external_listing_id ?: '-' }}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ $listing->external_sku ?: '-' }} / {{ $listing->external_barcode ?: '-' }}</td>
                                <td class="py-3 pr-4 text-slate-700">{{ $listing->sync_enabled ? 'Acik' : 'Kapali' }}</td>
                                <td class="py-3 pr-4 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <form method="POST" action="{{ route('portal.inventory.admin.mappings.update', $listing) }}" class="inline-flex items-center gap-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="marketplace_account_id" value="{{ $listing->marketplace_account_id }}">
                                            <input type="hidden" name="product_id" value="{{ $listing->product_id }}">
                                            <input type="hidden" name="external_listing_id" value="{{ $listing->external_listing_id }}">
                                            <input type="hidden" name="external_sku" value="{{ $listing->external_sku }}">
                                            <input type="hidden" name="external_barcode" value="{{ $listing->external_barcode }}">
                                            <input type="hidden" name="last_known_market_stock" value="{{ $listing->last_known_market_stock }}">
                                            <input type="hidden" name="sync_enabled" value="{{ $listing->sync_enabled ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-outline">{{ $listing->sync_enabled ? 'Sync Kapat' : 'Sync Ac' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('portal.inventory.admin.mappings.destroy', $listing) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline">Sil</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-slate-500">Kayit yok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $listings->links() }}
            </div>
        </div>
    </div>
@endsection

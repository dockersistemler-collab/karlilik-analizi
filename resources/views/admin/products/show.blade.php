@extends('layouts.admin')

@section('header')
    Ürün Detay
@endsection

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.products.edit', $product) }}" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-4 rounded">
        <i class="fas fa-edit mr-2"></i> Düzenle
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Ürün Bilgileri</h3>
        @if($product->display_image_url)
            <img src="{{ $product->display_image_url }}" alt="{{ $product->name }}" class="w-full h-48 object-cover rounded mb-4">
        @endif
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">SKU</dt>
                <dd><code class="bg-gray-100 px-2 py-1 rounded">{{ $product->sku }}</code></dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Barkod</dt>
                <dd>{{ $product->barcode ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Ürün Adı</dt>
                <dd class="font-semibold">{{ $product->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Marka</dt>
                <dd>{{ $product->brand ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Kategori</dt>
                <dd>{{ $product->category ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Fiyat ve Stok</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">Satış Fiyatı</dt>
                <dd class="text-2xl font-bold text-green-600">{{ number_format($product->price, 2) }} {{ $product->currency }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Maliyet Fiyatı</dt>
                <dd>{{ $product->cost_price ? number_format($product->cost_price, 2) . ' ' . $product->currency : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Kar Marjı</dt>
                <dd>
                    @if($product->cost_price && $product->price > 0)
                        {{ number_format((($product->price - $product->cost_price) / $product->price) * 100, 2) }}%
                    @else
                        -
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Stok Miktarı</dt>
                <dd>
                    <span class="px-2 py-1 text-sm rounded {{ $product->stock_quantity > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $product->stock_quantity }} adet
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Ağırlık</dt>
                <dd>{{ $product->weight ? $product->weight . ' KG' : '-' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Durum</dt>
                <dd>
                    <span class="px-2 py-1 text-xs rounded {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $product->is_active ? 'Aktif' : 'Pasif' }}
                    </span>
                </dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Açıklama</h3>
        @if($product->description)
            <div class="text-sm text-gray-600 rich-content">{!! $product->description !!}</div>
        @else
            <p class="text-sm text-gray-600">Aciklama bulunmuyor</p>
        @endif
    </div>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b flex items-center justify-between">
        <h3 class="text-lg font-semibold">Pazaryerleri</h3>
        <button onclick="document.getElementById('assignModal').classList.remove('hidden')" class="btn btn-solid-accent">
            <i class="fas fa-plus mr-2"></i> Pazaryerine Ekle
        </button>
    </div>
    <form id="bulk-mp-form" method="POST" action="{{ route('admin.marketplace-products.bulk-update') }}" class="p-6 border-b">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Yeni Fiyat</label>
                <input type="number" step="0.01" name="price" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Yeni Stok</label>
                <input type="number" name="stock_quantity" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Yeni Durum</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                    <option value="">Seçiniz</option>
                    <option value="draft">draft</option>
                    <option value="active">active</option>
                    <option value="inactive">inactive</option>
                    <option value="rejected">rejected</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-md w-full">
                    Seçili Pazaryerlerini Güncelle
                </button>
            </div>
        </div>
    </form>
    <form method="POST" action="{{ route('admin.marketplace-products.bulk-sync') }}" class="p-6 border-b">
        @csrf
        <input type="hidden" name="marketplace_product_ids" id="bulk-sync-ids">
        <button type="submit" class="btn btn-solid-accent">
            Seçili Ürünleri Senkronize Et
        </button>
    </form>
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        <input type="checkbox" onclick="document.querySelectorAll('input[form=&quot;bulk-mp-form&quot;][name=&quot;marketplace_product_ids[]&quot;]').forEach(cb=>cb.checked=this.checked)">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pazaryeri</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Son Senkronizasyon</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($product->marketplaceProducts as $mp)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" form="bulk-mp-form" name="marketplace_product_ids[]" value="{{ $mp->id }}">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ $mp->marketplace->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ number_format($mp->price, 2) }} TRY</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $mp->stock_quantity }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">{{ $mp->status }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        {{ $mp->last_sync_at ? $mp->last_sync_at->format('d.m.Y H:i') : 'Henüz senkronize edilmedi' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <details class="inline-block">
                            <summary class="text-amber-600 hover:text-amber-800 cursor-pointer inline">Düzenle</summary>
                            <form action="{{ route('admin.marketplace-products.update', $mp) }}" method="POST" class="mt-2 space-y-2">
                                @csrf
                                @method('PUT')
                                <input type="number" step="0.01" name="price" value="{{ $mp->price }}" class="w-full px-2 py-1 border border-gray-300 rounded text-xs">
                                <input type="number" name="stock_quantity" value="{{ $mp->stock_quantity }}" class="w-full px-2 py-1 border border-gray-300 rounded text-xs">
                                <select name="status" class="w-full px-2 py-1 border border-gray-300 rounded text-xs">
                                    <option value="draft" {{ $mp->status === 'draft' ? 'selected' : '' }}>draft</option>
                                    <option value="active" {{ $mp->status === 'active' ? 'selected' : '' }}>active</option>
                                    <option value="inactive" {{ $mp->status === 'inactive' ? 'selected' : '' }}>inactive</option>
                                    <option value="rejected" {{ $mp->status === 'rejected' ? 'selected' : '' }}>rejected</option>
                                </select>
                                <button type="submit" class="text-xs bg-amber-500 text-white px-2 py-1 rounded">Kaydet</button>
                            </form>
                        </details>
                        <form action="{{ route('admin.marketplace-products.sync', $mp) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-blue-600 hover:text-blue-900 ml-3">
                                <i class="fas fa-sync"></i>
                            </button>
                        </form>
                        <form action="{{ route('admin.marketplace-products.destroy', $mp) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900 ml-3" onclick="return confirm('Kaldırmak istediğinize emin misiniz?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">Bu ürün henüz hiçbir pazaryerine eklenmemiş</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <script>
        document.querySelectorAll('input[form="bulk-mp-form"][name="marketplace_product_ids[]"]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var ids = Array.from(document.querySelectorAll('input[form="bulk-mp-form"][name="marketplace_product_ids[]"]:checked'))
                    .map(function (el) { return el.value; });
                var hidden = document.getElementById('bulk-sync-ids');
                if (hidden) {
                    hidden.value = ids.join(',');
                }
            });
        });
    </script>
</div>

<div id="assignModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-semibold mb-4">Pazaryerine Ürün Ekle</h3>
            <form action="{{ route('admin.marketplace-products.assign') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pazaryeri</label>
                    <select name="marketplace_id" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                        <option value="">Seçiniz</option>
                        @foreach($availableMarketplaces as $marketplace)
                            <option value="{{ $marketplace->id }}">{{ $marketplace->name }}</option>
                        @endforeach
                    </select>
                    @if($availableMarketplaces->isEmpty())
                        <p class="text-xs text-gray-500 mt-2">Önce Entegrasyonlar bölümünden pazaryeri bağlantısı ekleyin.</p>
                    @endif
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fiyat (TRY)</label>
                    <input type="number" step="0.01" name="price" value="{{ $product->price }}" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stok Miktarı</label>
                    <input type="number" name="stock_quantity" value="{{ $product->stock_quantity }}" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="document.getElementById('assignModal').classList.add('hidden')" 
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        İptal
                    </button>
                    <button type="submit" class="btn btn-solid-accent">
                        Ekle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection



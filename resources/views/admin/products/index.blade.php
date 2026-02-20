@extends('layouts.admin')



@section('header')

    {{ ($isInventoryView ?? false) ? 'Stok Takip' : 'Ürünler' }}

@endsection



@section('content')

@php

    $ownerUser = auth()->user();

    $canExport = $ownerUser ? app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, 'feature.exports') : false;
    $inventoryMarketplaceBadgeClass = static function (?string $marketplaceName): string {
        $name = strtolower(trim((string) $marketplaceName));

        if (str_contains($name, 'trendyol')) {
            return 'bg-amber-400 text-slate-900';
        }
        if (str_contains($name, 'hepsiburada')) {
            return 'bg-orange-400 text-slate-900';
        }
        if (str_contains($name, 'n11')) {
            return 'bg-violet-300 text-slate-900';
        }
        if (str_contains($name, 'cicek') || str_contains($name, 'çiçek')) {
            return 'bg-emerald-200 text-slate-900';
        }
        if (str_contains($name, 'amazon')) {
            return 'bg-slate-900 text-white';
        }

        return 'bg-slate-200 text-slate-700';
    };

@endphp
@if($isInventoryView ?? false)
<style>
    .inventory-sticky-shell {
        position: sticky;
        top: 0;
        z-index: 70;
        background: #fff;
        padding-top: 8px;
    }
    .inventory-sticky-shell .inventory-top-card {
        margin-bottom: 0;
        border-bottom: 0;
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
    }
    .inventory-sticky-shell .inventory-search-card {
        margin-bottom: 0;
        border-top: 0;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
    }

    @media (max-width: 1024px) {
        .inventory-sticky-shell {
            top: 0;
        }
    }
</style>
@endif
@if($isInventoryView ?? false)
<div class="inventory-sticky-shell">
<div class="panel-card p-3 mb-4 inventory-top-card">
        @include('admin.products.partials.catalog-tabs', [
            'isInventoryView' => ($isInventoryView ?? false),
            'inventoryMarketplaces' => ($inventoryMarketplaces ?? collect()),
            'selectedMarketplaceId' => ($selectedMarketplaceId ?? 0),
        ])
</div>
<div class="panel-card p-4 mb-4 inventory-search-card">
@else
<div class="mb-4">
    @include('admin.products.partials.catalog-tabs', [
        'isInventoryView' => ($isInventoryView ?? false),
        'inventoryMarketplaces' => ($inventoryMarketplaces ?? collect()),
        'selectedMarketplaceId' => ($selectedMarketplaceId ?? 0),
    ])
</div>
<div class="panel-card p-4 mb-4">
@endif

    <div class="flex flex-col gap-3">

        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">

            <div class="flex items-center gap-3 w-full lg:w-auto">

                <span class="text-sm font-medium text-slate-700 whitespace-nowrap">Ürün Ara</span>

                <form method="GET" id="product-search-form" class="flex items-center gap-2 bg-white border border-slate-200 rounded-full px-4 py-2 w-full md:w-[520px]">

                    <i class="fa-solid fa-magnifying-glass text-slate-400 text-sm"></i>

                    <input type="text" id="product-search-input" name="search" placeholder="Barkod, SKU, Ürün adı, Marka..."

                           class="border-0 focus:ring-0 text-sm w-full"

                           value="{{ request('search') }}">

                    @foreach(request()->except('search', 'page') as $key => $value)

                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">

                    @endforeach

                    <button type="submit" class="text-slate-500 hover:text-slate-700 text-sm">Ara</button>

                </form>

            </div>

            <div class="flex flex-wrap items-center gap-3 justify-start lg:justify-end">

                @if($canExport)

                <a href="{{ route('portal.products.template') }}" class="btn btn-outline-accent">

                    Excel Şablonu

                </a>

                <a href="{{ route('portal.products.export') }}" class="btn btn-outline-accent">

                    Excel Dışa Aktar

                </a>

                @endif

                <form method="POST" action="{{ route('portal.products.import') }}" enctype="multipart/form-data" class="flex items-center gap-2">

                    @csrf

                    <input type="file" name="file" accept=".xlsx" class="text-sm">

                    <button type="submit" class="btn btn-outline-accent">

                        Excel İçeri Aktar

                    </button>

                </form>

                <a href="{{ route('portal.products.create') }}" class="btn btn-solid-accent">

                    <i class="fas fa-plus mr-2"></i> Yeni Ürün

                </a>

            </div>

        </div>

    </div>

</div>
@if($isInventoryView ?? false)
</div>
@endif



<div id="products-results">

    <div id="products-table-wrap" class="panel-card table-shell overflow-hidden {{ ($isInventoryView ?? false) ? 'rounded-t-none border-t-0' : '' }}">
        
        <table class="min-w-full border-separate border-spacing-y-2">

            <thead>

            @php

                $currentSort = request('sort');

                $currentDir = request('dir', 'asc');

                $nextDir = function ($key) use ($currentSort, $currentDir) {

                    return $currentSort === $key && $currentDir === 'asc' ? 'desc' : 'asc';

                };

                $sortLink = function ($key) use ($nextDir) {

                    return request()->fullUrlWithQuery([

                        'sort' => $key,

                        'dir' => $nextDir($key),

                        'page' => null,

                    ]);

                };

                $sortIcon = function ($key) use ($currentSort, $currentDir) {

                    if ($currentSort !== $key) {

                        return 'fa-sort';

                    }

                    return $currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';

                };

            @endphp

            <tr>

                @if($isInventoryView ?? false)
                <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 uppercase">
                    <input type="checkbox" id="inventory-select-all" class="rounded border-slate-300 text-[#ff4439] focus:ring-[#ff4439]">
                </th>
                @endif

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Görsel</th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('sku') }}" class="inline-flex items-center gap-2">

                        SKU

                        <i class="fa-solid {{ $sortIcon('sku') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('name') }}" class="inline-flex items-center gap-2">

                        Ürün

                        <i class="fa-solid {{ $sortIcon('name') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('brand') }}" class="inline-flex items-center gap-2">

                        Marka

                        <i class="fa-solid {{ $sortIcon('brand') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('price') }}" class="inline-flex items-center gap-2">

                        Fiyat

                        <i class="fa-solid {{ $sortIcon('price') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('cost') }}" class="inline-flex items-center gap-2">

                        Maliyet

                        <i class="fa-solid {{ $sortIcon('cost') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('stock') }}" class="inline-flex items-center gap-2">

                        Stok

                        <i class="fa-solid {{ $sortIcon('stock') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('marketplace') }}" class="inline-flex items-center gap-2">

                        Pazaryeri

                        <i class="fa-solid {{ $sortIcon('marketplace') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">

                    <a href="{{ $sortLink('status') }}" class="inline-flex items-center gap-2">

                        Durum

                        <i class="fa-solid {{ $sortIcon('status') }}"></i>

                    </a>

                </th>

                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">İşlem</th>

            </tr>

        </thead>

        <tbody class="divide-y divide-transparent">

            @forelse($products as $product)

            <tr class="bg-white shadow-sm">
                @if($isInventoryView ?? false)
                <td class="px-4 py-4 whitespace-nowrap">
                    <input type="checkbox" class="inventory-row-select rounded border-slate-300 text-[#ff4439] focus:ring-[#ff4439]" value="{{ $product->id }}" data-product-id="{{ $product->id }}">
                </td>
                @endif

                <td class="px-6 py-4 whitespace-nowrap">

                    @if($product->display_image_url)

                        <img src="{{ $product->display_image_url }}" alt="{{ $product->name }}" class="w-12 h-12 object-cover rounded-xl">

                    @else

                        <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center">

                            <i class="fas fa-image text-slate-400"></i>

                        </div>

                    @endif

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <code class="bg-slate-100 px-2 py-1 rounded text-xs">{{ $product->sku }}</code>

                </td>

                <td class="px-6 py-4">

                    <div class="text-sm font-semibold text-slate-900">{{ $product->name }}</div>

                    @if($product->barcode)

                        <div class="text-xs text-slate-500">Barkod: {{ $product->barcode }}</div>

                    @endif

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ $product->brand ?? '-' }}</td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <div class="flex items-center gap-2">

                        <input type="number" step="0.01" min="0" class="w-24 text-sm"

                               value="{{ $product->price }}"

                               data-product-price="{{ $product->id }}">

                        <span class="text-xs text-slate-500">{{ $product->currency }}</span>

                    </div>

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <div class="flex items-center gap-2">

                        <input type="number" step="0.01" min="0" class="w-24 text-sm"

                               value="{{ $product->cost_price }}"

                               data-product-cost="{{ $product->id }}">

                        <span class="text-xs text-slate-500">{{ $product->currency }}</span>

                    </div>

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <div class="flex items-center gap-2">

                        <input type="number" min="0" class="w-20 text-sm"

                               value="{{ $product->stock_quantity }}"

                               data-product-stock="{{ $product->id }}">

                        <span class="text-xs text-slate-500">adet</span>

                    </div>

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    @php

                        $marketplaceNames = $product->marketplaceProducts
                            ->pluck('marketplace')
                            ->filter()
                            ->unique('id')
                            ->pluck('name')
                            ->filter()
                            ->shuffle()
                            ->values();

                    @endphp

                    <div class="flex flex-wrap items-center gap-2 max-w-[280px]">
                        @forelse($marketplaceNames as $marketplaceName)
                            <span class="panel-pill text-xs {{ $inventoryMarketplaceBadgeClass($marketplaceName) }}">
                                {{ $marketplaceName }}
                            </span>
                        @empty
                            <span class="text-xs text-slate-400">-</span>
                        @endforelse
                    </div>

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                    <span class="panel-pill text-xs {{ $product->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-200 text-slate-600' }}">

                        {{ $product->is_active ? 'Aktif' : 'Pasif' }}

                    </span>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm">

                    <button type="button" class="text-emerald-600 hover:text-emerald-800 mr-3 quick-save"

                            data-product-id="{{ $product->id }}">

                        <i class="fas fa-check"></i>

                    </button>

                    <a href="{{ route('portal.products.show', $product) }}" class="text-blue-600 hover:text-blue-900 mr-3">

                        <i class="fas fa-eye"></i>

                    </a>

                    <a href="{{ route('portal.products.edit', $product) }}"
                       class="text-amber-600 hover:text-amber-800 mr-3"
                       data-product-edit-popup="1"
                       data-product-name="{{ $product->name }}">

                        <i class="fas fa-edit"></i>

                    </a>
                    @if($isInventoryView ?? false)
                    <button type="button"
                            class="text-violet-600 hover:text-violet-800 mr-3"
                            data-toggle-marketplace-row="{{ $product->id }}">
                        <i class="fas fa-store"></i>
                    </button>
                    @endif

                    <form action="{{ route('portal.products.destroy', $product) }}" method="POST" class="inline">

                        @csrf

                        @method('DELETE')

                        <button type="submit" class="text-rose-600 hover:text-rose-800" onclick="return confirm('Silmek istediğinize emin misiniz?')">

                            <i class="fas fa-trash"></i>

                        </button>

                    </form>

                </td>

            </tr>

            @if($isInventoryView ?? false)
            <tr class="hidden bg-slate-50" data-marketplace-row="{{ $product->id }}">
                <td colspan="{{ ($isInventoryView ?? false) ? 11 : 10 }}" class="px-6 py-4">
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="text-sm font-semibold text-slate-800 mb-3">Pazaryerine Ac</div>
                        <form method="POST"
                              action="{{ route('portal.marketplace-products.assign') }}"
                              class="flex flex-col md:flex-row md:items-end gap-3"
                              data-marketplace-assign-form="{{ $product->id }}">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="price" value="{{ $product->price }}" data-hidden-price="{{ $product->id }}">
                            <input type="hidden" name="stock_quantity" value="{{ $product->stock_quantity }}" data-hidden-stock="{{ $product->id }}">

                            <div class="w-full md:w-72">
                                <label class="block text-xs text-slate-600 mb-1">Pazaryeri</label>
                                <select name="marketplace_id" class="w-full" required>
                                    <option value="">Seciniz</option>
                                    @foreach(($inventoryMarketplaces ?? collect()) as $inventoryMarketplace)
                                        <option value="{{ $inventoryMarketplace->id }}">{{ $inventoryMarketplace->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-solid-accent">
                                Gonder
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @endif

            @empty

            <tr>

                <td colspan="{{ ($isInventoryView ?? false) ? 11 : 10 }}" class="px-6 py-4 text-center text-slate-500">Henüz ürün bulunmuyor</td>

            </tr>

            @endforelse

        </tbody>

        </table>

    </div>



    <div class="mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <form method="GET" class="flex items-center gap-2 text-sm">

            <label for="per-page" class="text-slate-500">Sayfa başına</label>

            <select id="per-page" name="per_page" class="w-24" onchange="this.form.submit()">

                @php
                    $perPageOptions = ($isInventoryView ?? false) ? [25, 50, 100] : [10, 20, 50, 100];
                    $defaultPerPage = ($isInventoryView ?? false) ? 25 : 20;
                @endphp
                @foreach($perPageOptions as $size)

                    <option value="{{ $size }}" @selected((int) request('per_page', $defaultPerPage) === $size)>{{ $size }}</option>

                @endforeach

            </select>

            @foreach(request()->except('per_page', 'page') as $key => $value)

                <input type="hidden" name="{{ $key }}" value="{{ $value }}">

            @endforeach

        </form>

        {{ $products->links() }}

    </div>

</div>

<div id="product-edit-modal" class="fixed inset-0 z-[150] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/55" data-product-edit-close></div>
    <div class="relative mx-auto mt-6 w-[96%] max-w-6xl h-[90vh] rounded-2xl border border-slate-200 bg-white shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h3 class="text-sm font-semibold text-slate-800">Ürün Düzenle: <span id="product-edit-modal-title" class="text-slate-600">-</span></h3>
            <button type="button" class="btn btn-outline text-xs" data-product-edit-close>Kapat</button>
        </div>
        <iframe id="product-edit-modal-frame" class="w-full h-[calc(90vh-56px)] border-0" src="about:blank" loading="lazy"></iframe>
    </div>
</div>

@endsection



@push('scripts')

<script>

    const searchForm = document.getElementById('product-search-form');

    const searchInput = document.getElementById('product-search-input');

    const resultsWrap = document.getElementById('products-results');
    const productEditModal = document.getElementById('product-edit-modal');
    const productEditModalFrame = document.getElementById('product-edit-modal-frame');
    const productEditModalTitle = document.getElementById('product-edit-modal-title');

    let searchTimer;

    let searchAbortController;
    const inventoryFlashMessage = @json(session('error') ?? (session('success') ?? ($errors->any() ? $errors->first() : null)));
    const inventoryFlashType = @json(session('error') || $errors->any() ? 'error' : (session('success') ? 'success' : null));

    const inlineSaveTimers = {};
    const inlineLastSavedState = {};

    function closeProductEditModal() {
        if (!productEditModal || !productEditModalFrame) return;
        productEditModal.classList.add('hidden');
        productEditModal.setAttribute('aria-hidden', 'true');
        productEditModalFrame.src = 'about:blank';
        document.body.style.overflow = '';
    }

    function openProductEditModal(url, titleText) {
        if (!productEditModal || !productEditModalFrame) return;
        if (productEditModalTitle) {
            productEditModalTitle.textContent = titleText || '-';
        }
        const iframeUrl = new URL(url, window.location.origin);
        iframeUrl.searchParams.set('embed', '1');
        productEditModalFrame.src = iframeUrl.toString();
        productEditModal.classList.remove('hidden');
        productEditModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    async function submitInlineUpdate(productId, triggerButton = null) {
        const costInput = document.querySelector(`[data-product-cost="${productId}"]`);
        const priceInput = document.querySelector(`[data-product-price="${productId}"]`);
        const stockInput = document.querySelector(`[data-product-stock="${productId}"]`);

        if (!priceInput || !stockInput) {
            return;
        }

        const costValue = costInput ? costInput.value : '';
        const stateKey = `${costValue}|${priceInput.value}|${stockInput.value}`;
        if (inlineLastSavedState[productId] === stateKey) {
            return;
        }

        if (triggerButton) {
            triggerButton.disabled = true;
        }

        const response = await fetch(`{{ url('/products') }}/${productId}/quick-update`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cost_price: costValue,
                price: priceInput.value,
                stock_quantity: stockInput.value,
            }),
        });

        if (triggerButton) {
            triggerButton.disabled = false;
        }

        if (!response.ok) {
            alert('Kaydedilemedi. Lutfen degerleri kontrol edin.');
            return;
        }

        inlineLastSavedState[productId] = stateKey;
    }


    function bindQuickSave() {

        const quickSaveButtons = document.querySelectorAll('.quick-save');
        const costInputs = Array.from(document.querySelectorAll('[data-product-cost]'));
        const priceInputs = Array.from(document.querySelectorAll('[data-product-price]'));
        const stockInputs = Array.from(document.querySelectorAll('[data-product-stock]'));

        quickSaveButtons.forEach((btn) => {

            btn.addEventListener('click', async () => {

                const productId = btn.dataset.productId;
                await submitInlineUpdate(productId, btn);

            });

        });

        const registerAutoSave = (inputEl) => {
            const productId = inputEl.getAttribute('data-product-cost') || inputEl.getAttribute('data-product-price') || inputEl.getAttribute('data-product-stock');
            if (!productId) {
                return;
            }

            const scheduleSave = () => {
                window.clearTimeout(inlineSaveTimers[productId]);
                inlineSaveTimers[productId] = window.setTimeout(() => {
                    submitInlineUpdate(productId);
                }, 500);
            };

            inputEl.addEventListener('input', scheduleSave);
            inputEl.addEventListener('change', scheduleSave);
            inputEl.addEventListener('blur', scheduleSave);
        };

        costInputs.forEach(registerAutoSave);
        priceInputs.forEach(registerAutoSave);
        stockInputs.forEach(registerAutoSave);

        const seenProductIds = new Set();
        [...costInputs, ...priceInputs, ...stockInputs].forEach((inputEl) => {
            const productId = inputEl.getAttribute('data-product-cost') || inputEl.getAttribute('data-product-price') || inputEl.getAttribute('data-product-stock');
            if (!productId || seenProductIds.has(productId)) {
                return;
            }

            seenProductIds.add(productId);
            const costInput = document.querySelector(`[data-product-cost="${productId}"]`);
            const priceInput = document.querySelector(`[data-product-price="${productId}"]`);
            const stockInput = document.querySelector(`[data-product-stock="${productId}"]`);
            if (priceInput && stockInput) {
                inlineLastSavedState[productId] = `${costInput ? costInput.value : ''}|${priceInput.value}|${stockInput.value}`;
            }
        });

    }
    function bindInventoryMarketplaceActions() {

        const toggleButtons = document.querySelectorAll('[data-toggle-marketplace-row]');

        toggleButtons.forEach((btn) => {

            btn.addEventListener('click', () => {

                const productId = btn.getAttribute('data-toggle-marketplace-row');

                const row = document.querySelector(`[data-marketplace-row="${productId}"]`);

                if (!row) return;

                row.classList.toggle('hidden');

            });

        });

        const assignForms = document.querySelectorAll('[data-marketplace-assign-form]');

        assignForms.forEach((form) => {

            form.addEventListener('submit', () => {

                const productId = form.getAttribute('data-marketplace-assign-form');

                const currentPrice = document.querySelector(`[data-product-price="${productId}"]`);

                const currentStock = document.querySelector(`[data-product-stock="${productId}"]`);

                const hiddenPrice = form.querySelector(`[data-hidden-price="${productId}"]`);

                const hiddenStock = form.querySelector(`[data-hidden-stock="${productId}"]`);

                if (currentPrice && hiddenPrice) {

                    hiddenPrice.value = currentPrice.value;

                }

                if (currentStock && hiddenStock) {

                    hiddenStock.value = currentStock.value;

                }

            });

        });

    }



    function bindInventorySelection() {

        const selectAll = document.getElementById('inventory-select-all');

        const rowCheckboxes = Array.from(document.querySelectorAll('.inventory-row-select'));
        const selectedSyncForm = document.querySelector('[data-inventory-sync-selected-form]');
        const selectedSyncCsvInput = selectedSyncForm ? selectedSyncForm.querySelector('input[name="selected_product_ids_csv"]') : null;
        const selectedSyncSubmit = selectedSyncForm ? selectedSyncForm.querySelector('[data-inventory-sync-selected-submit]') : null;
        const selectedSyncMarketplaceInput = selectedSyncForm ? selectedSyncForm.querySelector('input[name="marketplace_id"]') : null;
        const openForm = document.querySelector('[data-inventory-open-form]');
        const openToggle = openForm ? openForm.querySelector('[data-inventory-open-toggle]') : null;
        const openPanel = openForm ? openForm.querySelector('[data-inventory-open-panel]') : null;
        const openMarketplaceSelect = openForm ? openForm.querySelector('select[name="marketplace_id"]') : null;
        const openSelectedCsvInput = openForm ? openForm.querySelector('input[name="selected_product_ids_csv"]') : null;
        const openSubmit = openForm ? openForm.querySelector('[data-inventory-open-submit]') : null;
        try {
            window.sessionStorage.removeItem('inventory_selected_product_ids_v1');
        } catch (error) {
            // no-op
        }

        const updateOpenSubmitState = (selectedCount) => {
            if (!openSubmit) {
                return;
            }

            const hasMarketplace = !!(openMarketplaceSelect && openMarketplaceSelect.value);
            openSubmit.disabled = !(selectedCount > 0 && hasMarketplace);
        };

        const updateSelectedSyncSubmitState = (selectedCount) => {
            if (!selectedSyncSubmit) {
                return;
            }

            selectedSyncSubmit.disabled = selectedCount === 0;
        };

        const syncBulkFormState = () => {
            const selectedIds = rowCheckboxes
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.value);
            const selectedCount = selectedIds.length;

            if (selectedSyncCsvInput) {
                selectedSyncCsvInput.value = selectedIds.join(',');
            }
            if (selectedSyncMarketplaceInput) {
                const marketplaceFromQuery = new URLSearchParams(window.location.search).get('marketplace_id') || '';
                selectedSyncMarketplaceInput.value = (marketplaceFromQuery && marketplaceFromQuery !== '0') ? marketplaceFromQuery : '';
            }
            if (openSelectedCsvInput) {
                openSelectedCsvInput.value = selectedIds.join(',');
            }
            updateSelectedSyncSubmitState(selectedCount);

            updateOpenSubmitState(selectedCount);
            if (selectedCount === 0 && openPanel) {
                openPanel.classList.add('hidden');
            }
        };

        if (openToggle && openPanel) {
            openToggle.addEventListener('click', () => {
                openPanel.classList.toggle('hidden');
            });
        }

        if (openMarketplaceSelect) {
            openMarketplaceSelect.addEventListener('change', () => {
                const selectedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
                updateOpenSubmitState(selectedCount);
            });
        }

        if (!selectAll || rowCheckboxes.length === 0) {
            syncBulkFormState();
            return;
        }

        const syncSelectAllState = () => {
            const checkedCount = rowCheckboxes.filter((checkbox) => checkbox.checked).length;
            selectAll.checked = checkedCount > 0 && checkedCount === rowCheckboxes.length;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
            syncBulkFormState();
        };

        selectAll.addEventListener('change', () => {
            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            syncSelectAllState();
        });

        rowCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', syncSelectAllState);
        });

        if (selectedSyncForm) {
            selectedSyncForm.addEventListener('submit', () => {
                syncBulkFormState();
            });
        }

        if (openForm) {
            openForm.addEventListener('submit', () => {
                syncBulkFormState();
                if (openPanel) {
                    openPanel.classList.add('hidden');
                }
            });
        }

        syncSelectAllState();
    }


    async function fetchResults(url) {

        if (!resultsWrap) return;

        if (searchAbortController) {

            searchAbortController.abort();

        }

        searchAbortController = new AbortController();



        try {

            const response = await fetch(url, {

                headers: {

                    'X-Requested-With': 'XMLHttpRequest',

                },

                signal: searchAbortController.signal,

            });



            if (!response.ok) {

                return;

            }



            const html = await response.text();

            const parser = new DOMParser();

            const doc = parser.parseFromString(html, 'text/html');

            const nextResults = doc.getElementById('products-results');



            if (nextResults) {

                resultsWrap.innerHTML = nextResults.innerHTML;

                bindQuickSave();

                bindInventoryMarketplaceActions();
                bindInventorySelection();

                if (searchInput) {

                    searchInput.focus();

                    searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);

                }

                window.history.replaceState({}, '', url);

            }

        } catch (error) {

            if (error.name !== 'AbortError') {

                console.error(error);

            }

        }

    }



    async function runSearch(value) {

        if (!searchForm) return;



        const url = new URL(window.location.href);

        if (value) {

            url.searchParams.set('search', value);

        } else {

            url.searchParams.delete('search');

        }

        url.searchParams.delete('page');

        await fetchResults(url.toString());

    }



    bindQuickSave();

    bindInventoryMarketplaceActions();
    bindInventorySelection();
    if (inventoryFlashMessage) {
        const toast = document.createElement('div');
        toast.className = 'fixed right-4 bottom-4 z-[120] px-4 py-3 rounded-xl shadow-lg border text-sm max-w-sm';
        if (inventoryFlashType === 'error') {
            toast.classList.add('bg-red-50', 'border-red-200', 'text-red-700');
        } else {
            toast.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
        }
        toast.textContent = inventoryFlashMessage;
        document.body.appendChild(toast);
        window.setTimeout(() => {
            toast.remove();
        }, 4500);
    }



    if (resultsWrap) {

        resultsWrap.addEventListener('click', (event) => {
            const editTrigger = event.target.closest('[data-product-edit-popup]');
            if (!editTrigger || !resultsWrap.contains(editTrigger)) return;
            if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            const href = editTrigger.getAttribute('href');
            if (!href) return;
            event.preventDefault();
            openProductEditModal(href, editTrigger.getAttribute('data-product-name'));
        });

        resultsWrap.addEventListener('click', (event) => {

            const link = event.target.closest('a');

            if (!link || !resultsWrap.contains(link)) return;

            const href = link.getAttribute('href');

            if (!href || href.startsWith('#')) return;



            const isPagination = href.includes('page=');

            if (!isPagination) return;



            event.preventDefault();

            fetchResults(href);

        });



        resultsWrap.addEventListener('change', (event) => {

            const target = event.target;

            if (!(target instanceof HTMLSelectElement)) return;

            if (target.id !== 'per-page') return;



            const url = new URL(window.location.href);

            url.searchParams.set('per_page', target.value);

            url.searchParams.delete('page');

            fetchResults(url.toString());

        });

    }



    if (searchForm && searchInput) {

        searchInput.addEventListener('input', () => {

            window.clearTimeout(searchTimer);

            searchTimer = window.setTimeout(() => {

                runSearch(searchInput.value.trim());

            }, 350);

        });

    }

    document.querySelectorAll('[data-product-edit-close]').forEach((btn) => {
        btn.addEventListener('click', closeProductEditModal);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeProductEditModal();
        }
    });

</script>

@endpush
















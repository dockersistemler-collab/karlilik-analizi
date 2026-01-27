@extends('layouts.admin')

@section('header')
    Ürünler
@endsection

@section('content')
<div class="mb-4">
    @include('admin.products.partials.catalog-tabs')
</div>

<div class="panel-card p-4 mb-4">
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
                <a href="{{ route('admin.products.template') }}" class="btn btn-outline-accent">
                    Excel Şablonu
                </a>
                <a href="{{ route('admin.products.export') }}" class="btn btn-outline-accent">
                    Excel Dışa Aktar
                </a>
                <form method="POST" action="{{ route('admin.products.import') }}" enctype="multipart/form-data" class="flex items-center gap-2">
                    @csrf
                    <input type="file" name="file" accept=".xlsx" class="text-sm">
                    <button type="submit" class="btn btn-outline-accent">
                        Excel İçeri Aktar
                    </button>
                </form>
                <a href="{{ route('admin.products.create') }}" class="btn btn-solid-accent">
                    <i class="fas fa-plus mr-2"></i> Yeni Ürün
                </a>
            </div>
        </div>
    </div>
</div>

<div id="products-results">
    <div id="products-table-wrap" class="panel-card table-shell overflow-hidden">
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
                        <input type="number" min="0" class="w-20 text-sm"
                               value="{{ $product->stock_quantity }}"
                               data-product-stock="{{ $product->id }}">
                        <span class="text-xs text-slate-500">adet</span>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $marketplaces = $product->marketplaceProducts
                            ->pluck('marketplace')
                            ->filter()
                            ->unique('id');
                    @endphp
                    <div class="flex items-center gap-2">
                        <div class="flex -space-x-1">
                            @foreach($marketplaces as $marketplace)
                                @php
                                    $settings = $marketplace->settings ?? [];
                                    $logoPath = $settings['logo_path'] ?? null;
                                    $logo = $logoPath ? '/storage/' . ltrim($logoPath, '/') : ($settings['logo_url'] ?? $settings['logo'] ?? null);
                                    if ($logo && \Illuminate\Support\Str::startsWith($logo, ['http://', 'https://'])) {
                                        $path = parse_url($logo, PHP_URL_PATH);
                                        if ($path && \Illuminate\Support\Str::startsWith($path, '/storage/')) {
                                            $logo = $path;
                                        }
                                    } elseif ($logo && !\Illuminate\Support\Str::startsWith($logo, ['/'])) {
                                        $logo = '/' . ltrim($logo, '/');
                                    }
                                    $labelBase = $marketplace->code ?: $marketplace->name;
                                    $codeLabel = $labelBase ? strtoupper(substr($labelBase, 0, 2)) : '?';
                                @endphp
                                @if($logo)
                                    <img src="{{ $logo }}" alt="{{ $marketplace->name }} logo"
                                         class="w-7 h-7 rounded-full border border-white shadow-sm bg-white object-contain"
                                         title="{{ $marketplace->name }}">
                                @else
                                    <span class="w-7 h-7 rounded-full border border-white shadow-sm bg-slate-100 text-[10px] font-semibold text-slate-600 flex items-center justify-center"
                                          title="{{ $marketplace->name }}">
                                        {{ $codeLabel }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                        <span class="panel-pill text-xs bg-blue-100 text-blue-700">
                            {{ $product->marketplace_products_count }}
                        </span>
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
                    <a href="{{ route('admin.products.show', $product) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('admin.products.edit', $product) }}" class="text-amber-600 hover:text-amber-800 mr-3">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-rose-600 hover:text-rose-800" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="px-6 py-4 text-center text-slate-500">Henüz ürün bulunmuyor</td>
            </tr>
            @endforelse
        </tbody>
        </table>
    </div>

    <div class="mt-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <form method="GET" class="flex items-center gap-2 text-sm">
            <label for="per-page" class="text-slate-500">Sayfa başına</label>
            <select id="per-page" name="per_page" class="w-24" onchange="this.form.submit()">
                @foreach([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>{{ $size }}</option>
                @endforeach
            </select>
            @foreach(request()->except('per_page', 'page') as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
        </form>
        {{ $products->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    const searchForm = document.getElementById('product-search-form');
    const searchInput = document.getElementById('product-search-input');
    const resultsWrap = document.getElementById('products-results');
    let searchTimer;
    let searchAbortController;

    function bindQuickSave() {
        const quickSaveButtons = document.querySelectorAll('.quick-save');

        quickSaveButtons.forEach((btn) => {
            btn.addEventListener('click', async () => {
                const productId = btn.dataset.productId;
                const priceInput = document.querySelector(`[data-product-price="${productId}"]`);
                const stockInput = document.querySelector(`[data-product-stock="${productId}"]`);
                if (!priceInput || !stockInput) return;

                btn.disabled = true;
                const response = await fetch(`{{ url('/admin/products') }}/${productId}/quick-update`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        price: priceInput.value,
                        stock_quantity: stockInput.value,
                    }),
                });
                btn.disabled = false;

                if (!response.ok) {
                    alert('Kaydedilemedi. Lütfen değerleri kontrol edin.');
                }
            });
        });
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

    if (resultsWrap) {
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
</script>
@endpush

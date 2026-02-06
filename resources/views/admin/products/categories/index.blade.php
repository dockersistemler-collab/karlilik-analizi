@extends('layouts.admin')



@section('header')

    Kategoriler

@endsection



@section('content')

    @include('admin.products.partials.catalog-tabs')



    <div class="panel-card p-6 space-y-4">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div>

                <h3 class="text-sm font-semibold text-slate-800">Kategori Yönetimi</h3>

                <p class="text-xs text-slate-500 mt-1">Kendi kategorilerinizi oluşturun ve pazaryerleri ile eşitleyin.</p>

            </div>

            <div class="flex flex-wrap items-center gap-2">

                <button id="open-import-modal" type="button" class="btn btn-outline-accent" @disabled($marketplaces->isEmpty())>

                    <i class="fa-solid fa-cloud-arrow-down text-xs mr-2"></i>

                    Pazaryerinden İçe Aktar

                </button>

                <button id="open-category-modal" type="button" class="btn btn-solid-accent">

                    <i class="fa-solid fa-plus text-xs mr-2"></i>

                    Yeni Kategori

                </button>

            </div>

        </div>



        @if($marketplaces->isEmpty())

            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">

                Kategori eşitleme için önce en az 1 pazaryeri mağazası bağlayın:

                <a href="{{ route('portal.integrations.index') }}" class="font-semibold underline">Mağaza Bağla</a>

            </div>

        @endif



        <form method="GET" action="{{ route('portal.categories.index') }}" class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">

            <div class="flex-1 max-w-xl">

                <label class="block text-xs font-semibold text-slate-600 mb-1">Kategori Ara</label>

                <div class="flex items-center gap-2">

                    <input name="q" value="{{ $search ?? '' }}" class="w-full" placeholder="Kategori adı ile ara...">

                    <button type="submit" class="btn btn-outline-accent">Filtrele</button>

                </div>

            </div>

        </form>



        <div id="category-toast" class="hidden rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700"></div>



        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr class="text-left">

                        <th class="py-2 pr-4">Kategori</th>

                        @foreach($marketplaces as $marketplace)

                            <th class="py-2 pr-4 whitespace-nowrap">{{ $marketplace->name }}</th>

                        @endforeach

                        <th class="py-2 text-right">İşlemler</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($categories as $category)

                        @php

                            $rowMappings = $mappingsByCategory[$category->id] ?? collect();

                        @endphp

                        <tr class="border-t border-slate-100 align-top" data-category-row="{{ $category->id }}">

                            <td class="py-3 pr-4">

                                <div class="font-semibold text-slate-900">{{ $category->name }}</div>

                                <div class="text-xs text-slate-500 mt-1">ID: {{ $category->id }}</div>

                            </td>

                            @foreach($marketplaces as $marketplace)

                                @php $mapping = $rowMappings[$marketplace->id] ?? null; @endphp

                                <td class="py-3 pr-4" data-mapping-cell="{{ $category->id }}:{{ $marketplace->id }}">

                                    @if($mapping)

                                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs text-emerald-700">

                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Eşlendi

                                        </div>

                                        <div class="text-xs text-slate-500 mt-2 truncate max-w-[220px]">

                                            {{ $mapping->marketplaceCategory?->path ?? $mapping->marketplace_category_external_id }}

                                        </div>

                                    @else

                                        <div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-600">

                                            <span class="h-2 w-2 rounded-full bg-slate-300"></span> Boş

                                        </div>

                                    @endif

                                </td>

                            @endforeach

                            <td class="py-3 text-right whitespace-nowrap">

                                <button type="button" class="btn btn-outline-accent toggle-mapping" data-category-id="{{ $category->id }}">Eşitle</button>

                                <button type="button" class="text-slate-600 hover:text-slate-900 ml-3 open-category-edit" data-id="{{ $category->id }}" data-name="{{ $category->name }}">Düzenle</button>

                                <form method="POST" action="{{ route('portal.categories.destroy', $category) }}" class="inline ml-3">

                                    @csrf

                                    @method('DELETE')

                                    <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Kategori silinsin mi?')">Sil</button>

                                </form>

                            </td>

                        </tr>

                        <tr class="border-b border-slate-100 hidden" data-mapping-row-for="{{ $category->id }}">

                            <td colspan="{{ 2 + $marketplaces->count() }}" class="py-4">

                                <div class="rounded-xl border border-slate-200 bg-white p-4">

                                    <div class="flex items-center justify-between gap-3">

                                        <div>

                                            <h4 class="text-sm font-semibold text-slate-800">Kategori Eşitleme</h4>

                                            <p class="text-xs text-slate-500 mt-1">{{ $category->name }} için pazaryeri kategorilerini seçin.</p>

                                        </div>

                                        <button type="button" class="text-slate-400 hover:text-slate-600 close-mapping" data-category-id="{{ $category->id }}">

                                            <i class="fa-solid fa-xmark"></i>

                                        </button>

                                    </div>



                                    <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">

                                        @foreach($marketplaces as $marketplace)

                                            @php

                                                $mapping = $rowMappings[$marketplace->id] ?? null;

                                                $mappedDisplay = $mapping?->marketplaceCategory?->path ?: $mapping?->marketplace_category_external_id;

                                            @endphp

                                            <div class="rounded-xl border border-slate-200 p-4" data-mapping-card="{{ $category->id }}:{{ $marketplace->id }}">

                                                <div class="flex items-center justify-between gap-2">

                                                    <div class="text-sm font-semibold text-slate-800">

                                                        {{ $marketplace->name }}

                                                        <span class="ml-2 text-xs text-slate-500" data-mapping-status>

                                                            @if($mapping)

                                                                <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Eşlendi</span>

                                                            @else

                                                                <span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-300"></span> Boş</span>

                                                            @endif

                                                        </span>

                                                    </div>

                                                    <div class="flex items-center gap-2">

                                                        <button type="button" class="btn btn-outline-accent mp-sync" data-marketplace-id="{{ $marketplace->id }}">Senkronla</button>

                                                        <button type="button" class="btn btn-outline-accent mp-clear @if(!$mapping) hidden @endif" data-category-id="{{ $category->id }}" data-marketplace-id="{{ $marketplace->id }}">Kaldır</button>

                                                    </div>

                                                </div>



                                                <div class="mt-3">

                                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Pazaryeri Kategorisi Seç</label>

                                                    <div class="relative">

                                                        <input type="text" class="w-full mp-search" placeholder="Yaz ve ara... (örn: bebek)" data-category-id="{{ $category->id }}" data-marketplace-id="{{ $marketplace->id }}" autocomplete="off">

                                                        <div class="absolute left-0 right-0 mt-1 max-h-56 overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg hidden z-10 mp-results"></div>

                                                    </div>

                                                    <div class="text-xs text-slate-500 mt-2" data-current-mapping>

                                                        Seçili: <span class="font-semibold text-slate-700">{{ $mappedDisplay ?: '-' }}</span>

                                                    </div>

                                                </div>

                                            </div>

                                        @endforeach

                                    </div>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr class="border-t border-slate-100">

                            <td colspan="{{ 2 + $marketplaces->count() }}" class="py-6 text-center text-slate-500">Kategori bulunamadı.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>



        <div class="mt-4">

            {{ $categories->links() }}

        </div>

    </div>



    <div id="category-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">

        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6">

            <div class="flex items-center justify-between mb-4">

                <h3 class="text-sm font-semibold text-slate-800" id="category-modal-title">Yeni Kategori</h3>

                <button id="close-category-modal" type="button" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>

            </div>

            <form id="category-modal-form" class="space-y-4">

                @csrf

                <div id="category-modal-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">Kategori Adı</label>

                    <input type="text" name="name" class="mt-1 w-full" required>

                </div>

                <input type="hidden" name="category_id" value="">

                <div class="flex items-center gap-3">

                    <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                    <button id="cancel-category-modal" type="button" class="btn btn-outline-accent">Vazgeç</button>

                </div>

            </form>

        </div>

    </div>



    <div id="import-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">

        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6">

            <div class="flex items-center justify-between mb-4">

                <h3 class="text-sm font-semibold text-slate-800">Pazaryerinden İçe Aktar</h3>

                <button id="close-import-modal" type="button" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>

            </div>

            <form id="import-form" class="space-y-4">

                @csrf

                <div id="import-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">Pazaryeri</label>

                    <select name="marketplace_id" class="mt-1 w-full" required>

                        <option value="">Seçiniz</option>

                        @foreach($marketplaces as $marketplace)

                            <option value="{{ $marketplace->id }}">{{ $marketplace->name }}</option>

                        @endforeach

                    </select>

                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">

                    <input type="checkbox" name="only_leaf" value="1" class="rounded" @checked($categoryImportOnlyLeafDefault ?? true)>

                    Sadece en alt (leaf) kategorileri içe aktar

                </label>

                <label class="flex items-center gap-2 text-sm text-slate-700">

                    <input type="checkbox" name="create_mappings" value="1" class="rounded" @checked($categoryImportCreateMappingsDefault ?? true)>

                    Seçilen pazaryeri ile otomatik eşleme oluştur

                </label>

                <div class="flex items-center gap-3">

                    <button type="submit" class="btn btn-solid-accent">İçe Aktar</button>

                    <button id="cancel-import-modal" type="button" class="btn btn-outline-accent">Vazgeç</button>

                </div>

                <p class="text-xs text-slate-500">

                    Not: İlk içe aktarmada sistem pazaryeri kategorilerini API’dan çekip cache’ler.

                </p>

            </form>

        </div>

    </div>

@endsection



@push('scripts')

<script>

    const toast = document.getElementById('category-toast');

    const csrf = '{{ csrf_token() }}';

    const openCategoryId = '{{ $openCategoryId ?? '' }}';



    function showToast(message, type = 'info') {

        if (!toast) return;

        toast.textContent = message;

        toast.classList.remove('hidden', 'border-emerald-200', 'bg-emerald-50', 'text-emerald-800', 'border-red-200', 'bg-red-50', 'text-red-800', 'border-slate-200', 'bg-slate-50', 'text-slate-700');

        if (type === 'success') toast.classList.add('border-emerald-200', 'bg-emerald-50', 'text-emerald-800');

        else if (type === 'error') toast.classList.add('border-red-200', 'bg-red-50', 'text-red-800');

        else toast.classList.add('border-slate-200', 'bg-slate-50', 'text-slate-700');

        setTimeout(() => toast.classList.add('hidden'), 3500);

    }



    function toggleRow(categoryId, open) {

        const row = document.querySelector(`[data-mapping-row-for="${categoryId}"]`);

        if (!row) return;

        row.classList.toggle('hidden', !open);

    }



    document.querySelectorAll('.toggle-mapping').forEach((btn) => btn.addEventListener('click', () => toggleRow(btn.dataset.categoryId, true)));

    document.querySelectorAll('.close-mapping').forEach((btn) => btn.addEventListener('click', () => toggleRow(btn.dataset.categoryId, false)));



    if (openCategoryId) {

        toggleRow(openCategoryId, true);

        const anchor = document.querySelector(`[data-category-row="${openCategoryId}"]`);

        anchor?.scrollIntoView({ behavior: 'smooth', block: 'center' });

    }



    // Manual category modal

    const categoryModal = document.getElementById('category-modal');

    const categoryForm = document.getElementById('category-modal-form');

    const categoryError = document.getElementById('category-modal-error');

    const categoryTitle = document.getElementById('category-modal-title');

    const categoryIdInput = categoryForm?.querySelector('input[name="category_id"]');



    function toggleModal(modal, open) {

        if (!modal) return;

        modal.classList.toggle('hidden', !open);

        modal.classList.toggle('flex', open);

    }

    function showInlineError(el, message) {

        if (!el) return;

        el.textContent = message;

        el.classList.remove('hidden');

    }

    function clearInlineError(el) {

        if (!el) return;

        el.textContent = '';

        el.classList.add('hidden');

    }



    document.getElementById('open-category-modal')?.addEventListener('click', () => {

        categoryTitle.textContent = 'Yeni Kategori';

        categoryForm?.reset();

        if (categoryIdInput) categoryIdInput.value = '';

        clearInlineError(categoryError);

        toggleModal(categoryModal, true);

    });

    document.getElementById('close-category-modal')?.addEventListener('click', () => toggleModal(categoryModal, false));

    document.getElementById('cancel-category-modal')?.addEventListener('click', () => toggleModal(categoryModal, false));

    categoryModal?.addEventListener('click', (e) => { if (e.target === categoryModal) toggleModal(categoryModal, false); });



    document.querySelectorAll('.open-category-edit').forEach((btn) => {

        btn.addEventListener('click', () => {

            categoryTitle.textContent = 'Kategoriyi Düzenle';

            categoryForm?.reset();

            categoryForm?.querySelector('input[name="name"]')?.setAttribute('value', '');

            const nameInput = categoryForm?.querySelector('input[name="name"]');

            if (nameInput) nameInput.value = btn.dataset.name || '';

            if (categoryIdInput) categoryIdInput.value = btn.dataset.id || '';

            clearInlineError(categoryError);

            toggleModal(categoryModal, true);

        });

    });



    categoryForm?.addEventListener('submit', async (e) => {

        e.preventDefault();

        clearInlineError(categoryError);

        const formData = new FormData(categoryForm);

        const categoryId = categoryIdInput?.value;

        const isEdit = Boolean(categoryId);

        if (isEdit) formData.append('_method', 'PUT');

        const actionUrl = isEdit ? `{{ url('/portal/categories') }}/${categoryId}` : '{{ route('portal.categories.store') }}';



        const response = await fetch(actionUrl, {

            method: 'POST',

            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },

            body: formData,

        });

        const payload = await response.json().catch(() => null);

        if (!response.ok) {

            const errors = payload?.errors ? Object.values(payload.errors).flat() : [];

            showInlineError(categoryError, errors.length ? errors.join(' ') : (payload?.message || 'Kategori kaydedilemedi.'));

            return;

        }

        showToast(isEdit ? 'Kategori güncellendi.' : 'Kategori oluşturuldu.', 'success');

        setTimeout(() => window.location.reload(), 400);

    });



    // Import modal

    const importModal = document.getElementById('import-modal');

    const importForm = document.getElementById('import-form');

    const importError = document.getElementById('import-error');



    document.getElementById('open-import-modal')?.addEventListener('click', () => {

        importForm?.reset();

        clearInlineError(importError);

        toggleModal(importModal, true);

    });

    document.getElementById('close-import-modal')?.addEventListener('click', () => toggleModal(importModal, false));

    document.getElementById('cancel-import-modal')?.addEventListener('click', () => toggleModal(importModal, false));

    importModal?.addEventListener('click', (e) => { if (e.target === importModal) toggleModal(importModal, false); });



    importForm?.addEventListener('submit', async (e) => {

        e.preventDefault();

        clearInlineError(importError);

        const response = await fetch('{{ route('portal.categories.import') }}', {

            method: 'POST',

            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },

            body: new FormData(importForm),

        });

        const payload = await response.json().catch(() => null);

        if (!response.ok) {

            showInlineError(importError, payload?.message || 'İçe aktarma başarısız.');

            return;

        }

        showToast(`İçe aktarma tamamlandı. Yeni: ${payload.created}, Eşlenen: ${payload.mapped}`, 'success');

        toggleModal(importModal, false);

        setTimeout(() => window.location.reload(), 600);

    });



    // Marketplace sync + search + mapping

    const debounceMap = new Map();

    function debounce(key, fn, wait = 280) {

        if (debounceMap.has(key)) clearTimeout(debounceMap.get(key));

        debounceMap.set(key, setTimeout(fn, wait));

    }



    async function syncMarketplaceCategories(marketplaceId) {

        const response = await fetch(`{{ url('/portal/marketplace-categories') }}/${marketplaceId}/sync`, {

            method: 'POST',

            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },

        });

        const payload = await response.json().catch(() => null);

        if (!response.ok) {

            showToast(payload?.message || 'Senkron başarısız.', 'error');

            return false;

        }

        showToast(payload?.message || 'Senkron tamamlandı.', 'success');

        return true;

    }



    async function upsertMapping(categoryId, marketplaceId, externalId) {

        const response = await fetch(`{{ url('/portal/categories') }}/${categoryId}/mappings/${marketplaceId}`, {

            method: 'POST',

            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json' },

            body: JSON.stringify({ external_id: externalId }),

        });

        const payload = await response.json().catch(() => null);

        if (!response.ok) {

            showToast(payload?.message || 'Eşitleme kaydedilemedi.', 'error');

            return null;

        }

        showToast('Eşitleme kaydedildi.', 'success');

        return payload?.mapping || null;

    }



    async function deleteMapping(categoryId, marketplaceId) {

        const response = await fetch(`{{ url('/portal/categories') }}/${categoryId}/mappings/${marketplaceId}`, {

            method: 'DELETE',

            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },

        });

        const payload = await response.json().catch(() => null);

        if (!response.ok) {

            showToast(payload?.message || 'Eşitleme kaldırılamadı.', 'error');

            return false;

        }

        showToast('Eşitleme kaldırıldı.', 'success');

        return true;

    }



    document.querySelectorAll('.mp-sync').forEach((btn) => btn.addEventListener('click', async () => {

        btn.disabled = true;

        await syncMarketplaceCategories(btn.dataset.marketplaceId);

        btn.disabled = false;

    }));



    document.querySelectorAll('.mp-clear').forEach((btn) => btn.addEventListener('click', async () => {

        btn.disabled = true;

        const categoryId = btn.dataset.categoryId;

        const marketplaceId = btn.dataset.marketplaceId;

        const ok = await deleteMapping(categoryId, marketplaceId);

        btn.disabled = false;

        if (!ok) return;



        const card = document.querySelector(`[data-mapping-card="${categoryId}:${marketplaceId}"]`);

        const current = card?.querySelector('[data-current-mapping] span');

        if (current) current.textContent = '-';

        const status = card?.querySelector('[data-mapping-status]');

        if (status) status.innerHTML = `<span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-300"></span> Boş</span>`;

        btn.classList.add('hidden');



        const cell = document.querySelector(`[data-mapping-cell="${categoryId}:${marketplaceId}"]`);

        if (cell) cell.innerHTML = `<div class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs text-slate-600"><span class="h-2 w-2 rounded-full bg-slate-300"></span> Boş</div>`;

    }));



    document.querySelectorAll('.mp-search').forEach((input) => {

        const results = input.parentElement?.querySelector('.mp-results');

        const categoryId = input.dataset.categoryId;

        const marketplaceId = input.dataset.marketplaceId;

        function hideResults() { results?.classList.add('hidden'); if (results) results.innerHTML = ''; }

        input.addEventListener('blur', () => setTimeout(hideResults, 160));

        input.addEventListener('input', () => {

            const q = input.value.trim();

            if (q.length < 2) { hideResults(); return; }

            debounce(`${categoryId}:${marketplaceId}`, async () => {

                const response = await fetch(`{{ url('/portal/marketplace-categories') }}/${marketplaceId}/search?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });

                const payload = await response.json().catch(() => null);

                const items = payload?.items || [];

                if (!results) return;

                if (items.length === 0) {

                    results.innerHTML = `<div class="px-3 py-2 text-xs text-slate-500">Sonuç yok. Gerekirse "Senkronla" butonuna basın.</div>`;

                    results.classList.remove('hidden');

                    return;

                }

                results.innerHTML = items.map((item) => {

                    const label = item.path || item.name || item.external_id;

                    return `<button type="button" class="w-full text-left px-3 py-2 hover:bg-slate-50 border-b border-slate-100 last:border-b-0" data-external-id="${item.external_id}" data-label="${label.replace(/"/g,'&quot;')}"><div class="text-sm font-semibold text-slate-800 truncate">${label}</div><div class="text-[11px] text-slate-500 mt-1">ID: ${item.external_id}</div></button>`;

                }).join('');

                results.classList.remove('hidden');

                results.querySelectorAll('button[data-external-id]').forEach((btn) => btn.addEventListener('click', async () => {

                    hideResults();

                    const externalId = btn.dataset.externalId;

                    const label = btn.dataset.label;

                    input.value = '';

                    const mapping = await upsertMapping(categoryId, marketplaceId, externalId);

                    if (!mapping) return;



                    const card = document.querySelector(`[data-mapping-card="${categoryId}:${marketplaceId}"]`);

                    const current = card?.querySelector('[data-current-mapping]');

                    if (current) current.innerHTML = `Seçili: <span class="font-semibold text-slate-700">${mapping.path || label || externalId}</span>`;

                    const status = card?.querySelector('[data-mapping-status]');

                    if (status) status.innerHTML = `<span class="inline-flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Eşlendi</span>`;

                    card?.querySelector('.mp-clear')?.classList.remove('hidden');



                    const cell = document.querySelector(`[data-mapping-cell="${categoryId}:${marketplaceId}"]`);

                    if (cell) {

                        const display = (mapping.path || label || externalId).replace(/</g,'&lt;');

                        cell.innerHTML = `<div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs text-emerald-700"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Eşlendi</div><div class="text-xs text-slate-500 mt-2 truncate max-w-[220px]">${display}</div>`;

                    }

                }));

            });

        });

    });

</script>

@endpush








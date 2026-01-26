@extends('layouts.admin')

@section('title', 'Yeni Urun')
@section('page-title', 'Yeni Urun Ekle')

@section('content')
<div class="bg-white rounded-lg shadow p-6">
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SKU (Stok Kodu) *</label>
                <input type="text" name="sku" value="{{ old('sku') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                @error('sku')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Barkod/GTIN</label>
                <div class="flex items-center gap-2">
                    <input id="barcode-input" type="text" name="barcode" value="{{ old('barcode') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button id="barcode-generate" type="button" class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold border border-slate-200 rounded-md bg-slate-50 text-slate-600 hover:bg-slate-100">
                        <i class="fa-solid fa-barcode text-[10px]"></i>
                        Uret
                    </button>
                </div>
                @error('barcode')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Urun Adi *</label>
                <input id="product-name" type="text" name="name" value="{{ old('name') }}" maxlength="150"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <p class="text-xs text-gray-500 mt-1">Maksimum 150 karakter. <span id="name-counter">0/150</span></p>
                @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Urun Aciklamasi</label>
                <textarea id="description-editor" name="description" rows="6"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('description') }}</textarea>
                @error('description')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Marka</label>
                <div class="flex items-center gap-2">
                    <input id="brand-input" type="text" name="brand" list="brand-options" value="{{ old('brand') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Marka ara veya yaz">
                    <button id="open-brand-modal" type="button" class="px-3 py-2 text-xs font-semibold border border-gray-300 rounded-md hover:bg-gray-50 hidden">
                        Yeni Ekle
                    </button>
                </div>
                <datalist id="brand-options">
                    @foreach($brands ?? [] as $brand)
                        <option value="{{ $brand->name }}"></option>
                    @endforeach
                </datalist>
                @error('brand')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                <div class="flex items-center gap-2">
                    <input id="category-input" type="text" name="category" list="category-options" value="{{ old('category') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Kategori ara veya yaz">
                    <button id="open-category-modal" type="button" class="px-3 py-2 text-xs font-semibold border border-gray-300 rounded-md hover:bg-gray-50 hidden">
                        Yeni Ekle
                    </button>
                </div>
                <datalist id="category-options">
                    @foreach($categories ?? [] as $category)
                        <option value="{{ $category->name }}"></option>
                    @endforeach
                </datalist>
                @error('category')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Satis Fiyati (TRY) *</label>
                <input type="number" step="0.01" name="price" value="{{ old('price') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                @error('price')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Maliyet Fiyati (TRY)</label>
                <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('cost_price')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Stok Miktari *</label>
                <input type="number" name="stock_quantity" value="{{ old('stock_quantity', 0) }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                @error('stock_quantity')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Agirlik (KG)</label>
                <input type="number" step="0.01" name="weight" value="{{ old('weight') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('weight')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Desi</label>
                <div class="flex items-center gap-2">
                    <input id="desi-input" type="number" step="0.01" name="desi" value="{{ old('desi') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button id="open-desi-modal" type="button" class="inline-flex items-center gap-1 px-2 py-1 text-[11px] font-semibold border border-slate-200 rounded-md bg-slate-50 text-slate-600 hover:bg-slate-100">
                        <i class="fa-solid fa-cube text-[10px]"></i>
                        Desi Hesapla
                    </button>
                </div>
                @error('desi')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Urun KDV Orani</label>
                <select name="vat_rate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Seciniz</option>
                    <option value="0" @selected(old('vat_rate') == 0)>%0</option>
                    <option value="1" @selected(old('vat_rate') == 1)>%1</option>
                    <option value="10" @selected(old('vat_rate') == 10)>%10</option>
                    <option value="20" @selected(old('vat_rate') == 20)>%20</option>
                </select>
                @error('vat_rate')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" checked
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">
                    <span class="ml-2 text-sm text-gray-600">Aktif</span>
                </label>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-slate-200 p-6 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Urun Resimleri</h3>
            <label for="product-images" class="block border-2 border-dashed border-blue-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-400">
                <div class="text-blue-600 text-sm font-medium">Resimlerinizi bu alana surukleyip birakin ya da tiklayin</div>
                <div class="text-xs text-gray-500 mt-1">PNG, JPG veya WEBP (max 5MB)</div>
                <input id="product-images" type="file" name="images[]" multiple accept="image/*" class="hidden">
            </label>
            @error('images.*')
                <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
            @enderror
            <div id="image-preview" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-3"></div>
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                Iptal
            </a>
            <button type="submit" class="btn btn-solid-accent">
                Kaydet
            </button>
        </div>
    </form>
</div>

<div id="desi-modal" class="fixed inset-0 bg-slate-900/40 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden border border-slate-100">
        <div class="px-6 py-5 bg-gradient-to-r from-slate-50 to-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="h-11 w-11 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500">
                    <i class="fa-solid fa-cubes"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Desi Hesaplama</h3>
                    <p class="text-xs text-slate-500 mt-1">Hacim veya agirliktan buyuk olan deger baz alinir.</p>
                </div>
            </div>
            <button id="close-desi-modal" type="button" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="px-6 py-5 bg-slate-50/50 border-b border-slate-100">
            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span class="px-2 py-1 rounded-full bg-white border border-slate-200">En x Boy x Yukseklik / 3000</span>
                <span class="px-2 py-1 rounded-full bg-white border border-slate-200">Agirlik ile karsilastirilir</span>
            </div>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">En (CM)</label>
                <input id="desi-width" type="number" step="0.01" class="w-full px-3 py-2 border border-slate-300 rounded-md">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Boy (CM)</label>
                <input id="desi-length" type="number" step="0.01" class="w-full px-3 py-2 border border-slate-300 rounded-md">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Yukseklik (CM)</label>
                <input id="desi-height" type="number" step="0.01" class="w-full px-3 py-2 border border-slate-300 rounded-md">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Agirlik (KG)</label>
                <input id="desi-weight" type="number" step="0.01" class="w-full px-3 py-2 border border-slate-300 rounded-md">
            </div>
        </div>
        <div class="px-6 pb-6">
            <button id="desi-calc-btn" type="button" class="btn btn-solid-accent w-full">
                Hesapla
            </button>
        </div>
    </div>
</div>

<div id="brand-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-800">Yeni Marka</h3>
            <button id="close-brand-modal" type="button" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="brand-modal-form" class="space-y-4">
            @csrf
            <div id="brand-modal-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Marka Adi</label>
                <input id="brand-modal-name" type="text" name="name" class="mt-1 w-full" required>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit">Kaydet</button>
                <button id="cancel-brand-modal" type="button" class="btn btn-outline-accent">Vazgec</button>
            </div>
        </form>
    </div>
</div>

<div id="category-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-slate-800">Yeni Kategori</h3>
            <button id="close-category-modal" type="button" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="category-modal-form" class="space-y-4">
            @csrf
            <div id="category-modal-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Kategori Adi</label>
                <input id="category-modal-name" type="text" name="name" class="mt-1 w-full" required>
            </div>
            <div class="flex items-center gap-3">
                <button type="submit">Kaydet</button>
                <button id="cancel-category-modal" type="button" class="btn btn-outline-accent">Vazgec</button>
            </div>
        </form>
    </div>
<\/div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
    const nameInput = document.getElementById('product-name');
    const nameCounter = document.getElementById('name-counter');
    const updateNameCounter = () => {
        if (!nameInput || !nameCounter) return;
        nameCounter.textContent = `${nameInput.value.length}/150`;
    };
    nameInput?.addEventListener('input', updateNameCounter);
    updateNameCounter();

    const initTinyMce = () => {
        if (!window.tinymce) return;
        window.tinymce.init({
            selector: '#description-editor',
            height: 260,
            menubar: true,
            branding: false,
            promotion: false,
            plugins: 'lists link blockquote table code',
            toolbar: 'undo redo | styles | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist | blockquote | table | link | removeformat | code',
            style_formats: [
                { title: 'Paragraf', format: 'p' },
                { title: 'Baslik 2', format: 'h2' },
                { title: 'Baslik 3', format: 'h3' },
            ],
            content_style: 'body { font-family: Manrope, sans-serif; font-size: 14px; }',
        });
    };
    window.addEventListener('load', initTinyMce);

    const barcodeInput = document.getElementById('barcode-input');
    const barcodeGenerate = document.getElementById('barcode-generate');
    const generateEan13 = () => {
        const base = Array.from({ length: 12 }, () => Math.floor(Math.random() * 10)).join('');
        let sum = 0;
        for (let i = 0; i < base.length; i++) {
            const digit = parseInt(base[i], 10);
            sum += (i % 2 === 0) ? digit : digit * 3;
        }
        const check = (10 - (sum % 10)) % 10;
        return `${base}${check}`;
    };
    barcodeGenerate?.addEventListener('click', () => {
        if (barcodeInput) {
            barcodeInput.value = generateEan13();
        }
    });

    const imageInput = document.getElementById('product-images');
    const imagePreview = document.getElementById('image-preview');
    imageInput?.addEventListener('change', () => {
        if (!imagePreview || !imageInput.files) return;
        imagePreview.innerHTML = '';
        Array.from(imageInput.files).forEach((file) => {
            const item = document.createElement('div');
            item.className = 'border border-slate-200 rounded-lg p-2 text-xs text-slate-600';
            item.textContent = file.name;
            imagePreview.appendChild(item);
        });
    });

    const desiModal = document.getElementById('desi-modal');
    const openDesiModal = document.getElementById('open-desi-modal');
    const closeDesiModal = document.getElementById('close-desi-modal');
    const desiCalcBtn = document.getElementById('desi-calc-btn');
    const desiInput = document.getElementById('desi-input');
    const desiWidth = document.getElementById('desi-width');
    const desiLength = document.getElementById('desi-length');
    const desiHeight = document.getElementById('desi-height');
    const desiWeight = document.getElementById('desi-weight');

    const toggleDesiModal = (show) => {
        desiModal?.classList.toggle('hidden', !show);
        desiModal?.classList.toggle('flex', show);
    };

    openDesiModal?.addEventListener('click', () => toggleDesiModal(true));
    closeDesiModal?.addEventListener('click', () => toggleDesiModal(false));
    desiCalcBtn?.addEventListener('click', () => {
        const width = parseFloat(desiWidth?.value || '0');
        const length = parseFloat(desiLength?.value || '0');
        const height = parseFloat(desiHeight?.value || '0');
        const weight = parseFloat(desiWeight?.value || '0');
        let volumetric = 0;
        if (width > 0 && length > 0 && height > 0) {
            volumetric = (width * length * height) / 3000;
        }
        let desiValue = 0;
        if (volumetric > 0 && weight > 0) {
            desiValue = Math.max(volumetric, weight);
        } else if (volumetric > 0) {
            desiValue = volumetric;
        } else if (weight > 0) {
            desiValue = weight;
        }
        if (desiInput && desiValue > 0) {
            desiInput.value = desiValue.toFixed(2);
            toggleDesiModal(false);
        }
    });

    const brandInput = document.getElementById('brand-input');
    const brandOptions = document.getElementById('brand-options');
    const brandModal = document.getElementById('brand-modal');
    const brandOpenBtn = document.getElementById('open-brand-modal');
    const brandCloseBtn = document.getElementById('close-brand-modal');
    const brandCancelBtn = document.getElementById('cancel-brand-modal');
    const brandForm = document.getElementById('brand-modal-form');
    const brandError = document.getElementById('brand-modal-error');
    const brandModalName = document.getElementById('brand-modal-name');
    const brandValues = new Set(Array.from(brandOptions?.options || []).map((opt) => opt.value.toLowerCase()));

    const toggleBrandModal = (show) => {
        brandModal?.classList.toggle('hidden', !show);
        brandModal?.classList.toggle('flex', show);
    };
    const toggleBrandButton = () => {
        const value = (brandInput?.value || '').trim().toLowerCase();
        if (brandOpenBtn) {
            brandOpenBtn.classList.toggle('hidden', value === '' || brandValues.has(value));
        }
    };
    brandInput?.addEventListener('input', toggleBrandButton);
    toggleBrandButton();
    brandOpenBtn?.addEventListener('click', () => {
        if (brandModalName) {
            brandModalName.value = brandInput?.value || '';
        }
        brandError?.classList.add('hidden');
        toggleBrandModal(true);
    });
    brandCloseBtn?.addEventListener('click', () => toggleBrandModal(false));
    brandCancelBtn?.addEventListener('click', () => toggleBrandModal(false));
    brandForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!brandModalName?.value) return;
        brandError?.classList.add('hidden');
        const formData = new FormData(brandForm);
        const response = await fetch('{{ route('admin.brands.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });
        const payload = await response.json().catch(() => null);
        if (!response.ok) {
            brandError.textContent = payload?.message || 'Marka kaydedilemedi.';
            brandError.classList.remove('hidden');
            return;
        }
        const newOption = document.createElement('option');
        newOption.value = payload.name;
        brandOptions?.appendChild(newOption);
        brandValues.add(payload.name.toLowerCase());
        if (brandInput) {
            brandInput.value = payload.name;
        }
        toggleBrandModal(false);
        toggleBrandButton();
        brandForm.reset();
    });

    const categoryInput = document.getElementById('category-input');
    const categoryOptions = document.getElementById('category-options');
    const categoryModal = document.getElementById('category-modal');
    const categoryOpenBtn = document.getElementById('open-category-modal');
    const categoryCloseBtn = document.getElementById('close-category-modal');
    const categoryCancelBtn = document.getElementById('cancel-category-modal');
    const categoryForm = document.getElementById('category-modal-form');
    const categoryError = document.getElementById('category-modal-error');
    const categoryModalName = document.getElementById('category-modal-name');
    const categoryValues = new Set(Array.from(categoryOptions?.options || []).map((opt) => opt.value.toLowerCase()));

    const toggleCategoryModal = (show) => {
        categoryModal?.classList.toggle('hidden', !show);
        categoryModal?.classList.toggle('flex', show);
    };
    const toggleCategoryButton = () => {
        const value = (categoryInput?.value || '').trim().toLowerCase();
        if (categoryOpenBtn) {
            categoryOpenBtn.classList.toggle('hidden', value === '' || categoryValues.has(value));
        }
    };
    categoryInput?.addEventListener('input', toggleCategoryButton);
    toggleCategoryButton();
    categoryOpenBtn?.addEventListener('click', () => {
        if (categoryModalName) {
            categoryModalName.value = categoryInput?.value || '';
        }
        categoryError?.classList.add('hidden');
        toggleCategoryModal(true);
    });
    categoryCloseBtn?.addEventListener('click', () => toggleCategoryModal(false));
    categoryCancelBtn?.addEventListener('click', () => toggleCategoryModal(false));
    categoryForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!categoryModalName?.value) return;
        categoryError?.classList.add('hidden');
        const formData = new FormData(categoryForm);
        const response = await fetch('{{ route('admin.categories.store') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });
        const payload = await response.json().catch(() => null);
        if (!response.ok) {
            categoryError.textContent = payload?.message || 'Kategori kaydedilemedi.';
            categoryError.classList.remove('hidden');
            return;
        }
        const newOption = document.createElement('option');
        newOption.value = payload.name;
        categoryOptions?.appendChild(newOption);
        categoryValues.add(payload.name.toLowerCase());
        if (categoryInput) {
            categoryInput.value = payload.name;
        }
        toggleCategoryModal(false);
        toggleCategoryButton();
        categoryForm.reset();
    });
</script>
@endpush

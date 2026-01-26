@extends('layouts.admin')

@section('header')
    Kategoriler
@endsection

@section('content')
    @include('admin.products.partials.catalog-tabs')

    <div class="panel-card p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Kategori Yönetimi</h3>
                <p class="text-xs text-slate-500 mt-1">Ürün kategorilerini burada yönetin.</p>
            </div>
            <button id="open-category-modal" type="button" class="btn btn-solid-accent">
                <i class="fa-solid fa-plus text-xs mr-2"></i>
                Yeni Kategori
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left">
                        <th>Ad</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr class="border-t border-slate-100">
                            <td class="font-medium text-slate-900">{{ $category->name }}</td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button" class="text-slate-600 hover:text-slate-900 mr-3 open-category-edit"
                                        data-id="{{ $category->id }}" data-name="{{ $category->name }}">
                                    Düzenle
                                </button>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Kategori silinsin mi?')">Sil</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="border-t border-slate-100">
                            <td colspan="2" class="py-6 text-center text-slate-500">Kategori bulunamadı.</td>
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
                <h3 class="text-sm font-semibold text-slate-800">Yeni Kategori</h3>
                <button id="close-category-modal" type="button" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
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
                    <button type="submit">Kaydet</button>
                    <button id="cancel-category-modal" type="button" class="text-slate-500 hover:text-slate-700">Vazgeç</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const categoryOpenBtn = document.getElementById('open-category-modal');
    const categoryModal = document.getElementById('category-modal');
    const categoryCloseBtn = document.getElementById('close-category-modal');
    const categoryCancelBtn = document.getElementById('cancel-category-modal');

    function toggleCategoryModal(show) {
        if (!categoryModal) return;
        categoryModal.classList.toggle('hidden', !show);
        categoryModal.classList.toggle('flex', show);
    }

    categoryOpenBtn?.addEventListener('click', () => {
        if (categoryForm) {
            categoryForm.reset();
        }
        if (categoryIdInput) {
            categoryIdInput.value = '';
        }
        clearCategoryError();
        toggleCategoryModal(true);
    });
    categoryCloseBtn?.addEventListener('click', () => {
        if (categoryForm) {
            categoryForm.reset();
        }
        if (categoryIdInput) {
            categoryIdInput.value = '';
        }
        toggleCategoryModal(false);
    });
    categoryCancelBtn?.addEventListener('click', () => {
        if (categoryForm) {
            categoryForm.reset();
        }
        if (categoryIdInput) {
            categoryIdInput.value = '';
        }
        toggleCategoryModal(false);
    });
</script>
<script>
    const categoryForm = document.getElementById('category-modal-form');
    const categoryError = document.getElementById('category-modal-error');

    function showCategoryError(message) {
        if (!categoryError) return;
        categoryError.textContent = message;
        categoryError.classList.remove('hidden');
    }

    function clearCategoryError() {
        if (!categoryError) return;
        categoryError.textContent = '';
        categoryError.classList.add('hidden');
    }

    const categoryEditButtons = document.querySelectorAll('.open-category-edit');
    const categoryIdInput = categoryForm?.querySelector('input[name="category_id"]');

    categoryEditButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const nameInput = categoryForm?.querySelector('input[name="name"]');
            if (!nameInput || !categoryIdInput) return;
            nameInput.value = btn.dataset.name || '';
            categoryIdInput.value = btn.dataset.id || '';
            clearCategoryError();
            toggleCategoryModal(true);
        });
    });

    categoryForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearCategoryError();
        const formData = new FormData(categoryForm);
        const categoryId = categoryIdInput?.value;
        const isEdit = Boolean(categoryId);
        if (isEdit) {
            formData.append('_method', 'PUT');
        }
        const actionUrl = isEdit
            ? `{{ url('/admin/categories') }}/${categoryId}`
            : '{{ route('admin.categories.store') }}';
        const response = await fetch(actionUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: formData,
        });

        const contentType = response.headers.get('content-type') ?? '';
        const payload = contentType.includes('application/json') ? await response.json().catch(() => null) : null;

        if (!response.ok) {
            if (response.status === 422) {
                const errors = payload?.errors ? Object.values(payload.errors).flat() : [];
                showCategoryError(errors.length ? errors.join(' ') : 'Lütfen bilgileri kontrol edin.');
            } else {
                showCategoryError('Kategori kaydedilemedi. Lütfen tekrar deneyin.');
            }
            return;
        }

        const tableBody = document.querySelector('table tbody');
        if (tableBody && payload) {
            if (isEdit) {
                const existingRow = tableBody.querySelector(`button.open-category-edit[data-id="${payload.id}"]`)?.closest('tr');
                if (existingRow) {
                    const nameCell = existingRow.querySelector('td');
                    if (nameCell) {
                        nameCell.textContent = payload.name;
                    }
                    const editButton = existingRow.querySelector('button.open-category-edit');
                    if (editButton) {
                        editButton.dataset.name = payload.name;
                    }
                }
            } else {
                const row = document.createElement('tr');
                row.className = 'border-t border-slate-100';
                row.innerHTML = `
                    <td class="font-medium text-slate-900">${payload.name}</td>
                    <td class="text-right whitespace-nowrap">
                        <button type="button" class="text-slate-600 hover:text-slate-900 mr-3 open-category-edit" data-id="${payload.id}" data-name="${payload.name}">Düzenle</button>
                        <form method="POST" action="{{ url('/admin/categories') }}/${payload.id}" class="inline">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Kategori silinsin mi?')">Sil</button>
                        </form>
                    </td>
                `;
                tableBody.prepend(row);
                row.querySelector('.open-category-edit')?.addEventListener('click', () => {
                    const nameInput = categoryForm?.querySelector('input[name="name"]');
                    if (!nameInput || !categoryIdInput) return;
                    nameInput.value = payload.name || '';
                    categoryIdInput.value = payload.id || '';
                    clearCategoryError();
                    toggleCategoryModal(true);
                });
            }
        }

        categoryForm.reset();
        if (categoryIdInput) {
            categoryIdInput.value = '';
        }
        toggleCategoryModal(false);
    });
</script>
@endpush

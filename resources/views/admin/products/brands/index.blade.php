@extends('layouts.admin')



@section('header')

    Markalar

@endsection



@section('content')

    @include('admin.products.partials.catalog-tabs')



    <div class="panel-card p-6">

        <div class="flex items-center justify-between mb-4">

            <div>

                <h3 class="text-sm font-semibold text-slate-800">Marka Yönetimi</h3>

                <p class="text-xs text-slate-500 mt-1">Ürün markalarını burada yönetin.</p>

            </div>

            <button id="open-brand-modal" type="button" class="btn btn-solid-accent">

                <i class="fa-solid fa-plus text-xs mr-2"></i>

                Yeni Marka

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

                    @forelse($brands as $brand)

                        <tr class="border-t border-slate-100">

                            <td class="font-medium text-slate-900">{{ $brand->name }}</td>

                            <td class="text-right whitespace-nowrap">

                                <button type="button" class="text-slate-600 hover:text-slate-900 mr-3 open-brand-edit"

                                        data-id="{{ $brand->id }}" data-name="{{ $brand->name }}">

                                    Düzenle

                                </button>

                                <form method="POST" action="{{ route('portal.brands.destroy', $brand) }}" class="inline">

                                    @csrf

                                    @method('DELETE')

                                    <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Marka silinsin mi?')">Sil</button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr class="border-t border-slate-100">

                            <td colspan="2" class="py-6 text-center text-slate-500">Marka bulunamadı.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>



        <div class="mt-4">

            {{ $brands->links() }}

        </div>

    </div>



    <div id="brand-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">

        <div class="bg-white rounded-xl shadow-lg w-full max-w-lg p-6">

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

                    <label class="block text-sm font-medium text-slate-700">Marka Adı</label>

                    <input type="text" name="name" class="mt-1 w-full" required>

                </div>

                <input type="hidden" name="brand_id" value="">

                <div class="flex items-center gap-3">

                    <button type="submit">Kaydet</button>

                    <button id="cancel-brand-modal" type="button" class="text-slate-500 hover:text-slate-700">Vazgeç</button>

                </div>

            </form>

        </div>

    </div>

@endsection



@push('scripts')

<script>

    const brandOpenBtn = document.getElementById('open-brand-modal');

    const brandModal = document.getElementById('brand-modal');

    const brandCloseBtn = document.getElementById('close-brand-modal');

    const brandCancelBtn = document.getElementById('cancel-brand-modal');



    function toggleBrandModal(show) {

        if (!brandModal) return;

        brandModal.classList.toggle('hidden', !show);

        brandModal.classList.toggle('flex', show);

    }



    brandOpenBtn?.addEventListener('click', () => {

        if (brandForm) {

            brandForm.reset();

        }

        if (brandIdInput) {

            brandIdInput.value = '';

        }

        clearBrandError();

        toggleBrandModal(true);

    });

    brandCloseBtn?.addEventListener('click', () => {

        if (brandForm) {

            brandForm.reset();

        }

        if (brandIdInput) {

            brandIdInput.value = '';

        }

        toggleBrandModal(false);

    });

    brandCancelBtn?.addEventListener('click', () => {

        if (brandForm) {

            brandForm.reset();

        }

        if (brandIdInput) {

            brandIdInput.value = '';

        }

        toggleBrandModal(false);

    });

</script>

<script>

    const brandForm = document.getElementById('brand-modal-form');

    const brandError = document.getElementById('brand-modal-error');



    function showBrandError(message) {

        if (!brandError) return;

        brandError.textContent = message;

        brandError.classList.remove('hidden');

    }



    function clearBrandError() {

        if (!brandError) return;

        brandError.textContent = '';

        brandError.classList.add('hidden');

    }



    const brandEditButtons = document.querySelectorAll('.open-brand-edit');

    const brandIdInput = brandForm?.querySelector('input[name="brand_id"]');



    brandEditButtons.forEach((btn) => {

        btn.addEventListener('click', () => {

            const nameInput = brandForm?.querySelector('input[name="name"]');

            if (!nameInput || !brandIdInput) return;

            nameInput.value = btn.dataset.name || '';

            brandIdInput.value = btn.dataset.id || '';

            clearBrandError();

            toggleBrandModal(true);

        });

    });



    brandForm?.addEventListener('submit', async (event) => {

        event.preventDefault();

        clearBrandError();

        const formData = new FormData(brandForm);

        const brandId = brandIdInput?.value;

        const isEdit = Boolean(brandId);

        if (isEdit) {

            formData.append('_method', 'PUT');

        }

        const actionUrl = isEdit

            ? `{{ url('/portal/brands') }}/${brandId}`

            : '{{ route('portal.brands.store') }}';

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

                showBrandError(errors.length ? errors.join(' ') : 'Lütfen bilgileri kontrol edin.');

            } else {

                showBrandError('Marka kaydedilemedi. Lütfen tekrar deneyin.');

            }

            return;

        }



        const tableBody = document.querySelector('table tbody');

        if (tableBody && payload) {

            if (isEdit) {

                const existingRow = tableBody.querySelector(`button.open-brand-edit[data-id="${payload.id}"]`)?.closest('tr');

                if (existingRow) {

                    const nameCell = existingRow.querySelector('td');

                    if (nameCell) {

                        nameCell.textContent = payload.name;

                    }

                    const editButton = existingRow.querySelector('button.open-brand-edit');

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

                        <button type="button" class="text-slate-600 hover:text-slate-900 mr-3 open-brand-edit" data-id="${payload.id}" data-name="${payload.name}">Düzenle</button>

                        <form method="POST" action="{{ url('/portal/brands') }}/${payload.id}" class="inline">

                            <input type="hidden" name="_token" value="{{ csrf_token() }}">

                            <input type="hidden" name="_method" value="DELETE">

                            <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Marka silinsin mi?')">Sil</button>

                        </form>

                    </td>

                `;

                tableBody.prepend(row);

                row.querySelector('.open-brand-edit')?.addEventListener('click', () => {

                    const nameInput = brandForm?.querySelector('input[name="name"]');

                    if (!nameInput || !brandIdInput) return;

                    nameInput.value = payload.name || '';

                    brandIdInput.value = payload.id || '';

                    clearBrandError();

                    toggleBrandModal(true);

                });

            }

        }



        brandForm.reset();

        if (brandIdInput) {

            brandIdInput.value = '';

        }

        toggleBrandModal(false);

    });

</script>

@endpush








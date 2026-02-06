@extends('layouts.super-admin')



@section('header')

    Fatura Oluştur

@endsection



@section('content')

    <div class="panel-card p-6 max-w-3xl">

        <form method="POST" action="{{ route('super-admin.invoices.store') }}" class="space-y-5">

            @csrf



            <div>

                <label class="block text-sm font-medium text-slate-700">Abone Ara</label>

                <input id="subscriber-search" type="text" class="mt-1 w-full" placeholder="En az 2 harf girin">

                <div id="subscriber-results" class="mt-2 hidden rounded-lg border border-slate-200 bg-white text-sm"></div>

                <div id="subscriber-empty" class="mt-2 hidden rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">

                    <div class="flex items-center justify-between gap-3">

                        <span>Abone bulunamadı.</span>

                    </div>

                    <button id="open-customer-modal" type="button" class="mt-3 inline-flex w-full items-center justify-center gap-3 rounded-lg border border-[#ff4439] bg-[#ff4439] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:border-[#e83a31] hover:bg-[#e83a31]">

                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-white/20 bg-white/15">

                            <i class="fa-solid fa-plus text-xs"></i>

                        </span>

                        MÜŞTERİ EKLE

                    </button>

                </div>

                <input id="subscriber-id" type="hidden" name="user_id" value="{{ old('user_id') }}">

            </div>



            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2">

                    <label class="block text-sm font-medium text-slate-700">Müşteri Adı</label>

                    <input id="customer-name" name="customer_name" type="text" class="mt-1 w-full" value="{{ old('customer_name') }}" required>

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">E-posta</label>

                    <input id="customer-email" name="customer_email" type="email" class="mt-1 w-full" value="{{ old('customer_email') }}" required>

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">Para Birimi</label>

                    <select name="currency" class="mt-1 w-full">

                        <option value="TRY" @selected(old('currency', 'TRY') === 'TRY')>TRY</option>

                        <option value="USD" @selected(old('currency') === 'USD')>USD</option>

                        <option value="EUR" @selected(old('currency') === 'EUR')>EUR</option>

                    </select>

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">Tutar</label>

                    <input name="amount" type="number" step="0.01" class="mt-1 w-full" value="{{ old('amount') }}" required>

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">Durum</label>

                    <select name="status" class="mt-1 w-full">

                        <option value="paid" @selected(old('status', 'paid') === 'paid')>Ödendi</option>

                        <option value="pending" @selected(old('status') === 'pending')>Beklemede</option>

                        <option value="failed" @selected(old('status') === 'failed')>Başarısız</option>

                        <option value="refunded" @selected(old('status') === 'refunded')>İade</option>

                    </select>

                </div>

                <div class="md:col-span-2">

                    <label class="block text-sm font-medium text-slate-700">Fatura Adresi</label>

                    <textarea name="billing_address" rows="3" class="mt-1 w-full">{{ old('billing_address') }}</textarea>

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">Fatura Tarihi</label>

                    <input name="issued_at" type="date" class="mt-1 w-full" value="{{ old('issued_at', now()->format('Y-m-d')) }}" required>

                </div>

                <div class="md:col-span-2">

                    <label class="block text-sm font-medium text-slate-700">Not</label>

                    <textarea name="notes" rows="3" class="mt-1 w-full">{{ old('notes') }}</textarea>

                </div>

            </div>



            <div class="flex items-center gap-3">

                <button type="submit">Fatura Oluştur</button>

                <a href="{{ route('super-admin.invoices.index') }}" class="btn btn-outline-accent">Vazgeç</a>

            </div>

        </form>

    </div>



    <div id="customer-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">

        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-6">

            <div class="flex items-center justify-between mb-4">

                <h3 class="text-sm font-semibold text-slate-800">Yeni Müşteri</h3>

                <button id="close-customer-modal" type="button" class="text-slate-400 hover:text-slate-600">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>



            <form id="customer-modal-form" class="space-y-4">

                <div id="customer-modal-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">Mevcut Abone (opsiyonel)</label>

                    <select name="user_id" class="mt-1 w-full">

                        <option value="">Bağlı değil</option>

                        @foreach($clients as $client)

                            <option value="{{ $client->id }}" data-name="{{ $client->name }}" data-email="{{ $client->email }}">

                                {{ $client->name }}

                            </option>

                        @endforeach

                    </select>

                </div>



                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="md:col-span-2">

                        <label class="block text-sm font-medium text-slate-700">Müşteri Adı</label>

                        <input name="name" type="text" class="mt-1 w-full" required>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">E-posta</label>

                        <input name="email" type="email" class="mt-1 w-full" required>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">Telefon</label>

                        <input name="phone" type="text" class="mt-1 w-full">

                    </div>

                </div>



                <div class="bg-white border border-slate-200 rounded-lg p-4">

                    <h3 class="text-sm font-semibold text-slate-800">Adres Bilgileri</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                        <div>

                            <label class="block text-sm font-medium text-slate-700">İl</label>

                            <select id="modal-city" name="city" class="mt-1 w-full">

                                <option value="">Seçiniz</option>

                            </select>

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-slate-700">İlçe</label>

                            <select id="modal-district" name="district" class="mt-1 w-full">

                                <option value="">Seçiniz</option>

                            </select>

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-slate-700">Mahalle</label>

                            <input name="neighborhood" type="text" class="mt-1 w-full">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-slate-700">Sokak</label>

                            <input name="street" type="text" class="mt-1 w-full">

                        </div>

                        <div class="md:col-span-2">

                            <label class="block text-sm font-medium text-slate-700">Açık Adres</label>

                            <textarea name="billing_address" rows="3" class="mt-1 w-full"></textarea>

                        </div>

                    </div>

                </div>



                <div class="bg-white border border-slate-200 rounded-lg p-4">

                    <h3 class="text-sm font-semibold text-slate-800">Müşteri Türü</h3>

                    <div class="flex flex-wrap gap-4 mt-4 text-sm">

                        <label class="inline-flex items-center gap-2">

                            <input type="radio" name="customer_type" value="corporate" class="rounded">

                            <span>Tüzel Kişi</span>

                        </label>

                        <label class="inline-flex items-center gap-2">

                            <input type="radio" name="customer_type" value="individual" class="rounded" checked>

                            <span>Gerçek Kişi</span>

                        </label>

                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                        <div id="modal-company-title" class="md:col-span-2">

                            <label class="block text-sm font-medium text-slate-700">Firma Ünvanı</label>

                            <input name="company_title" type="text" class="mt-1 w-full">

                        </div>

                        <div>

                            <label id="modal-tax-label" class="block text-sm font-medium text-slate-700">TC Kimlik Numarası</label>

                            <input name="tax_id" type="text" class="mt-1 w-full">

                        </div>

                        <div id="modal-tax-office" class="hidden">

                            <label class="block text-sm font-medium text-slate-700">Vergi Dairesi</label>

                            <input name="tax_office" type="text" class="mt-1 w-full">

                        </div>

                    </div>

                </div>



                <div class="flex items-center gap-3">

                    <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                    <button id="cancel-customer-modal" type="button" class="btn btn-outline-accent">Vazgeç</button>

                </div>

            </form>

        </div>

    </div>

@endsection



@push('scripts')

<script>

    const searchInput = document.getElementById('subscriber-search');

    const results = document.getElementById('subscriber-results');

    const emptyState = document.getElementById('subscriber-empty');

    const idInput = document.getElementById('subscriber-id');

    const nameInput = document.getElementById('customer-name');

    const emailInput = document.getElementById('customer-email');

    const openModalBtn = document.getElementById('open-customer-modal');

    const modal = document.getElementById('customer-modal');

    const closeModalBtn = document.getElementById('close-customer-modal');

    const cancelModalBtn = document.getElementById('cancel-customer-modal');

    const modalForm = document.getElementById('customer-modal-form');

    const modalError = document.getElementById('customer-modal-error');

    const modalCity = document.getElementById('modal-city');

    const modalDistrict = document.getElementById('modal-district');

    const modalTaxLabel = document.getElementById('modal-tax-label');

    const modalTaxOffice = document.getElementById('modal-tax-office');

    const modalCompanyTitle = document.getElementById('modal-company-title');

    let timer;



    function clearResults() {

        results.innerHTML = '';

        results.classList.add('hidden');

    }



    function showEmpty() {

        emptyState.classList.remove('hidden');

    }



    function hideEmpty() {

        emptyState.classList.add('hidden');

    }



    searchInput?.addEventListener('input', () => {

        clearTimeout(timer);

        const query = searchInput.value.trim();

        if (query.length < 2) {

            clearResults();

            hideEmpty();

            return;

        }

        timer = setTimeout(async () => {

            const response = await fetch(`{{ route('super-admin.invoices.subscribers') }}?q=${encodeURIComponent(query)}`);

            const data = await response.json();

            clearResults();

            hideEmpty();

            if (!data.length) {

                showEmpty();

                return;

            }

            results.classList.remove('hidden');

            data.forEach((item) => {

                const row = document.createElement('button');

                row.type = 'button';

                row.className = 'w-full text-left px-3 py-2 hover:bg-slate-50';

                row.innerHTML = `<div class="text-sm font-medium text-slate-900">${item.name ?? '-'}</div><div class="text-xs text-slate-500">${item.email ?? ''}</div>`;

                row.addEventListener('click', () => {

                    idInput.value = item.id;

                    nameInput.value = item.name ?? '';

                    emailInput.value = item.email ?? '';

                    clearResults();

                });

                results.appendChild(row);

            });

        }, 250);

    });



    function toggleModal(show) {

        if (!modal) return;

        modal.classList.toggle('hidden', !show);

        modal.classList.toggle('flex', show);

    }



    function showModalError(message) {

        if (!modalError) return;

        modalError.textContent = message;

        modalError.classList.remove('hidden');

    }



    function clearModalError() {

        if (!modalError) return;

        modalError.textContent = '';

        modalError.classList.add('hidden');

    }



    openModalBtn?.addEventListener('click', () => {

        clearModalError();

        toggleModal(true);

    });

    closeModalBtn?.addEventListener('click', () => toggleModal(false));

    cancelModalBtn?.addEventListener('click', () => toggleModal(false));



    function updateModalCustomerType() {

        const selected = modalForm?.querySelector('input[name="customer_type"]:checked')?.value;

        if (selected === 'corporate') {

            modalTaxLabel.textContent = 'Vergi Kimlik Numarası';

            modalTaxOffice.classList.remove('hidden');

            modalCompanyTitle?.classList.remove('hidden');

        } else {

            modalTaxLabel.textContent = 'TC Kimlik Numarası';

            modalTaxOffice.classList.add('hidden');

            modalCompanyTitle?.classList.add('hidden');

        }

    }



    modalForm?.querySelectorAll('input[name="customer_type"]').forEach((input) => {

        input.addEventListener('change', updateModalCustomerType);

    });

    updateModalCustomerType();



    async function loadModalCities() {

        const response = await fetch('/data/turkey-cities.json');

        const data = await response.json();

        data.forEach((city) => {

            const option = document.createElement('option');

            option.value = city.name;

            option.textContent = city.name;

            modalCity.appendChild(option);

        });



        modalCity.addEventListener('change', (event) => {

            const selected = data.find((city) => city.name === event.target.value);

            modalDistrict.innerHTML = '<option value=\"\">Seçiniz</option>';

            (selected?.towns || []).forEach((town) => {

                const option = document.createElement('option');

                option.value = town.name;

                option.textContent = town.name;

                modalDistrict.appendChild(option);

            });

        });

    }



    loadModalCities().catch(() => {});



    modalForm?.addEventListener('submit', async (event) => {

        event.preventDefault();

        clearModalError();

        const formData = new FormData(modalForm);

        let response = null;

        let payload = null;

        let contentType = '';

        try {

            response = await fetch('{{ route('super-admin.customers.store') }}', {

                method: 'POST',

                headers: {

                    'X-CSRF-TOKEN': '{{ csrf_token() }}',

                    'X-Requested-With': 'XMLHttpRequest',

                    'Accept': 'application/json',

                },

                body: formData,

            });



            contentType = response.headers.get('content-type') ?? '';

            payload = contentType.includes('application/json')

                ? await response.json().catch(() => null)

                : null;

        } catch (error) {

            showModalError('Müşteri kaydedilemedi. Lütfen tekrar deneyin.');

            return;

        }



        if (!response.ok) {

            if (response.status === 422) {

                const errors = payload?.errors ? Object.values(payload.errors).flat() : [];

                showModalError(errors.length ? errors.join(' ') : 'Lütfen bilgileri kontrol edin.');

            } else if (payload?.name || payload?.email) {

                nameInput.value = payload.name ?? '';

                emailInput.value = payload.email ?? '';

                searchInput.value = payload.name ?? '';

                clearResults();

                hideEmpty();

                toggleModal(false);

                modalForm.reset();

            } else {

                showModalError('Müşteri kaydedilemedi. Lütfen tekrar deneyin.');

            }

            return;

        }



        const customer = payload   {

            name: formData.get('name'),

            email: formData.get('email'),

            billing_address: formData.get('billing_address'),

        };

        nameInput.value = customer.name ?? '';

        emailInput.value = customer.email ?? '';

        if (customer.user_id) {

            idInput.value = customer.user_id;

        }

        searchInput.value = customer.name ?? '';

        clearResults();

        hideEmpty();

        toggleModal(false);

        modalForm.reset();

    });

</script>

@endpush

















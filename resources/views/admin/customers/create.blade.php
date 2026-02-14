@extends('layouts.admin')



@section('header')

    Müşteri Ekle

@endsection



@section('content')

    <div class="panel-card p-6 max-w-4xl">

        <form method="POST" action="{{ route('portal.customers.store') }}" class="space-y-6">

            @csrf



            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="md:col-span-2">

                    <label class="block text-sm font-medium text-slate-700">Müşteri Adı</label>

                    <input name="name" type="text" class="mt-1 w-full" value="{{ old('name') }}" required>

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">E-posta</label>

                    <input name="email" type="email" class="mt-1 w-full" value="{{ old('email') }}" required>

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">Telefon</label>

                    <input name="phone" type="text" class="mt-1 w-full" value="{{ old('phone') }}">

                </div>

            </div>



            <div class="panel-card p-4">

                <h3 class="text-sm font-semibold text-slate-800">Adres Bilgileri</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                    <div>

                        <label class="block text-sm font-medium text-slate-700">İl</label>

                        <select id="city-select" name="city" class="mt-1 w-full" data-current="{{ old('city') }}">

                            <option value="">Seçiniz</option>

                        </select>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">İlçe</label>

                        <select id="district-select" name="district" class="mt-1 w-full" data-current="{{ old('district') }}">

                            <option value="">Seçiniz</option>

                        </select>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">Mahalle</label>

                        <input name="neighborhood" type="text" class="mt-1 w-full" value="{{ old('neighborhood') }}">

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">Sokak</label>

                        <input name="street" type="text" class="mt-1 w-full" value="{{ old('street') }}">

                    </div>

                    <div class="md:col-span-2">

                        <label class="block text-sm font-medium text-slate-700">Açık Adres</label>

                        <textarea name="billing_address" rows="3" class="mt-1 w-full">{{ old('billing_address') }}</textarea>

                    </div>

                </div>

            </div>



            <div class="panel-card p-4">

                <h3 class="text-sm font-semibold text-slate-800">Müşteri Türü</h3>

                <div class="flex flex-wrap gap-4 mt-4 text-sm">

                    <label class="inline-flex items-center gap-2">

                        <input type="radio" name="customer_type" value="corporate" class="rounded" @checked(old('customer_type', 'individual') === 'corporate')>

                        <span>Tüzel Kişi</span>

                    </label>

                    <label class="inline-flex items-center gap-2">

                        <input type="radio" name="customer_type" value="individual" class="rounded" @checked(old('customer_type', 'individual') === 'individual')>

                        <span>Gerçek Kişi</span>

                    </label>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                    <div id="company-title-field" class="md:col-span-2">

                        <label class="block text-sm font-medium text-slate-700">Firma Ünvanı</label>

                        <input name="company_title" type="text" class="mt-1 w-full" value="{{ old('company_title') }}">

                    </div>

                    <div>

                        <label id="tax-id-label" class="block text-sm font-medium text-slate-700">Vergi Kimlik Numarası</label>

                        <input name="tax_id" type="text" class="mt-1 w-full" value="{{ old('tax_id') }}">

                    </div>

                    <div id="tax-office-field">

                        <label class="block text-sm font-medium text-slate-700">Vergi Dairesi</label>

                        <input name="tax_office" type="text" class="mt-1 w-full" value="{{ old('tax_office') }}">

                    </div>

                </div>

            </div>



            <div class="flex items-center gap-3">

                <button type="submit">Kaydet</button>

                <a href="{{ route('portal.customers.index') }}" class="btn btn-outline-accent">Vazgeç</a>

            </div>

        </form>

    </div>

@endsection



@push('scripts')

<script>

    const citySelect = document.getElementById('city-select');

    const districtSelect = document.getElementById('district-select');

    const currentCity = citySelect?.dataset.current;

    const currentDistrict = districtSelect?.dataset.current;



    async function loadCities() {

        const response = await fetch('/data/turkey-cities.json');

        const data = await response.json();



        data.forEach((city) => {

            const option = document.createElement('option');

            option.value = city.name;

            option.textContent = city.name;

            if (currentCity && currentCity === city.name) {

                option.selected = true;

            }

            citySelect.appendChild(option);

        });



        if (currentCity) {

            const selected = data.find((city) => city.name === currentCity);

            if (selected) {

                populateDistricts(selected.towns || []);

            }

        }



        citySelect.addEventListener('change', (event) => {

            const selected = data.find((city) => city.name === event.target.value);

            populateDistricts(selected ? selected.towns || [] : []);

        });

    }



    function populateDistricts(towns) {

        districtSelect.innerHTML = '<option value=\"\">Seçiniz</option>';

        towns.forEach((town) => {

            const option = document.createElement('option');

            option.value = town.name;

            option.textContent = town.name;

            if (currentDistrict && currentDistrict === town.name) {

                option.selected = true;

            }

            districtSelect.appendChild(option);

        });

    }



    const typeInputs = document.querySelectorAll('input[name="customer_type"]');

    const taxOfficeField = document.getElementById('tax-office-field');

    const taxIdLabel = document.getElementById('tax-id-label');

    const companyTitleField = document.getElementById('company-title-field');



    function updateCustomerTypeUI() {

        const selected = document.querySelector('input[name="customer_type"]:checked')?.value;

        if (selected === 'corporate') {

            taxIdLabel.textContent = 'Vergi Kimlik Numarası';

            taxOfficeField.classList.remove('hidden');

            companyTitleField?.classList.remove('hidden');

        } else {

            taxIdLabel.textContent = 'TC Kimlik Numarası';

            taxOfficeField.classList.add('hidden');

            companyTitleField?.classList.add('hidden');

        }

    }



    typeInputs.forEach((input) => {

        input.addEventListener('change', updateCustomerTypeUI);

    });



    updateCustomerTypeUI();



    if (citySelect && districtSelect) {

        loadCities().catch(() => {});

    }

</script>

@endpush









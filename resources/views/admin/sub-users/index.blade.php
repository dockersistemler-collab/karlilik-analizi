@extends('layouts.admin')



@section('header')

    Alt Kullanıcılar

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">

            <div>

                <h3 class="text-sm font-semibold text-slate-800">Alt Kullanıcı Yönetimi</h3>

                <p class="text-xs text-slate-500 mt-1">Yetkileri modül bazında belirleyebilirsiniz.</p>

            </div>

            <button id="open-subuser-modal" type="button" class="btn btn-solid-accent">

                Alt Kullanıcı Ekle

            </button>

        </div>



        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr class="text-left">

                        <th>İsim</th>

                        <th>E-posta</th>

                        <th>Durum</th>

                        <th>Yetkiler</th>

                        <th class="text-right">İÅŸlem</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($subUsers as $subUser)

                        <tr class="border-t border-slate-100">

                            <td class="font-medium text-slate-900">{{ $subUser->name }}</td>

                            <td class="text-slate-600">{{ $subUser->email }}</td>

                            <td>

                                <span class="px-2 py-1 rounded text-xs {{ $subUser->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">

                                    {{ $subUser->is_active ? 'Aktif' : 'Pasif' }}

                                </span>

                            </td>

                            <td class="text-slate-500">

                                {{ $subUser->permissions->pluck('permission_key')->join(', ') ?: '-' }}

                            </td>

                            <td class="text-right whitespace-nowrap">

                                <a href="{{ route('portal.sub-users.edit', $subUser) }}" class="text-slate-600 hover:text-slate-900 mr-3">Düzenle</a>

                                <form method="POST" action="{{ route('portal.sub-users.destroy', $subUser) }}" class="inline">

                                    @csrf

                                    @method('DELETE')

                                    <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Alt kullanıcı silinsin mi?')">Sil</button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr class="border-t border-slate-100">

                            <td colspan="5" class="py-6 text-center text-slate-500">Henüz alt kullanıcı yok.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>



    <div id="subuser-modal" class="fixed inset-0 bg-slate-900/30 hidden items-center justify-center z-50">

        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-6">

            <div class="flex items-center justify-between mb-4">

                <h3 class="text-sm font-semibold text-slate-800">Alt Kullanıcı Ekle</h3>

                <button id="close-subuser-modal" type="button" class="text-slate-400 hover:text-slate-600">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>



            <form id="subuser-modal-form" class="space-y-4">

                @csrf

                <div id="subuser-modal-error" class="hidden rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div>

                        <label class="block text-sm font-medium text-slate-700">İsim</label>

                        <input type="text" name="name" class="mt-1 w-full" required>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">E-posta</label>

                        <input type="email" name="email" class="mt-1 w-full" required>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">Şifre</label>

                        <input type="password" name="password" class="mt-1 w-full" required>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">Şifre Tekrar</label>

                        <input type="password" name="password_confirmation" class="mt-1 w-full" required>

                    </div>

                    <div class="md:col-span-2 flex items-center gap-2">

                        <input type="checkbox" id="modal-is-active" name="is_active" value="1" class="rounded" checked>

                        <label for="modal-is-active" class="text-sm text-slate-700">Aktif</label>

                    </div>

                </div>



                <div>

                    <h3 class="text-sm font-semibold text-slate-800 mb-3">Yetkiler</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">

                        @foreach([

                            'dashboard' => 'Panel',

                            'products' => 'Ãœrünler',

                            'orders' => 'SipariÅŸler',

                            'customers' => 'Müşteriler',

                            'reports' => 'Raporlar (Tümü)',

                            'reports.orders' => 'Raporlar: SipariÅŸ ve Ciro',

                            'reports.top_products' => 'Raporlar: Ã‡ok Satan Ãœrünler',

                            'reports.sold_products' => 'Raporlar: Satılan Ãœrün Listesi',

                            'reports.category_sales' => 'Raporlar: Kategori Bazlı SatıÅŸ',

                            'reports.brand_sales' => 'Raporlar: Marka Bazlı SatıÅŸ',

                            'reports.vat' => 'Raporlar: KDV Raporu',

                            'reports.commission' => 'Raporlar: Komisyon Raporu',

                            'reports.stock_value' => 'Raporlar: Stoktaki Ãœrün Tutarları',

                            'integrations' => 'Entegrasyonlar',

                            'addons' => 'Ek Modüller',

                            'subscription' => 'Paketim',

                            'settings' => 'Ayarlar',

                            'help' => 'Yardım',

                            'tickets' => 'Ticketlar',

                            'invoices' => 'Faturalar',

                        ] as $key => $label)

                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2">

                                <input type="checkbox" name="permissions[]" value="{{ $key }}" class="rounded" {{ $key === 'dashboard' ? 'checked' : '' }}>

                                <span class="text-slate-700">{{ $label }}</span>

                            </label>

                        @endforeach

                    </div>

                </div>



                <div class="flex items-center gap-3">

                    <button type="submit">Kaydet</button>

                    <button id="cancel-subuser-modal" type="button" class="text-slate-500 hover:text-slate-700">Vazgeç</button>

                </div>

            </form>

        </div>

    </div>

@endsection



@push('scripts')

<script>

    const subuserOpenBtn = document.getElementById('open-subuser-modal');

    const subuserModal = document.getElementById('subuser-modal');

    const subuserCloseBtn = document.getElementById('close-subuser-modal');

    const subuserCancelBtn = document.getElementById('cancel-subuser-modal');

    const subuserForm = document.getElementById('subuser-modal-form');

    const subuserError = document.getElementById('subuser-modal-error');



        const permissionLabels = {

        dashboard: 'Panel',

        products: 'Ãœrünler',

        orders: 'SipariÅŸler',

        customers: 'Müşteriler',

        reports: 'Raporlar (Tümü)',

        'reports.orders': 'Raporlar: SipariÅŸ ve Ciro',

        'reports.top_products': 'Raporlar: Ã‡ok Satan Ãœrünler',

        'reports.sold_products': 'Raporlar: Satılan Ãœrün Listesi',

        'reports.category_sales': 'Raporlar: Kategori Bazlı SatıÅŸ',

        'reports.brand_sales': 'Raporlar: Marka Bazlı SatıÅŸ',

        'reports.vat': 'Raporlar: KDV Raporu',

        'reports.commission': 'Raporlar: Komisyon Raporu',

        'reports.stock_value': 'Raporlar: Stoktaki Ãœrün Tutarları',

        integrations: 'Entegrasyonlar',

        addons: 'Ek Modüller',

        subscription: 'Paketim',

        settings: 'Ayarlar',

        help: 'Yardım',

        tickets: 'Ticketlar',

        invoices: 'Faturalar',

    };



    function toggleSubUserModal(show) {

        if (!subuserModal) return;

        subuserModal.classList.toggle('hidden', !show);

        subuserModal.classList.toggle('flex', show);

    }



    function showSubUserError(message) {

        if (!subuserError) return;

        subuserError.textContent = message;

        subuserError.classList.remove('hidden');

    }



    function clearSubUserError() {

        if (!subuserError) return;

        subuserError.textContent = '';

        subuserError.classList.add('hidden');

    }



    subuserOpenBtn?.addEventListener('click', () => {

        clearSubUserError();

        toggleSubUserModal(true);

    });

    subuserCloseBtn?.addEventListener('click', () => toggleSubUserModal(false));

    subuserCancelBtn?.addEventListener('click', () => toggleSubUserModal(false));



    subuserForm?.addEventListener('submit', async (event) => {

        event.preventDefault();

        clearSubUserError();

        const formData = new FormData(subuserForm);

        const response = await fetch('{{ route('portal.sub-users.store') }}', {

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

                showSubUserError(errors.length ? errors.join(' ') : 'Lütfen bilgileri kontrol edin.');

            } else {

                showSubUserError('Alt kullanıcı kaydedilemedi. Lütfen tekrar deneyin.');

            }

            return;

        }



        const tableBody = document.querySelector('table tbody');

        if (tableBody && payload) {

            const permissionText = (payload.permissions || [])

                .map((key) => permissionLabels[key] || key)

                .join(', ') || '-';

            const statusBadge = payload.is_active

                ? '<span class="px-2 py-1 rounded text-xs bg-emerald-50 text-emerald-700">Aktif</span>'

                : '<span class="px-2 py-1 rounded text-xs bg-slate-100 text-slate-500">Pasif</span>';

            const row = document.createElement('tr');

            row.className = 'border-t border-slate-100';

            row.innerHTML = `

                <td class="font-medium text-slate-900">${payload.name}</td>

                <td class="text-slate-600">${payload.email}</td>

                <td>${statusBadge}</td>

                <td class="text-slate-500">${permissionText}</td>

                <td class="text-right whitespace-nowrap">

                    <a href="{{ url('/portal/sub-users') }}/${payload.id}/edit" class="text-slate-600 hover:text-slate-900 mr-3">Düzenle</a>

                    <form method="POST" action="{{ url('/portal/sub-users') }}/${payload.id}" class="inline">

                        <input type="hidden" name="_token" value="{{ csrf_token() }}">

                        <input type="hidden" name="_method" value="DELETE">

                        <button type="submit" class="text-red-500 hover:text-red-600" onclick="return confirm('Alt kullanıcı silinsin mi?')">Sil</button>

                    </form>

                </td>

            `;

            tableBody.prepend(row);

        }



        subuserForm.reset();

        const activeInput = document.getElementById('modal-is-active');

        if (activeInput) {

            activeInput.checked = true;

        }

        toggleSubUserModal(false);

    });

</script>

@endpush















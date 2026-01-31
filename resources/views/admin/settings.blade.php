@extends('layouts.admin')

@section('header')
    Genel Ayarlar
@endsection

@section('content')
    <div class="panel-card p-6">
        <div class="max-w-5xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-xl border border-slate-100 p-4">
                    <div class="font-semibold text-slate-800">Tüm Ayarlar</div>
                    <div class="mt-3">
                        <input id="settingsSearch" type="text" placeholder="Ara..." class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white">
                    </div>

                    <div class="mt-3 space-y-1" id="settingsNav">
                        <button type="button" data-settings-tab="company" class="settings-tab-btn w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">
                            Firma Ayarları
                        </button>
                        <button type="button" data-settings-tab="invoice" class="settings-tab-btn w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">
                            Fatura Ayarları
                        </button>
                        <button type="button" data-settings-tab="product_list" class="settings-tab-btn w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">
                            Ürün Listesi Ayarları
                        </button>
                        <button type="button" data-settings-tab="marketplaces" class="settings-tab-btn w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">
                            Pazaryeri Ayarları
                        </button>
                        <button type="button" data-settings-tab="shipping_label" class="settings-tab-btn w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">
                            Kargo Etiket Ayarları
                        </button>
                        <button type="button" data-settings-tab="invoice_description_fields" class="settings-tab-btn w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">
                            Fatura Açıklama Alanı Tanımları
                        </button>
                        <button type="button" data-settings-tab="notifications" class="settings-tab-btn w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">
                            Bildirim Ayarları
                        </button>
                        <button type="button" data-settings-tab="products" class="settings-tab-btn w-full text-left px-3 py-2 rounded-lg text-sm text-slate-700 hover:bg-slate-50">
                            Ürün Ayarları
                        </button>
                    </div>
                </div>

                <div class="lg:col-span-3 bg-white rounded-xl border border-slate-100 p-6">
                    <div data-settings-panel="company" class="space-y-5">
                        <div>
                            <div class="text-base font-semibold text-slate-900">Firma Ayarları</div>
                            <p class="mt-1 text-sm text-slate-500">Firma bilgilerini ve logonu buradan güncelleyebilirsin.</p>
                        </div>

                        <form class="space-y-4" method="POST" action="{{ route('admin.settings.update', ['tab' => 'company']) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Firma Logosu</label>
                                <div class="flex items-center gap-4">
                                    @if($user?->company_logo_path)
                                        <img src="{{ asset('storage/' . $user->company_logo_path) }}" alt="Firma Logosu" class="h-14 w-14 rounded-xl object-cover border border-slate-200">
                                    @else
                                        <div class="h-14 w-14 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-xs text-slate-400">
                                            Logo
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <input type="file" name="company_logo" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                                        <p class="text-xs text-slate-400 mt-1">Önerilen: 512x512 px, PNG/JPG</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Slogan</label>
                                <input type="text" name="company_slogan" value="{{ old('company_slogan', $user?->company_slogan) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Firma Adı <span class="text-rose-500">*</span></label>
                                <input type="text" name="company_name" value="{{ old('company_name', $user?->company_name) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Telefon <span class="text-rose-500">*</span></label>
                                <input type="text" name="company_phone" value="{{ old('company_phone', $user?->company_phone) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Firma Adresi</label>
                                <textarea rows="3" name="company_address" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">{{ old('company_address', $user?->company_address) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Firma Websitesi</label>
                                <input type="text" name="company_website" value="{{ old('company_website', $user?->company_website) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-semibold">
                                    Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <div data-settings-panel="invoice" class="hidden space-y-5">
                        <div>
                            <div class="text-base font-semibold text-slate-900">Fatura Ayarları</div>
                            <p class="mt-1 text-sm text-slate-500">Fatura bilgilerini ve fatura numarası takibi ayarını buradan yönetebilirsin.</p>
                        </div>

                        <form class="space-y-4" method="POST" action="{{ route('admin.settings.update', ['tab' => 'invoice']) }}">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Fatura Ünvanı</label>
                                <input type="text" name="billing_name" value="{{ old('billing_name', $user?->billing_name) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Fatura E-postası</label>
                                <input type="email" name="billing_email" value="{{ old('billing_email', $user?->billing_email) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Fatura Adresi</label>
                                <textarea rows="3" name="billing_address" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">{{ old('billing_address', $user?->billing_address) }}</textarea>
                            </div>

                            <div class="flex items-center gap-3">
                                <input type="hidden" name="invoice_number_tracking" value="0">
                                <input type="checkbox" name="invoice_number_tracking" value="1" class="h-4 w-4 text-blue-600 border-slate-300 rounded" @checked(old('invoice_number_tracking', $user?->invoice_number_tracking))>
                                <label class="text-sm text-slate-700">Fatura No Takibi Yapılsın mı?</label>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-semibold">
                                    Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <div data-settings-panel="notifications" class="hidden space-y-5">
                        <div>
                            <div class="text-base font-semibold text-slate-900">Bildirim Ayarları</div>
                            <p class="mt-1 text-sm text-slate-500">Bilgilendirme e-posta adresini buradan ayarlayabilirsin.</p>
                        </div>

                        <form class="space-y-4" method="POST" action="{{ route('admin.settings.update', ['tab' => 'notifications']) }}">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Bilgilendirme E-posta Adresi</label>
                                <input type="email" name="notification_email" value="{{ old('notification_email', $user?->notification_email) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                                <p class="text-xs text-slate-400 mt-1">Kullanıcı giriş e-postası: <span class="font-medium text-slate-600">{{ $user?->email }}</span></p>
                            </div>

                            <div class="pt-2">
                                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-semibold">
                                    Kaydet
                                </button>
                            </div>
                        </form>
                    </div>

                    <div data-settings-panel="marketplaces" class="hidden space-y-4">
                        <div>
                            <div class="text-base font-semibold text-slate-900">Pazaryeri Ayarları</div>
                            <p class="mt-1 text-sm text-slate-500">Pazaryeri bağlantılarını “Pazaryeri Bağlantıları” ekranından yönetebilirsin.</p>
                        </div>
                        <div>
                            <a href="{{ route('admin.integrations.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                                Pazaryeri Bağlantılarına Git
                            </a>
                        </div>
                    </div>

                    <div data-settings-panel="shipping_label" class="hidden space-y-4">
                        <div>
                            <div class="text-base font-semibold text-slate-900">Kargo Etiket Ayarları</div>
                            <p class="mt-1 text-sm text-slate-500">Kargo etiketi süreçleri pazaryeri bağlantılarına göre değişebilir. Şimdilik bu bölüm pazaryeri ayarları üzerinden yönetiliyor.</p>
                        </div>
                        <div>
                            <a href="{{ route('admin.integrations.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                                Pazaryeri Bağlantılarına Git
                            </a>
                        </div>
                    </div>

                    <div data-settings-panel="product_list" class="hidden space-y-4">
                        <div>
                            <div class="text-base font-semibold text-slate-900">Ürün Listesi Ayarları</div>
                            <p class="mt-1 text-sm text-slate-500">Bu bölüm henüz eklenmedi. İstersen ürün listesi ekranındaki filtreleri/varsayılanları buraya taşıyabiliriz.</p>
                        </div>
                        <div>
                            <a href="{{ route('admin.products.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                                Ürün Listesine Git
                            </a>
                        </div>
                    </div>

                    <div data-settings-panel="invoice_description_fields" class="hidden space-y-4">
                        <div>
                            <div class="text-base font-semibold text-slate-900">Fatura Açıklama Alanı Tanımları</div>
                            <p class="mt-1 text-sm text-slate-500">Bu bölüm henüz eklenmedi. Hangi alanları (ör. “Kargo Notu”, “Müşteri Notu”, vb.) tanımlamak istediğini söylersen buraya ekleyebilirim.</p>
                        </div>
                    </div>

                    <div data-settings-panel="products" class="hidden space-y-4">
                        <div>
                            <div class="text-base font-semibold text-slate-900">Ürün Ayarları</div>
                            <p class="mt-1 text-sm text-slate-500">Bu bölüm henüz eklenmedi. Ürün açıklaması, varsayılan KDV, desi hesaplama vb. ayarları buraya toplayabiliriz.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const initialTab = @json($activeTab ?? 'company');

            const navButtons = Array.from(document.querySelectorAll('[data-settings-tab]'));
            const panels = Array.from(document.querySelectorAll('[data-settings-panel]'));

            function setActive(tab, updateUrl) {
                if (!panels.some((p) => p.dataset.settingsPanel === tab)) {
                    tab = 'company';
                }

                navButtons.forEach((btn) => {
                    const isActive = btn.dataset.settingsTab === tab;
                    btn.classList.toggle('bg-slate-900', isActive);
                    btn.classList.toggle('text-white', isActive);
                    btn.classList.toggle('hover:bg-slate-900', isActive);
                });

                panels.forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.settingsPanel !== tab);
                });

                if (updateUrl) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tab);
                    history.replaceState(null, '', url);
                }
            }

            navButtons.forEach((btn) => {
                btn.addEventListener('click', () => setActive(btn.dataset.settingsTab, true));
            });

            const url = new URL(window.location.href);
            const tabFromUrl = url.searchParams.get('tab') || (window.location.hash ? window.location.hash.substring(1) : null);
            setActive(tabFromUrl || initialTab, false);

            const searchInput = document.getElementById('settingsSearch');
            if (searchInput) {
                searchInput.addEventListener('input', () => {
                    const q = (searchInput.value || '').toLowerCase().trim();
                    navButtons.forEach((btn) => {
                        const text = (btn.textContent || '').toLowerCase();
                        btn.style.display = q === '' || text.includes(q) ? '' : 'none';
                    });
                });
            }
        })();
    </script>
@endsection

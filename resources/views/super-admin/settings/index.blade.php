@extends('layouts.super-admin')



@section('header')

    Sistem Ayarları

@endsection



@section('content')

    <div class="panel-card p-6 mb-6">

        <h3 class="text-sm font-semibold text-slate-800 mb-2">Ayar Kategorileri</h3>

        <p class="text-sm text-slate-600 mb-4">

            Ayarları aşağıdaki kategorilerden yönetebilirsiniz.

        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="client">

                <i class="fa-solid fa-user-gear"></i>

                Müşteri Ayarları

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="exports">

                <i class="fa-solid fa-file-export"></i>

                Rapor Dışa Aktarım

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="mail">

                <i class="fa-solid fa-envelope"></i>

                Mail & Bildirim Ayarları

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="incident-sla">

                <i class="fa-solid fa-triangle-exclamation"></i>

                Incident & SLA Ayarları

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="health">

                <i class="fa-solid fa-heart-pulse"></i>

                Integration Health Ayarları

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="billing">

                <i class="fa-solid fa-credit-card"></i>

                Planlar & Fiyatlandırma

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="features">

                <i class="fa-solid fa-toggle-on"></i>

                Modul / Feature Yonetimi

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="vat">

                <i class="fa-solid fa-palette"></i>

                KDV Kart Renkleri

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="category-mapping">

                <i class="fa-solid fa-sitemap"></i>

                Kategori Eşitleme

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="quick">

                <i class="fa-solid fa-bolt"></i>

                Hızlı Menü

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="theme">

                <i class="fa-solid fa-paintbrush"></i>

                Panel Görünümü

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="referral">

                <i class="fa-solid fa-user-plus"></i>

                Tavsiye Programı

            </button>

            <button type="button" class="tab-button btn btn-outline justify-start" data-tab-button="ne-kazanirim">

                <i class="fa-solid fa-calculator"></i>

                Ne Kazanırım

            </button>

        </div>

    </div>



    <section class="tab-panel" data-tab-panel="client">

        <div class="panel-card p-6 max-w-4xl">

            <h3 class="text-sm font-semibold text-slate-800 mb-2">Müşteri Ayarları</h3>

            <p class="text-sm text-slate-600 mb-4">

                Seçtiğiniz müşterinin firma ve fatura ayarlarını buradan güncelleyebilirsiniz.

            </p>



            <form method="GET" action="{{ route('super-admin.settings.index') }}" class="mb-6">

                <label class="block text-sm font-medium text-slate-700 mb-1">Müşteri Seç</label>

                <select name="user_id" class="w-full max-w-lg" onchange="this.form.submit()">

                    @foreach($users as $user)

                        <option value="{{ $user->id }}" @selected($selectedUser && $selectedUser->id === $user->id)>

                            {{ $user->company_name ?: $user->name }} ({{ $user->email }})

                        </option>

                    @endforeach

                </select>

            </form>



            @if($selectedUser)

                <form class="space-y-4" method="POST" action="{{ route('super-admin.settings.client.update', $selectedUser) }}" enctype="multipart/form-data">

                    @csrf

                    @method('PUT')

                    <div>

                        <label class="block text-sm font-medium text-slate-700 mb-1">Firma Logosu</label>

                        <div class="flex items-center gap-4">

                            @if($selectedUser->company_logo_path)

                                <img src="{{ asset('storage/' . $selectedUser->company_logo_path) }}" alt="Firma Logosu" class="h-14 w-14 rounded-xl object-cover border border-slate-200">

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

                        <input type="text" name="company_slogan" value="{{ old('company_slogan', $selectedUser->company_slogan) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700 mb-1">Firma Adı</label>

                        <input type="text" name="company_name" value="{{ old('company_name', $selectedUser->company_name) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700 mb-1">Telefon</label>

                        <input type="text" name="company_phone" value="{{ old('company_phone', $selectedUser->company_phone) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700 mb-1">Bilgilendirme E-posta Adresi</label>

                        <input type="email" name="notification_email" value="{{ old('notification_email', $selectedUser->notification_email) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700 mb-1">Kullanıcı Giriş E-posta Adresi</label>

                        <input type="email" value="{{ $selectedUser->email }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-slate-50" disabled>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700 mb-1">Firma Adresi</label>

                        <textarea rows="3" name="company_address" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">{{ old('company_address', $selectedUser->company_address) }}</textarea>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700 mb-1">Firma Websitesi</label>

                        <input type="text" name="company_website" value="{{ old('company_website', $selectedUser->company_website) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">

                    </div>

                    <div class="flex items-center gap-3">

                        <input type="checkbox" name="invoice_number_tracking" value="1" class="h-4 w-4 text-blue-600 border-slate-300 rounded" @checked(old('invoice_number_tracking', $selectedUser->invoice_number_tracking))>

                        <label class="text-sm text-slate-700">Fatura No Takibi Yapılsın mı?</label>

                    </div>

                    <div class="pt-2">

                        <button type="submit" class="btn btn-solid-accent">

                            Ayarları Kaydet

                        </button>

                    </div>

                </form>

            @else

                <p class="text-sm text-slate-500">Güncellenecek müşteri bulunamadı.</p>

            @endif

        </div>

    </section>



    <section class="tab-panel hidden" data-tab-panel="exports">

        <div class="panel-card p-6 max-w-3xl">

            <h3 class="text-sm font-semibold text-slate-800 mb-2">Rapor Dışa Aktarım</h3>

            <p class="text-sm text-slate-600 mb-4">Tüm rapor sayfalarındaki dışa aktarma butonlarını kontrol eder.</p>



            <form method="POST" action="{{ route('super-admin.settings.report-exports') }}" class="flex items-center justify-between">

                @csrf

                <div class="flex items-center gap-3">

                    <input type="checkbox" name="reports_exports_enabled" value="1" class="h-4 w-4 text-blue-600 border-slate-300 rounded" @checked($reportExportsEnabled)>

                    <label class="text-sm text-slate-700">Rapor dışa aktarma açık</label>

                </div>

                <button type="submit" class="btn btn-solid-accent">Kaydet</button>

            </form>

        </div>

    </section>



    <section class="tab-panel hidden" data-tab-panel="mail">

        @include('super-admin.settings.partials.mail')

    </section>



    <section class="tab-panel hidden" data-tab-panel="incident-sla">

        @include('super-admin.settings.partials.incident_sla')

    </section>



    <section class="tab-panel hidden" data-tab-panel="health">

        @include('super-admin.settings.partials.integration_health')

    </section>



    <section class="tab-panel hidden" data-tab-panel="billing">

        @include('super-admin.settings.partials.billing_plans')

    </section>



    <section class="tab-panel hidden" data-tab-panel="features">

        @include('super-admin.settings.partials.features')

    </section>



    <section class="tab-panel hidden" data-tab-panel="vat">

        <div class="panel-card p-6 max-w-4xl">

            <h3 class="text-sm font-semibold text-slate-800 mb-2">KDV Raporu Kart Renkleri</h3>

            <p class="text-sm text-slate-600 mb-4">Pazaryeri bazlı KDV kartlarının kenar rengini buradan belirleyebilirsiniz.</p>



            <form method="POST" action="{{ route('super-admin.settings.vat-colors') }}" class="space-y-3">

                @csrf

                @foreach($marketplaces as $marketplace)

                    <div class="flex items-center justify-between gap-4 border border-slate-200 rounded-lg px-4 py-3">

                        <div>

                            <p class="text-sm font-medium text-slate-800">{{ $marketplace->name }}</p>

                            <p class="text-xs text-slate-500">Varsayılan: #ff4439</p>

                        </div>

                        <input

                            type="color"

                            name="vat_colors[{{ $marketplace->id }}]"

                            value="{{ $vatColors[$marketplace->id] ?? '#ff4439' }}"

                            class="h-9 w-16 rounded-md border border-slate-200 bg-white p-0"

                        >

                    </div>

                @endforeach

                <div class="pt-2">

                    <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                </div>

            </form>

        </div>

    </section>



    <section class="tab-panel hidden" data-tab-panel="category-mapping">

        <div class="panel-card p-6 max-w-3xl">

            <h3 class="text-sm font-semibold text-slate-800 mb-2">Kategori Eşitleme Ayarları</h3>

            <p class="text-sm text-slate-600 mb-4">

                Kategori cache, eşitleme ekranı ve ürün ekranındaki eşitleme paneli davranışlarını buradan yönetebilirsiniz.

            </p>



            <form method="POST" action="{{ route('super-admin.settings.category-mapping') }}" class="space-y-4">

                @csrf



                <label class="flex items-center gap-2 text-sm text-slate-700">

                    <input type="checkbox" name="category_mapping_enabled" value="1" class="rounded" @checked($categoryMappingEnabled)>

                    Kategori eşitleme sistemi aktif

                </label>



                <label class="flex items-center gap-2 text-sm text-slate-700">

                    <input type="checkbox" name="category_mapping_auto_sync_enabled" value="1" class="rounded" @checked($categoryMappingAutoSyncEnabled)>

                    Pazaryeri bağlanınca otomatik kategori senkronu

                </label>



                <label class="flex items-center gap-2 text-sm text-slate-700">

                    <input type="checkbox" name="category_mapping_inline_enabled" value="1" class="rounded" @checked($categoryMappingInlineEnabled)>

                    Ürün ekle/düzenle ekranında inline “Kategori Eşitle” paneli göster

                </label>



                <div class="border-t border-slate-200 pt-4">

                    <div class="text-sm font-semibold text-slate-800 mb-2">İçe Aktarım Varsayılanları</div>

                    <label class="flex items-center gap-2 text-sm text-slate-700">

                        <input type="checkbox" name="category_import_only_leaf_default" value="1" class="rounded" @checked($categoryImportOnlyLeafDefault)>

                        Varsayılan: sadece en alt (leaf) kategoriler

                    </label>

                    <label class="flex items-center gap-2 text-sm text-slate-700 mt-2">

                        <input type="checkbox" name="category_import_create_mappings_default" value="1" class="rounded" @checked($categoryImportCreateMappingsDefault)>

                        Varsayılan: içe aktarırken otomatik eşleme oluştur

                    </label>

                </div>



                <div class="pt-2">

                    <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                </div>

            </form>

        </div>

    </section>



    <section class="tab-panel hidden" data-tab-panel="quick">

        <div class="panel-card p-6 max-w-4xl">

            <h3 class="text-sm font-semibold text-slate-800 mb-2">Hızlı Menü</h3>

            <p class="text-sm text-slate-600 mb-4">Admin panelindeki hızlı işlem menüsünde gösterilecek kısayolları seçin.</p>



            <form method="POST" action="{{ route('super-admin.settings.quick-actions') }}" class="space-y-3">

                @csrf

                <div id="quick-actions-list" class="space-y-3">

                    @foreach($quickActionsConfig as $index => $action)

                        <div class="flex flex-wrap items-center gap-3 border border-slate-200 rounded-lg px-4 py-3" draggable="true" data-quick-item>

                            <span class="text-slate-400 cursor-move">

                                <i class="fa-solid fa-grip-lines"></i>

                            </span>

                            <input type="hidden" name="actions[{{ $index }}][key]" value="{{ $action['key'] }}">

                            <input type="hidden" name="actions[{{ $index }}][order]" value="{{ $index }}" data-quick-order>

                            <label class="flex items-center gap-2">

                                <input type="checkbox"

                                       name="actions[{{ $index }}][enabled]"

                                       value="1"

                                       class="h-4 w-4 text-blue-600 border-slate-300 rounded"

                                       @checked($action['enabled'])>

                                <span class="text-sm font-semibold text-slate-700">{{ $action['label'] }}</span>

                            </label>

                            <div class="flex items-center gap-2">

                                <span class="text-xs text-slate-500">İkon</span>

                                <select name="actions[{{ $index }}][icon]" class="text-sm border border-slate-200 rounded-md px-2 py-1 bg-white">

                                    @foreach($quickActionIcons as $icon)

                                        <option value="{{ $icon }}" @selected($action['icon'] === $icon)>{{ $icon }}</option>

                                    @endforeach

                                </select>

                            </div>

                            <div class="flex items-center gap-2">

                                <span class="text-xs text-slate-500">Renk</span>

                                <input type="color" name="actions[{{ $index }}][color]" value="{{ $action['color'] }}" class="h-8 w-12 rounded-md border border-slate-200 bg-white p-0">

                            </div>

                            <div class="flex flex-wrap items-center gap-2">

                                <span class="text-xs text-slate-500">Roller</span>

                                @foreach($quickActionRoles as $roleKey => $roleLabel)

                                    <label class="flex items-center gap-1 text-xs text-slate-600">

                                        <input type="checkbox"

                                               name="actions[{{ $index }}][roles][]"

                                               value="{{ $roleKey }}"

                                               class="h-3 w-3 text-blue-600 border-slate-300 rounded"

                                               @checked(in_array($roleKey, $action['roles'], true))>

                                        {{ $roleLabel }}

                                    </label>

                                @endforeach

                            </div>

                        </div>

                    @endforeach

                </div>

                <div class="pt-2">

                    <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                </div>

            </form>

        </div>

    </section>



    <section class="tab-panel hidden" data-tab-panel="theme">

        <div class="panel-card p-6 max-w-3xl">

            <h3 class="text-sm font-semibold text-slate-800 mb-2">Panel Görünümü</h3>

            <p class="text-sm text-slate-600 mb-4">

                Bu ayarlar hem Admin paneline hem de Süper Admin paneline uygulanır.

            </p>



            <form method="POST" action="{{ route('super-admin.settings.theme') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">

                @csrf



                <div class="md:col-span-2">

                    <label class="block text-sm font-medium text-slate-700">Font</label>

                    <select name="panel_theme_font" class="mt-1 w-full">

                        @foreach($panelThemeFontOptions as $key => $label)

                            <option value="{{ $key }}" @selected(old('panel_theme_font', $panelThemeFont) === $key)>{{ $label }}</option>

                        @endforeach

                    </select>

                    <p class="text-xs text-slate-500 mt-1">Varsayılan: Poppins</p>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700">Aksan Rengi</label>

                    <div class="flex items-center gap-3 mt-1">

                        <input id="panel-theme-accent-picker" type="color" value="{{ old('panel_theme_accent', $panelThemeAccent) }}" class="h-10 w-14 rounded-md border border-slate-200 bg-white p-1">

                        <input id="panel-theme-accent-input" type="text" name="panel_theme_accent" value="{{ old('panel_theme_accent', $panelThemeAccent) }}" class="flex-1" placeholder="#ff4439">

                    </div>

                    <p class="text-xs text-slate-500 mt-1">HEX formatı: #ff4439</p>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700">Köşe Yuvarlaklığı (px)</label>

                    <input type="number" min="0" max="16" name="panel_theme_radius" value="{{ old('panel_theme_radius', $panelThemeRadius) }}" class="mt-1 w-full">

                    <p class="text-xs text-slate-500 mt-1">0–16 arası önerilir.</p>

                </div>



                <div class="md:col-span-2 flex items-center gap-3 pt-2">

                    <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                </div>

            </form>

        </div>

    </section>



    <section class="tab-panel hidden" data-tab-panel="referral">

        <div class="panel-card p-6 max-w-3xl">

            <h3 class="text-sm font-semibold text-slate-800 mb-2">Tavsiye Programı</h3>

            <p class="text-sm text-slate-600 mb-4">

                Tavsiye programını etkinleştirip dönemsel olarak ödülleri ve limitleri yönetebilirsiniz.

            </p>



            <form method="POST" action="{{ route('super-admin.settings.referral') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">

                @csrf



                <div class="md:col-span-2">

                    <label class="block text-sm font-medium text-slate-700">Program Adı</label>

                    <input type="text" name="name" value="{{ old('name', $program->name ?? 'Webreen Tavsiye Programı') }}" class="mt-1 w-full" required>

                </div>



                <div class="md:col-span-2">

                    <label class="block text-sm font-medium text-slate-700">Açıklama</label>

                    <textarea name="description" rows="3" class="mt-1 w-full">{{ old('description', $program->description ?? '') }}</textarea>

                </div>



                <div class="md:col-span-2 flex items-center gap-2">

                    <input type="checkbox" name="is_active" value="1" class="rounded" @checked(old('is_active', $program->is_active ?? true))>

                    <span class="text-sm text-slate-600">Program aktif</span>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700">Tavsiye Eden Ödülü</label>

                    <div class="flex gap-2 mt-1">

                        <select name="referrer_reward_type" class="flex-1">

                            <option value="duration" @selected(old('referrer_reward_type', $program->referrer_reward_type ?? 'duration') === 'duration')>Ay Kullanım</option>

                            <option value="percent" @selected(old('referrer_reward_type', $program->referrer_reward_type ?? 'duration') === 'percent')>% İndirim</option>

                        </select>

                        <input type="number" step="0.01" name="referrer_reward_value" value="{{ old('referrer_reward_value', $program->referrer_reward_value ?? 1) }}" class="w-32">

                    </div>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700">Tavsiye Alan Ödülü</label>

                    <div class="flex gap-2 mt-1">

                        <select name="referred_reward_type" class="flex-1">

                            <option value="duration" @selected(old('referred_reward_type', $program->referred_reward_type ?? 'duration') === 'duration')>Ay Kullanım</option>

                            <option value="percent" @selected(old('referred_reward_type', $program->referred_reward_type ?? 'duration') === 'percent')>% İndirim</option>

                        </select>

                        <input type="number" step="0.01" name="referred_reward_value" value="{{ old('referred_reward_value', $program->referred_reward_value ?? 1) }}" class="w-32">

                    </div>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700">Yıllık Kullanım Limiti</label>

                    <input type="number" name="max_uses_per_referrer_per_year" value="{{ old('max_uses_per_referrer_per_year', $program->max_uses_per_referrer_per_year ?? 0) }}" class="mt-1 w-full">

                    <p class="text-xs text-slate-500 mt-1">0 = limitsiz</p>

                </div>



                <div>

                    <label class="block text-sm font-medium text-slate-700">Program Dönemi</label>

                    <div class="flex gap-2 mt-1">

                        <input type="date" name="starts_at" value="{{ old('starts_at', optional($program?->starts_at)->format('Y-m-d')) }}" class="flex-1">

                        <input type="date" name="ends_at" value="{{ old('ends_at', optional($program?->ends_at)->format('Y-m-d')) }}" class="flex-1">

                    </div>

                </div>



                <div class="md:col-span-2 flex items-center gap-3 mt-2">

                    <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                </div>

            </form>

        </div>

    </section>

    <section class="tab-panel hidden" data-tab-panel="ne-kazanirim">

        @include('super-admin.settings.partials.ne_kazanirim')

    </section>

@endsection



@push('scripts')

    <script>

        const tabButtons = document.querySelectorAll('[data-tab-button]');

        const tabPanels = document.querySelectorAll('[data-tab-panel]');

        const tabStorageKey = 'superAdminSettingsTab';



        const params = new URLSearchParams(window.location.search);

        const paramTab = params.get('tab');

        if (paramTab) {

            localStorage.setItem(tabStorageKey, paramTab);

        }



        function setActiveTab(tab) {

            tabButtons.forEach((button) => {

                const isActive = button.dataset.tabButton === tab;

                button.classList.toggle('btn-solid-accent', isActive);

                button.classList.toggle('btn-outline', !isActive);

            });

            tabPanels.forEach((panel) => {

                panel.classList.toggle('hidden', panel.dataset.tabPanel !== tab);

            });

        }



        const storedTab = paramTab || localStorage.getItem(tabStorageKey) || 'client';

        setActiveTab(storedTab);



        tabButtons.forEach((button) => {

            button.addEventListener('click', () => {

                const tab = button.dataset.tabButton;

                localStorage.setItem(tabStorageKey, tab);

                setActiveTab(tab);

            });

        });

    </script>

    <script>

        const quickList = document.getElementById('quick-actions-list');

        if (quickList) {

            let dragged = null;



            quickList.addEventListener('dragstart', (event) => {

                dragged = event.target.closest('[data-quick-item]');

                if (dragged) {

                    event.dataTransfer.effectAllowed = 'move';

                }

            });



            quickList.addEventListener('dragover', (event) => {

                event.preventDefault();

                const target = event.target.closest('[data-quick-item]');

                if (!target || target === dragged) return;

                const rect = target.getBoundingClientRect();

                const next = (event.clientY - rect.top) > rect.height / 2;

                quickList.insertBefore(dragged, next ? target.nextSibling : target);

            });



            quickList.addEventListener('dragend', () => {

                dragged = null;

                const items = quickList.querySelectorAll('[data-quick-item]');

                items.forEach((item, index) => {

                    const orderInput = item.querySelector('[data-quick-order]');

                    if (orderInput) {

                        orderInput.value = index;

                    }

                });

            });

        }

    </script>

    <script>

        const accentPicker = document.getElementById('panel-theme-accent-picker');

        const accentInput = document.getElementById('panel-theme-accent-input');



        function syncPickerToInput() {

            if (!accentPicker || !accentInput) return;

            accentInput.value = accentPicker.value;

        }



        function syncInputToPicker() {

            if (!accentPicker || !accentInput) return;

            const value = (accentInput.value || '').trim();

            if (/^#[0-9a-fA-F]{6}$/.test(value)) {

                accentPicker.value = value;

            }

        }



        accentPicker?.addEventListener('input', syncPickerToInput);

        accentInput?.addEventListener('input', syncInputToPicker);

        syncInputToPicker();

    </script>

@endpush








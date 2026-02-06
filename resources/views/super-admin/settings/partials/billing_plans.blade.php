<div class="panel-card p-6 max-w-5xl">

    <h3 class="text-sm font-semibold text-slate-800 mb-2">Planlar &amp; Fiyatlandirma</h3>

    <p class="text-sm text-slate-600 mb-6">

        Plan katalogu sadece fiyatlama ve gosterim icindir. Yetkilendirme icin Feature matrisi kullanilir.

    </p>



    <form method="POST" action="{{ route('super-admin.settings.billing.update') }}" class="space-y-6">

        @csrf

        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">

            <h4 class="text-sm font-semibold text-slate-800 mb-3">Iyzico Ayarlari</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="flex items-center gap-2">

                    <input type="checkbox" name="iyzico_enabled" value="1" class="h-4 w-4" @checked($iyzicoSettings['enabled'] ?? false)>

                    <span class="text-xs text-slate-600">Iyzico aktif</span>

                </div>

                <div class="flex items-center gap-2">

                    <input type="checkbox" name="iyzico_sandbox" value="1" class="h-4 w-4" @checked($iyzicoSettings['sandbox'] ?? true)>

                    <span class="text-xs text-slate-600">Sandbox modu</span>

                </div>

                <div>

                    <label class="text-xs text-slate-500">API Key</label>

                    <input type="password" name="iyzico_api_key" class="w-full" placeholder="••••••••">

                </div>

                <div>

                    <label class="text-xs text-slate-500">Secret Key</label>

                    <input type="password" name="iyzico_secret_key" class="w-full" placeholder="••••••••">

                </div>

                <div>

                    <label class="text-xs text-slate-500">Webhook Secret (opsiyonel)</label>

                    <input type="password" name="iyzico_webhook_secret" class="w-full" placeholder="••••••••">

                </div>

            </div>

            <p class="text-xs text-slate-500 mt-3">CheckoutForm callbackUrl HTTPS olmali.</p>

            <p class="text-xs text-slate-500">Webhook signature icin hesabinizda ozellik acilmalidir.</p>

        </div>



        <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">

            <h4 class="text-sm font-semibold text-slate-800 mb-3">Dunning / Grace</h4>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div>

                    <label class="text-xs text-slate-500">Grace Gun</label>

                    <input type="number" name="dunning_grace_days" min="0" max="365" value="{{ $dunningSettings['grace_days'] ?? 3 }}" class="w-full">

                </div>

                <div>

                    <label class="text-xs text-slate-500">Hatirlatma Gun 1</label>

                    <input type="number" name="dunning_reminder_day_1" min="0" max="30" value="{{ $dunningSettings['reminder_day_1'] ?? 0 }}" class="w-full">

                </div>

                <div>

                    <label class="text-xs text-slate-500">Hatirlatma Gun 2</label>

                    <input type="number" name="dunning_reminder_day_2" min="0" max="30" value="{{ $dunningSettings['reminder_day_2'] ?? 2 }}" class="w-full">

                </div>

                <label class="flex items-center gap-2 text-xs text-slate-600">

                    <input type="checkbox" name="dunning_send_reminders" value="1" class="h-4 w-4" @checked($dunningSettings['send_reminders'] ?? true)>

                    Hatirlatma gonder

                </label>

                <label class="flex items-center gap-2 text-xs text-slate-600">

                    <input type="checkbox" name="dunning_auto_downgrade" value="1" class="h-4 w-4" @checked($dunningSettings['auto_downgrade'] ?? true)>

                    Grace bitince otomatik dusur

                </label>

            </div>

        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            @foreach($billingPlansCatalog as $code => $plan)

                <div class="border border-slate-200 rounded-lg p-4">

                    <h4 class="text-sm font-semibold text-slate-800 mb-3">{{ strtoupper($code) }}</h4>

                    <div class="space-y-3">

                        <div>

                            <label class="text-xs text-slate-500">Plan Adi</label>

                            <input type="text" name="plans[{{ $code }}][name]" value="{{ $plan['name'] }}" class="w-full">

                        </div>

                        <div>

                            <label class="text-xs text-slate-500">Aylik Fiyat (TL)</label>

                            <input type="number" name="plans[{{ $code }}][price_monthly]" value="{{ $plan['price_monthly'] }}" min="0" max="1000000" class="w-full">

                        </div>

                        <div class="flex items-center gap-2">

                            <input type="checkbox" name="plans[{{ $code }}][recommended]" value="1" class="h-4 w-4" @checked($plan['recommended'])>

                            <span class="text-xs text-slate-600">Onerilen</span>

                        </div>

                        <div class="flex items-center gap-2">

                            <input type="checkbox" name="plans[{{ $code }}][contact_sales]" value="1" class="h-4 w-4" @checked($plan['contact_sales'])>

                            <span class="text-xs text-slate-600">Satis ile gorus</span>

                        </div>

                        <div>

                            <label class="text-xs text-slate-500">Ozellikler</label>

                            <div class="mt-2 space-y-1">

                                @foreach($featureKeys as $featureKey)

                                    <label class="flex items-center gap-2 text-xs text-slate-600">

                                        <input

                                            type="checkbox"

                                            name="plans[{{ $code }}][features][]"

                                            value="{{ $featureKey }}"

                                            class="h-4 w-4"

                                            @checked(in_array($featureKey, $plan['features'] ?? [], true))

                                        >

                                        {{ $featureLabels[$featureKey] ?? $featureKey }}

                                    </label>

                                @endforeach

                            </div>

                        </div>

                        <div class="pt-2">

                            <label class="text-xs text-slate-500">Iyzico Product Ref</label>

                            <input id="iyzico-product-{{ $code }}" type="text" name="plans[{{ $code }}][iyzico][productReferenceCode]" value="{{ $plan['iyzico']['productReferenceCode'] ?? '' }}" class="w-full">

                            <div class="mt-2 flex items-center gap-2">

                                <button

                                    type="button"

                                    class="btn btn-outline-accent text-xs px-2 py-1"

                                    data-iyzico-action="product"

                                    data-plan-code="{{ $code }}"

                                    data-url="{{ route('super-admin.system-settings.billing.iyzico.product-create') }}"

                                    data-product-input="iyzico-product-{{ $code }}"

                                    data-pricing-input="iyzico-pricing-{{ $code }}"

                                    @if(!empty($plan['iyzico']['productReferenceCode'] ?? '')) style="display:none" @endif

                                >

                                    Urunu Olustur

                                </button>

                                <span class="text-xs text-slate-500 hidden" data-iyzico-spinner="product">Isleniyor...</span>

                            </div>

                        </div>

                        <div>

                            <label class="text-xs text-slate-500">Iyzico Pricing Plan Ref</label>

                            <input id="iyzico-pricing-{{ $code }}" type="text" name="plans[{{ $code }}][iyzico][pricingPlanReferenceCode]" value="{{ $plan['iyzico']['pricingPlanReferenceCode'] ?? '' }}" class="w-full">

                            <div class="mt-2 flex items-center gap-2">

                                <button

                                    type="button"

                                    class="btn btn-outline-accent text-xs px-2 py-1"

                                    data-iyzico-action="pricing-plan"

                                    data-plan-code="{{ $code }}"

                                    data-url="{{ route('super-admin.system-settings.billing.iyzico.pricing-plan-create') }}"

                                    data-product-input="iyzico-product-{{ $code }}"

                                    data-pricing-input="iyzico-pricing-{{ $code }}"

                                    @if(empty($plan['iyzico']['productReferenceCode'] ?? '') || !empty($plan['iyzico']['pricingPlanReferenceCode'] ?? '')) style="display:none" @endif

                                >

                                    Plani Olustur

                                </button>

                                <span class="text-xs text-slate-500 hidden" data-iyzico-spinner="pricing-plan">Isleniyor...</span>

                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

        <div>

            <button type="submit" class="btn btn-solid-accent">Kaydet</button>

        </div>

    </form>

</div>



@push('scripts')

@once

<script>

    (() => {

        const csrfToken = '{{ csrf_token() }}';

        const buttons = document.querySelectorAll('[data-iyzico-action]');



        function setLoading(button, loading) {

            const action = button.dataset.iyzicoAction;

            const spinner = button.parentElement?.querySelector(`[data-iyzico-spinner="${action}"]`);

            if (spinner) {

                spinner.classList.toggle('hidden', !loading);

            }

            button.disabled = loading;

        }



        function updateInputs(button, payload) {

            const productInput = document.getElementById(button.dataset.productInput);

            const pricingInput = document.getElementById(button.dataset.pricingInput);

            if (productInput && payload.productReferenceCode) {

                productInput.value = payload.productReferenceCode;

            }

            if (pricingInput && payload.pricingPlanReferenceCode) {

                pricingInput.value = payload.pricingPlanReferenceCode;

            }



            if (productInput && productInput.value) {

                const productBtn = document.querySelector(`[data-iyzico-action="product"][data-plan-code="${button.dataset.planCode}"]`);

                if (productBtn) {

                    productBtn.style.display = 'none';

                }

            }

            if (productInput && productInput.value && pricingInput && !pricingInput.value) {

                const pricingBtn = document.querySelector(`[data-iyzico-action="pricing-plan"][data-plan-code="${button.dataset.planCode}"]`);

                if (pricingBtn) {

                    pricingBtn.style.display = '';

                }

            }

            if (pricingInput && pricingInput.value) {

                const pricingBtn = document.querySelector(`[data-iyzico-action="pricing-plan"][data-plan-code="${button.dataset.planCode}"]`);

                if (pricingBtn) {

                    pricingBtn.style.display = 'none';

                }

            }

        }



        buttons.forEach((button) => {

            button.addEventListener('click', async () => {

                const url = button.dataset.url;

                const planCode = button.dataset.planCode;

                if (!url || !planCode) {

                    alert('Plan bilgisi eksik.');

                    return;

                }



                setLoading(button, true);

                let payload = null;

                try {

                    const response = await fetch(url, {

                        method: 'POST',

                        headers: {

                            'Content-Type': 'application/json',

                            'X-CSRF-TOKEN': csrfToken,

                            'X-Requested-With': 'XMLHttpRequest',

                            'Accept': 'application/json',

                        },

                        body: JSON.stringify({ plan_code: planCode }),

                    });



                    const contentType = response.headers.get('content-type') || '';

                    payload = contentType.includes('application/json') ? await response.json().catch(() => null) : null;



                    if (!response.ok) {

                        alert(payload?.message || 'Islem basarisiz oldu.');

                        return;

                    }



                    updateInputs(button, payload || {});

                    alert(payload?.message || 'Islem tamamlandi.');

                } catch (error) {

                    alert('Iyzico ile iletisim saglanamadi. Lutfen tekrar deneyin.');

                } finally {

                    setLoading(button, false);

                }

            });

        });

    })();

</script>

@endonce

@endpush


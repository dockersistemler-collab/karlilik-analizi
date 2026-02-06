<div class="panel-card p-6 max-w-4xl">

    <h3 class="text-sm font-semibold text-slate-800 mb-2">Modul / Feature Yonetimi</h3>

    <p class="text-sm text-slate-600 mb-4">

        Plan bazli ozellikleri buradan acip kapatabilirsiniz.

    </p>



    <div class="mb-4">

        <label class="block text-xs font-semibold text-slate-500 mb-2">Plan Sec</label>

        <select id="feature-plan-select" class="w-full max-w-sm">

            @foreach($featurePlans as $plan)

                <option value="{{ $plan['code'] }}">{{ $plan['label'] }}</option>

            @endforeach

        </select>

    </div>



    @foreach($featurePlans as $plan)

        @php

            $planCode = $plan['code'];

            $enabled = $featureMatrix[$planCode] ?? [];

        @endphp

        <div class="feature-plan-panel" data-plan="{{ $planCode }}">

            <form method="POST" action="{{ route('super-admin.settings.features.update') }}" class="space-y-4">

                @csrf

                <input type="hidden" name="plan_code" value="{{ $planCode }}">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">

                    @foreach($featureKeys as $featureKey)

                        @php

                            $label = $featureLabels[$featureKey] ?? $featureKey;

                        @endphp

                        <label class="flex items-center gap-2 text-sm text-slate-700">

                            <input type="checkbox"

                                   name="features[]"

                                   value="{{ $featureKey }}"

                                   class="rounded"

                                   @checked(in_array($featureKey, $enabled, true))>

                            {{ $label }}

                        </label>

                    @endforeach

                </div>

                <div class="pt-2">

                    <button type="submit" class="btn btn-solid-accent">Kaydet</button>

                </div>

            </form>

        </div>

    @endforeach

</div>



@push('scripts')

    <script>

        const planSelect = document.getElementById('feature-plan-select');

        const panels = document.querySelectorAll('.feature-plan-panel');



        function setActivePlan(code) {

            panels.forEach((panel) => {

                panel.classList.toggle('hidden', panel.dataset.plan !== code);

            });

        }



        if (planSelect) {

            setActivePlan(planSelect.value);

            planSelect.addEventListener('change', () => setActivePlan(planSelect.value));

        }

    </script>

@endpush


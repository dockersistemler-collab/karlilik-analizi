<div class="panel-card p-6 max-w-3xl">

    <h3 class="text-sm font-semibold text-slate-800 mb-2">Integration Health Ayarları</h3>

    <p class="text-sm text-slate-600 mb-4">

        Bu değerler tüm tenant'lar için geçerlidir. Health durumları (OK/DEGRADED/DOWN) ve otomatik bildirim/incident üretimi bu eşiklere göre hesaplanır.

    </p>



    <form method="POST" action="{{ route('super-admin.settings.health.update') }}" class="space-y-4">

        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>

                <label class="block text-sm font-medium text-slate-700">Stale Minutes</label>

                <input type="number" min="1" max="1440" name="stale_minutes" value="{{ old('stale_minutes', $integrationHealthSettings['stale_minutes'] ?? 30) }}" class="mt-1 w-full">

            </div>

            <div>

                <label class="block text-sm font-medium text-slate-700">Window Hours</label>

                <input type="number" min="1" max="168" name="window_hours" value="{{ old('window_hours', $integrationHealthSettings['window_hours'] ?? 24) }}" class="mt-1 w-full">

            </div>

            <div>

                <label class="block text-sm font-medium text-slate-700">Degraded Error Threshold</label>

                <input type="number" min="0" max="1000" name="degraded_error_threshold" value="{{ old('degraded_error_threshold', $integrationHealthSettings['degraded_error_threshold'] ?? 1) }}" class="mt-1 w-full">

            </div>

            <div class="flex items-center gap-2 mt-6">

                <input type="checkbox" name="down_requires_critical" value="1" class="rounded" @checked(old('down_requires_critical', $integrationHealthSettings['down_requires_critical'] ?? true))>

                <label class="text-sm text-slate-700">DOWN için kritik hata şartı</label>

            </div>

        </div>



        <div class="pt-2">

            <button type="submit" class="btn btn-solid-accent">Kaydet</button>

        </div>

    </form>

</div>


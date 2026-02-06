<div class="panel-card p-6 max-w-3xl">

    <h3 class="text-sm font-semibold text-slate-800 mb-2">Incident & SLA Ayarları</h3>

    <p class="text-sm text-slate-600 mb-4">

        Bu değerler tüm tenant'lar için geçerlidir. SLA Risk/Breach rozetleri bu eşiklere göre hesaplanır.

    </p>



    <form method="POST" action="{{ route('super-admin.settings.incident-sla.update') }}" class="space-y-4">

        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div>

                <label class="block text-sm font-medium text-slate-700">ACK SLA (dakika)</label>

                <input type="number" min="1" max="10080" name="ack_sla_minutes" value="{{ old('ack_sla_minutes', $incidentSlaSettings['ack_sla_minutes'] ?? 30) }}" class="mt-1 w-full">

            </div>

            <div>

                <label class="block text-sm font-medium text-slate-700">Resolve SLA (dakika)</label>

                <input type="number" min="1" max="10080" name="resolve_sla_minutes" value="{{ old('resolve_sla_minutes', $incidentSlaSettings['resolve_sla_minutes'] ?? 240) }}" class="mt-1 w-full">

            </div>

        </div>



        <div class="pt-2">

            <button type="submit" class="btn btn-solid-accent">Kaydet</button>

        </div>

    </form>

</div>


<div class="panel-card p-6 max-w-5xl">
    <h3 class="text-sm font-semibold text-slate-800 mb-2">Ne Kazanırım Modülü</h3>
    <p class="text-sm text-slate-600 mb-6">
        Stopaj orani, hizmet bedeli kademeleri, ek hizmet bedeli ve pazaryeri platform hizmetleri buradan yonetilir.
    </p>

    <form method="POST" action="{{ route('super-admin.settings.ne-kazanirim.update') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Stopaj Orani (%)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="w-full"
                    name="withholding_rate_percent"
                    value="{{ $neKazanirimSettings['withholding_rate_percent'] ?? 0 }}"
                    required
                >
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Ek Hizmet Bedeli (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full"
                    name="extra_service_fee_amount"
                    value="{{ $neKazanirimSettings['extra_service_fee_amount'] ?? 0 }}"
                    required
                >
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Trendyol Platform Hizmet (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full"
                    name="platform_service_amount_trendyol"
                    value="{{ $neKazanirimSettings['platform_service_amount_trendyol'] ?? 0 }}"
                    required
                >
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Hepsiburada Platform Hizmet (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full"
                    name="platform_service_amount_hepsiburada"
                    value="{{ $neKazanirimSettings['platform_service_amount_hepsiburada'] ?? 0 }}"
                    required
                >
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">N11 Platform Hizmet (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full"
                    name="platform_service_amount_n11"
                    value="{{ $neKazanirimSettings['platform_service_amount_n11'] ?? 0 }}"
                    required
                >
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Amazon Platform Hizmet (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full"
                    name="platform_service_amount_amazon"
                    value="{{ $neKazanirimSettings['platform_service_amount_amazon'] ?? 0 }}"
                    required
                >
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">&Ccedil;i&ccedil;ek Sepeti Platform Hizmet (TL)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full"
                    name="platform_service_amount_ciceksepeti"
                    value="{{ $neKazanirimSettings['platform_service_amount_ciceksepeti'] ?? 0 }}"
                    required
                >
            </div>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="text-left p-3">Min Satis Fiyati (TL)</th>
                        <th class="text-left p-3">Max Satis Fiyati (TL)</th>
                        <th class="text-left p-3">Sabit Hizmet Bedeli (TL)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($neKazanirimSettings['service_fee_brackets'] ?? []) as $idx => $row)
                        <tr class="border-t border-slate-200">
                            <td class="p-3">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full"
                                    name="service_fee_brackets[{{ $idx }}][min]"
                                    value="{{ $row['min'] }}"
                                    required
                                >
                            </td>
                            <td class="p-3">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full"
                                    name="service_fee_brackets[{{ $idx }}][max]"
                                    value="{{ $row['max'] }}"
                                    placeholder="Bos = limitsiz"
                                >
                            </td>
                            <td class="p-3">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="w-full"
                                    name="service_fee_brackets[{{ $idx }}][fee]"
                                    value="{{ $row['fee'] }}"
                                    required
                                >
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            Formul: Hizmet Bedeli = Kademedeki Sabit Ucret + Ek Hizmet Bedeli + Platform Hizmeti.
        </div>

        <div>
            <button type="submit" class="btn btn-solid-accent">Kaydet</button>
        </div>
    </form>
</div>


@extends('layouts.admin')

@section('header')
    Profit Engine
@endsection

@section('content')
<div class="space-y-6">
    @include('admin.decision-center.partials.nav', ['active' => 'profit'])

    <div class="panel-card p-4">
        <form method="GET" action="{{ route('portal.profit-engine.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-slate-500">Pazaryeri</label>
                <select name="marketplace" class="mt-1 w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                    <option value="">Tum pazaryerleri</option>
                    @foreach($marketplaces as $marketplace)
                        <option value="{{ $marketplace->code }}" @selected(request('marketplace') === $marketplace->code)>{{ $marketplace->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-slate-500">Kural Eksik</label>
                <select name="rule_missing" class="mt-1 w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                    <option value="">Tum durumlar</option>
                    <option value="1" @selected(request('rule_missing') === '1')>Eksik kural var</option>
                    <option value="0" @selected(request('rule_missing') === '0')>Eksik kural yok</option>
                </select>
            </div>
            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit" class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('portal.profit-engine.index') }}" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600">Temizle</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
        <div class="panel-card p-4 xl:col-span-2">
            <h3 class="text-base font-semibold mb-3">Siparis Karlilik Listesi</h3>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b">
                            <th class="px-3 py-2">Siparis</th>
                            <th class="px-3 py-2">Pazaryeri</th>
                            <th class="px-3 py-2">Net Kar</th>
                            <th class="px-3 py-2">Net Marj</th>
                            <th class="px-3 py-2">Durum</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($snapshots as $snapshot)
                            @php
                                $ruleMissing = (bool) data_get($snapshot->meta, 'rule_missing', false);
                                $missingCosts = (array) data_get($snapshot->meta, 'cost_missing_skus', []);
                            @endphp
                            <tr class="border-b border-slate-100">
                                <td class="px-3 py-2">
                                    <div class="font-medium">{{ $snapshot->order?->order_number ?? ('#'.$snapshot->order_id) }}</div>
                                    <div class="text-xs text-slate-400">{{ $snapshot->calculated_at?->format('d.m.Y H:i') }}</div>
                                </td>
                                <td class="px-3 py-2 uppercase">{{ $snapshot->marketplace }}</td>
                                <td class="px-3 py-2 font-semibold {{ (float)$snapshot->net_profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ number_format((float)$snapshot->net_profit, 2) }} TRY
                                </td>
                                <td class="px-3 py-2">
                                    <span class="inline-flex px-2 py-1 rounded text-xs {{ (float)$snapshot->net_margin >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        {{ number_format((float)$snapshot->net_margin, 2) }}%
                                    </span>
                                </td>
                                <td class="px-3 py-2 space-x-1">
                                    @if($ruleMissing)
                                        <span class="inline-flex px-2 py-1 rounded bg-amber-100 text-amber-700 text-xs">Kural eksik</span>
                                    @endif
                                    @if(count($missingCosts) > 0)
                                        <span class="inline-flex px-2 py-1 rounded bg-orange-100 text-orange-700 text-xs">Maliyet eksik</span>
                                    @endif
                                    @if(!$ruleMissing && count($missingCosts) === 0)
                                        <span class="inline-flex px-2 py-1 rounded bg-slate-100 text-slate-700 text-xs">Tamam</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('portal.profit-engine.show', $snapshot) }}" class="btn btn-outline-accent">Detay</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-6 text-center text-slate-500">Kayit bulunamadi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $snapshots->links() }}</div>
        </div>

        <div class="panel-card p-4">
            <h3 class="text-base font-semibold mb-3">Masraf Profili Ekle</h3>
            <form method="POST" action="{{ route('portal.profit-engine.profiles.store') }}" class="space-y-2">
                @csrf
                <input name="name" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="Profil adi" required>
                <input name="packaging_cost" type="number" step="0.01" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="Paketleme maliyeti" required>
                <input name="operational_cost" type="number" step="0.01" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="Operasyon maliyeti" required>
                <input name="return_rate_default" type="number" step="0.01" min="0" max="100" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="Iade riski %" required>
                <input name="ad_cost_default" type="number" step="0.01" min="0" class="w-full border border-slate-200 rounded-lg px-3 py-2" placeholder="Reklam maliyeti" required>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_default" value="1">
                    Varsayilan profil
                </label>
                <button class="btn btn-solid-accent w-full">Kaydet</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="panel-card p-4">
            <h3 class="text-base font-semibold mb-3">Mevcut Masraf Profilleri</h3>
            <div class="space-y-2">
                @forelse($profiles as $profile)
                    <form method="POST" action="{{ route('portal.profit-engine.profiles.update', $profile) }}" class="grid grid-cols-2 gap-2 border border-slate-200 rounded-lg p-3">
                        @csrf
                        @method('PUT')
                        <input name="name" value="{{ $profile->name }}" class="col-span-2 border border-slate-200 rounded px-2 py-1">
                        <input name="packaging_cost" value="{{ $profile->packaging_cost }}" type="number" step="0.01" min="0" class="border border-slate-200 rounded px-2 py-1">
                        <input name="operational_cost" value="{{ $profile->operational_cost }}" type="number" step="0.01" min="0" class="border border-slate-200 rounded px-2 py-1">
                        <input name="return_rate_default" value="{{ $profile->return_rate_default }}" type="number" step="0.01" min="0" max="100" class="border border-slate-200 rounded px-2 py-1">
                        <input name="ad_cost_default" value="{{ $profile->ad_cost_default }}" type="number" step="0.01" min="0" class="border border-slate-200 rounded px-2 py-1">
                        <label class="inline-flex items-center gap-2 text-xs">
                            <input type="checkbox" name="is_default" value="1" @checked($profile->is_default)>
                            Varsayilan
                        </label>
                        <div class="text-right">
                            <button class="btn btn-outline-accent">Guncelle</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('portal.profit-engine.profiles.destroy', $profile) }}" class="mt-1 text-right">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Silinsin mi?')" class="px-3 py-1 border border-rose-300 text-rose-600 rounded">Sil</button>
                    </form>
                @empty
                    <p class="text-sm text-slate-500">Profil yok.</p>
                @endforelse
            </div>
        </div>

        <div class="panel-card p-4">
            <h3 class="text-base font-semibold mb-3">Fee Rule Ekle</h3>
            <form method="POST" action="{{ route('portal.profit-engine.fee-rules.store') }}" class="grid grid-cols-2 gap-2">
                @csrf
                <select name="marketplace" class="col-span-2 border border-slate-200 rounded px-2 py-2" required>
                    @foreach($marketplaces as $marketplace)
                        <option value="{{ $marketplace->code }}">{{ $marketplace->name }}</option>
                    @endforeach
                </select>
                <input name="sku" placeholder="SKU (opsiyonel)" class="col-span-2 border border-slate-200 rounded px-2 py-2">
                <input name="category_id" type="number" min="1" placeholder="Kategori ID (opsiyonel)" class="border border-slate-200 rounded px-2 py-2">
                <input name="brand_id" type="number" min="1" placeholder="Marka ID (opsiyonel)" class="border border-slate-200 rounded px-2 py-2">
                <input name="commission_rate" type="number" step="0.01" min="0" max="100" placeholder="Komisyon %" class="border border-slate-200 rounded px-2 py-2" required>
                <input name="fixed_fee" type="number" step="0.01" min="0" placeholder="Sabit ucret" class="border border-slate-200 rounded px-2 py-2" required>
                <input name="shipping_fee" type="number" step="0.01" min="0" placeholder="Kargo ucreti" class="border border-slate-200 rounded px-2 py-2" required>
                <input name="service_fee" type="number" step="0.01" min="0" placeholder="Hizmet ucreti" class="border border-slate-200 rounded px-2 py-2" required>
                <input name="campaign_contribution_rate" type="number" step="0.01" min="0" max="100" placeholder="Kampanya %" class="border border-slate-200 rounded px-2 py-2" required>
                <input name="vat_rate" type="number" step="0.01" min="0" max="100" placeholder="KDV %" class="border border-slate-200 rounded px-2 py-2" required>
                <input name="priority" type="number" min="0" max="10000" value="10" placeholder="Oncelik" class="border border-slate-200 rounded px-2 py-2" required>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="active" value="1" checked> Aktif
                </label>
                <button class="col-span-2 btn btn-solid-accent">Rule Kaydet</button>
            </form>

            <div class="mt-4 space-y-2 max-h-80 overflow-auto">
                @forelse($feeRules as $rule)
                    <form method="POST" action="{{ route('portal.profit-engine.fee-rules.update', $rule) }}" class="grid grid-cols-2 gap-2 border border-slate-200 rounded-lg p-2">
                        @csrf
                        @method('PUT')
                        <input name="marketplace" value="{{ $rule->marketplace }}" class="col-span-2 border border-slate-200 rounded px-2 py-1" required>
                        <input name="sku" value="{{ $rule->sku }}" class="col-span-2 border border-slate-200 rounded px-2 py-1">
                        <input name="category_id" value="{{ $rule->category_id }}" type="number" min="1" class="border border-slate-200 rounded px-2 py-1">
                        <input name="brand_id" value="{{ $rule->brand_id }}" type="number" min="1" class="border border-slate-200 rounded px-2 py-1">
                        <input name="commission_rate" value="{{ $rule->commission_rate }}" type="number" step="0.01" min="0" max="100" class="border border-slate-200 rounded px-2 py-1">
                        <input name="fixed_fee" value="{{ $rule->fixed_fee }}" type="number" step="0.01" min="0" class="border border-slate-200 rounded px-2 py-1">
                        <input name="shipping_fee" value="{{ $rule->shipping_fee }}" type="number" step="0.01" min="0" class="border border-slate-200 rounded px-2 py-1">
                        <input name="service_fee" value="{{ $rule->service_fee }}" type="number" step="0.01" min="0" class="border border-slate-200 rounded px-2 py-1">
                        <input name="campaign_contribution_rate" value="{{ $rule->campaign_contribution_rate }}" type="number" step="0.01" min="0" max="100" class="border border-slate-200 rounded px-2 py-1">
                        <input name="vat_rate" value="{{ $rule->vat_rate }}" type="number" step="0.01" min="0" max="100" class="border border-slate-200 rounded px-2 py-1">
                        <input name="priority" value="{{ $rule->priority }}" type="number" min="0" max="10000" class="border border-slate-200 rounded px-2 py-1">
                        <label class="inline-flex items-center gap-1 text-xs">
                            <input type="checkbox" name="active" value="1" @checked($rule->active)> Aktif
                        </label>
                        <div class="col-span-2 text-right">
                            <button class="btn btn-outline-accent">Guncelle</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('portal.profit-engine.fee-rules.destroy', $rule) }}" class="text-right mt-1">
                        @csrf
                        @method('DELETE')
                        <button onclick="return confirm('Silinsin mi?')" class="px-3 py-1 border border-rose-300 text-rose-600 rounded">Sil</button>
                    </form>
                @empty
                    <p class="text-sm text-slate-500">Rule bulunamadi.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

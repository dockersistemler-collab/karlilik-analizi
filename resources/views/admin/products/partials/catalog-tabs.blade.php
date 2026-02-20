@php
    $isInventoryView = $isInventoryView ?? false;
    $inventoryMarketplaces = $inventoryMarketplaces ?? collect();
    $selectedMarketplaceId = (int) ($selectedMarketplaceId ?? 0);
    $marketplaceColorClass = static function ($marketplace): string {
        $name = strtolower((string) ($marketplace->name ?? ''));
        $code = strtolower((string) ($marketplace->code ?? ''));
        $key = $code !== '' ? $code : $name;

        if (str_contains($key, 'trendyol')) {
            return 'bg-amber-400 text-slate-900';
        }
        if (str_contains($key, 'hepsiburada')) {
            return 'bg-orange-400 text-slate-900';
        }
        if (str_contains($key, 'n11')) {
            return 'bg-violet-300 text-slate-900';
        }
        if (str_contains($key, 'cicek') || str_contains($key, 'ciceksepeti')) {
            return 'bg-emerald-200 text-slate-900';
        }
        if (str_contains($key, 'amazon')) {
            return 'bg-slate-900 text-white';
        }

        return 'bg-slate-200 text-slate-800';
    };
@endphp

@if($isInventoryView)
<style>
    .inventory-market-tabs {
        background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 8px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
    }
    .inventory-market-chip {
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.25);
        padding: 11px 18px;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.01em;
        transition: transform .18s ease, box-shadow .2s ease, opacity .2s ease, border-color .2s ease;
    }
    .inventory-market-chip:hover {
        transform: translateY(-1px);
    }
    .inventory-market-chip.is-active {
        border-color: rgba(15, 23, 42, 0.08);
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.14);
        transform: translateY(-1px);
    }
    .inventory-action-btn {
        border-radius: 14px;
        border: 1px solid #dbe3ee;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        color: #0f172a;
        font-size: 14px;
        font-weight: 700;
        padding: 11px 20px;
        transition: transform .18s ease, box-shadow .2s ease, border-color .2s ease, background-color .2s ease;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
    }
    .inventory-action-btn:hover:not(:disabled) {
        border-color: #c8d6ea;
        transform: translateY(-1px);
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.1);
    }
    .inventory-action-btn:disabled {
        opacity: .5;
        cursor: not-allowed;
        box-shadow: none;
    }
    .inventory-action-btn.is-primary {
        border-color: #fecdd3;
        background: linear-gradient(180deg, #fff1f2 0%, #ffe4e6 100%);
        color: #9f1239;
    }
</style>
@endif

<div class="flex items-center gap-6 border-b border-slate-200 mb-5">
    @if($isInventoryView)
        <div class="w-full pb-3 flex items-center justify-between gap-4 flex-wrap">
            <div class="inventory-market-tabs flex items-center gap-2 flex-wrap">
                <a href="{{ request()->fullUrlWithQuery(['marketplace_id' => 0, 'page' => null]) }}"
                   class="inventory-market-chip inline-flex items-center {{ $selectedMarketplaceId === 0 ? 'is-active bg-slate-900 text-white' : 'bg-white text-slate-700 hover:text-slate-900' }}">
                    T&uuml;m&uuml;
                </a>
                @foreach($inventoryMarketplaces as $marketplace)
                    @php
                        $pillColor = $marketplaceColorClass($marketplace);
                        $isActivePill = $selectedMarketplaceId === (int) $marketplace->id;
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['marketplace_id' => $marketplace->id, 'page' => null]) }}"
                       class="inventory-market-chip inline-flex items-center {{ $pillColor }} {{ $isActivePill ? 'is-active bg-opacity-95' : 'bg-opacity-60 hover:bg-opacity-80' }}">
                        {{ $marketplace->name }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <form method="POST"
                      action="{{ route('portal.inventory.admin.sync-marketplace') }}"
                      data-inventory-sync-form
                      data-default-sync-scope="all"
                      data-default-label="T&uuml;m&uuml;n&uuml; Senkronize Et">
                    @csrf
                    <input type="hidden" name="sync_scope" value="all">
                    <input type="hidden" name="marketplace_id" value="">
                    <input type="hidden" name="selected_product_ids_csv" value="">
                    <button type="submit" class="inventory-action-btn is-primary" data-inventory-sync-submit>
                        T&uuml;m&uuml;n&uuml; Senkronize Et
                    </button>
                </form>

                <form method="POST"
                      action="{{ route('portal.inventory.admin.sync-marketplace') }}"
                      data-inventory-sync-selected-form>
                    @csrf
                    <input type="hidden" name="sync_scope" value="selected">
                    <input type="hidden" name="marketplace_id" value="{{ $selectedMarketplaceId > 0 ? $selectedMarketplaceId : '' }}">
                    <input type="hidden" name="selected_product_ids_csv" value="">
                    <button type="submit" class="inventory-action-btn" data-inventory-sync-selected-submit disabled>
                        Se&ccedil;ili &Uuml;r&uuml;nlere Stok G&ouml;nder
                    </button>
                </form>

                <form method="POST"
                      action="{{ route('portal.inventory.admin.assign-marketplace') }}"
                      class="relative"
                      data-inventory-open-form>
                    @csrf
                    <input type="hidden" name="selected_product_ids_csv" value="">
                    <button type="button" class="inventory-action-btn" data-inventory-open-toggle>
                        Pazaryerinde A&ccedil;
                    </button>
                    <div class="hidden absolute right-0 top-[110%] z-20 w-72 rounded-xl border border-slate-200 bg-white shadow-lg p-3 space-y-2"
                         data-inventory-open-panel>
                        <label class="block text-xs font-medium text-slate-600">Pazaryeri Se&ccedil;</label>
                        <select name="marketplace_id" class="w-full" required>
                            <option value="">Se&ccedil;iniz</option>
                            @foreach($inventoryMarketplaces as $marketplace)
                                <option value="{{ $marketplace->id }}">{{ $marketplace->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="inventory-action-btn w-full" data-inventory-open-submit disabled>
                            Se&ccedil;ili &Uuml;r&uuml;nleri A&ccedil;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <a href="{{ route('portal.products.index') }}"
           class="pb-3 text-sm font-medium {{ request()->routeIs('portal.products.*') ? 'text-slate-900 border-b-2 border-[#ff4439]' : 'text-slate-500 hover:text-slate-900' }}">
            &Uuml;r&uuml;n Listesi
        </a>

        <a href="{{ route('portal.categories.index') }}"
           class="pb-3 text-sm font-medium {{ request()->routeIs('portal.categories.*') ? 'text-slate-900 border-b-2 border-[#ff4439]' : 'text-slate-500 hover:text-slate-900' }}">
            Kategoriler
        </a>

        <a href="{{ route('portal.brands.index') }}"
           class="pb-3 text-sm font-medium {{ request()->routeIs('portal.brands.*') ? 'text-slate-900 border-b-2 border-[#ff4439]' : 'text-slate-500 hover:text-slate-900' }}">
            Markalar
        </a>
    @endif
</div>

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
        if (str_contains($key, 'cicek') || str_contains($key, 'çiçek')) {
            return 'bg-emerald-200 text-slate-900';
        }
        if (str_contains($key, 'amazon')) {
            return 'bg-slate-900 text-white';
        }

        return 'bg-slate-200 text-slate-800';
    };
@endphp

<div class="flex items-center gap-6 border-b border-slate-200 mb-5">
    @if($isInventoryView)
        <div class="w-full pb-3 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ request()->fullUrlWithQuery(['marketplace_id' => 0, 'page' => null]) }}"
                   class="inline-flex items-center rounded-xl px-5 py-3 text-sm font-semibold border transition {{ $selectedMarketplaceId === 0 ? 'bg-white border-rose-200 text-slate-900 shadow-sm' : 'bg-slate-100 border-slate-200 text-slate-600 hover:bg-slate-200' }}">
                    Tümü
                </a>
                @foreach($inventoryMarketplaces as $marketplace)
                    @php
                        $pillColor = $marketplaceColorClass($marketplace);
                        $isActivePill = $selectedMarketplaceId === (int) $marketplace->id;
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['marketplace_id' => $marketplace->id, 'page' => null]) }}"
                       class="inline-flex items-center rounded-xl px-5 py-3 text-sm font-semibold border transition-all duration-200 {{ $pillColor }} {{ $isActivePill ? 'border-transparent bg-opacity-95 shadow-[0_10px_24px_rgba(239,68,68,0.55)] -translate-y-0.5' : 'border-transparent bg-opacity-55 opacity-85 hover:opacity-100 hover:bg-opacity-70 hover:shadow-md hover:shadow-slate-200/70 hover:-translate-y-0.5' }}"
                       >
                        {{ $marketplace->name }}
                    </a>
                @endforeach
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <form method="POST"
                      action="{{ route('portal.inventory.admin.sync-marketplace') }}"
                      data-inventory-sync-form
                      data-default-sync-scope="all"
                      data-default-label="Tümünü Senkronize Et">
                    @csrf
                    <input type="hidden" name="sync_scope" value="all">
                    <input type="hidden" name="marketplace_id" value="">
                    <input type="hidden" name="selected_product_ids_csv" value="">
                    <button type="submit" class="btn btn-outline-accent" data-inventory-sync-submit>
                        Tümünü Senkronize Et
                    </button>
                </form>

                <form method="POST"
                      action="{{ route('portal.inventory.admin.sync-marketplace') }}"
                      data-inventory-sync-selected-form>
                    @csrf
                    <input type="hidden" name="sync_scope" value="selected">
                    <input type="hidden" name="marketplace_id" value="{{ $selectedMarketplaceId > 0 ? $selectedMarketplaceId : '' }}">
                    <input type="hidden" name="selected_product_ids_csv" value="">
                    <button type="submit" class="btn btn-outline-accent" data-inventory-sync-selected-submit disabled>
                        Secili Urunlere Stok Gonder
                    </button>
                </form>

                <form method="POST"
                      action="{{ route('portal.inventory.admin.assign-marketplace') }}"
                      class="relative"
                      data-inventory-open-form>
                    @csrf
                    <input type="hidden" name="selected_product_ids_csv" value="">
                    <button type="button" class="btn btn-outline" data-inventory-open-toggle>
                        Pazaryerinde Ac
                    </button>
                    <div class="hidden absolute right-0 top-[110%] z-20 w-72 rounded-xl border border-slate-200 bg-white shadow-lg p-3 space-y-2"
                         data-inventory-open-panel>
                        <label class="block text-xs font-medium text-slate-600">Pazaryeri Sec</label>
                        <select name="marketplace_id" class="w-full" required>
                            <option value="">Seciniz</option>
                            @foreach($inventoryMarketplaces as $marketplace)
                                <option value="{{ $marketplace->id }}">{{ $marketplace->name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-outline w-full" data-inventory-open-submit disabled>
                            Secili Urunleri Ac
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <a href="{{ route('portal.products.index') }}"
           class="pb-3 text-sm font-medium {{ request()->routeIs('portal.products.*') ? 'text-slate-900 border-b-2 border-[#ff4439]' : 'text-slate-500 hover:text-slate-900' }}">
            Ürün Listesi
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

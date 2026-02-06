<div class="flex items-center gap-6 border-b border-slate-200 mb-5">
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
</div>



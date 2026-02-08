<div class="panel-card overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200" style="background: linear-gradient(135deg, #f28f67 0%, #df744b 100%);">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-white">{{ $title }}</h3>
                <p class="text-sm text-white/85 mt-1">{{ $subtitle ?? 'Rapor ozeti ve filtrelenebilir tablo gorunumu.' }}</p>
            </div>
            @if(!empty($badge))
                <span class="px-3 py-1 rounded-full text-xs bg-white/20 text-white border border-white/30">{{ $badge }}</span>
            @endif
        </div>
    </div>

    <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="panel-card p-4 border-dashed border-slate-200 lg:col-span-1 bg-slate-50/60">
            <p class="text-xs uppercase text-slate-500">Filtreler</p>
            <ul class="text-sm text-slate-600 mt-2 space-y-1">
                <li>Tarih araligi</li>
                <li>Pazaryeri secimi</li>
                <li>Kategori / durum secimi</li>
            </ul>
        </div>
        <div class="panel-card p-4 border-dashed border-slate-200 lg:col-span-2 bg-slate-50/60">
            <p class="text-xs uppercase text-slate-500">Ciktilar</p>
            <ul class="text-sm text-slate-600 mt-2 space-y-1">
                <li>Kar, ciro, marj metrikleri</li>
                <li>Detay tablolari</li>
                <li>Excel/CSV disa aktarim</li>
            </ul>
        </div>
    </div>
</div>

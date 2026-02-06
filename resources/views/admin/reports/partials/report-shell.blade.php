<div class="panel-card p-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

        <div>

            <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>

            <p class="text-sm text-slate-500 mt-1">{{ $subtitle ?? 'Bu rapor yakında detaylandırılacak.' }}</p>

        </div>

        @if(!empty($badge))

            <span class="px-3 py-1 rounded-full text-xs bg-slate-100 text-slate-600">{{ $badge }}</span>

        @endif

    </div>



    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">

        <div class="panel-card p-4 border-dashed border-slate-200 lg:col-span-1">

            <p class="text-xs uppercase text-slate-400">Filtreler</p>

            <ul class="text-sm text-slate-600 mt-2 space-y-1">

                <li>Pazaryeri seçimi</li>

                <li>Tarih aralığı</li>

                <li>Durum / kategori kırılımı</li>

            </ul>

        </div>

        <div class="panel-card p-4 border-dashed border-slate-200 lg:col-span-2">

            <p class="text-xs uppercase text-slate-400">Çıktılar</p>

            <ul class="text-sm text-slate-600 mt-2 space-y-1">

                <li>Özet metrikler</li>

                <li>Tablo ve grafikler</li>

                <li>CSV / Excel dışa aktarım</li>

            </ul>

        </div>

    </div>

</div>



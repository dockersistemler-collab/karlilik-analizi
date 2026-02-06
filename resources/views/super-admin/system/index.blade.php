@extends('layouts.super-admin')



@section('header')

    Sistem Merkezi

@endsection



@section('content')

    <div class="panel-card p-6 mb-6">

        <h3 class="text-sm font-semibold text-slate-800 mb-2">Hızlı Sistem Erişimi</h3>

        <p class="text-sm text-slate-600">

            Sık kullanılan sistem ayarları ve log sayfalarına buradan ulaşabilirsiniz.

        </p>

    </div>



    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">

        <a href="{{ route('super-admin.settings.index') }}" class="panel-card p-5 hover:shadow-md transition">

            <div class="flex items-center gap-3">

                <i class="fa-solid fa-gear text-slate-600"></i>

                <div>

                    <div class="text-sm font-semibold text-slate-800">Sistem Ayarları</div>

                    <div class="text-xs text-slate-500">Genel yapılandırmalar</div>

                </div>

            </div>

        </a>

        <a href="{{ route('super-admin.mail-logs.index') }}" class="panel-card p-5 hover:shadow-md transition">

            <div class="flex items-center gap-3">

                <i class="fa-solid fa-envelope-open-text text-slate-600"></i>

                <div>

                    <div class="text-sm font-semibold text-slate-800">E-Posta Kayıtları</div>

                    <div class="text-xs text-slate-500">Mail logları ve durumlar</div>

                </div>

            </div>

        </a>

        <a href="{{ route('super-admin.support-view-sessions.index') }}" class="panel-card p-5 hover:shadow-md transition">

            <div class="flex items-center gap-3">

                <i class="fa-solid fa-eye text-slate-600"></i>

                <div>

                    <div class="text-sm font-semibold text-slate-800">Support View Oturumları</div>

                    <div class="text-xs text-slate-500">Destek erişim kayıtları</div>

                </div>

            </div>

        </a>

        <a href="{{ route('super-admin.reports.index') }}" class="panel-card p-5 hover:shadow-md transition">

            <div class="flex items-center gap-3">

                <i class="fa-solid fa-chart-column text-slate-600"></i>

                <div>

                    <div class="text-sm font-semibold text-slate-800">Raporlar</div>

                    <div class="text-xs text-slate-500">Finansal özetler</div>

                </div>

            </div>

        </a>

        <a href="{{ route('super-admin.cargo.health.index') }}" class="panel-card p-5 hover:shadow-md transition">

            <div class="flex items-center gap-3">

                <i class="fa-solid fa-heart-pulse text-slate-600"></i>

                <div>

                    <div class="text-sm font-semibold text-slate-800">Cargo Health</div>

                    <div class="text-xs text-slate-500">Entegrasyon saÄŸlık durumu</div>

                </div>

            </div>

        </a>

        <a href="{{ route('super-admin.banners.index') }}" class="panel-card p-5 hover:shadow-md transition">

            <div class="flex items-center gap-3">

                <i class="fa-solid fa-bullhorn text-slate-600"></i>

                <div>

                    <div class="text-sm font-semibold text-slate-800">Bannerlar</div>

                    <div class="text-xs text-slate-500">Duyuru yönetimi</div>

                </div>

            </div>

        </a>

    </div>

@endsection








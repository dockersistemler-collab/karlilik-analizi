@extends('layouts.admin')



@section('header')

    Destek Merkezi

@endsection



@section('content')

    @php

        $activePlan = auth()->user()?->getActivePlan();
$ownerUser = auth()->user();

        $canTickets = $ownerUser ? app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, 'feature.tickets') : false;

    @endphp



    <div class="panel-card p-6 mb-6 relative overflow-hidden">

        <div class="absolute inset-0 bg-gradient-to-br from-rose-50 via-white to-sky-50"></div>

        <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-rose-200/40 blur-3xl"></div>

        <div class="absolute -bottom-28 -right-28 h-80 w-80 rounded-full bg-sky-200/40 blur-3xl"></div>

        <div class="absolute top-10 right-10 h-16 w-16 rotate-12 rounded-2xl bg-amber-200/35 blur-sm"></div>

        <div class="absolute bottom-10 left-16 h-10 w-10 -rotate-12 rounded-full bg-emerald-200/35 blur-sm"></div>



        <div class="relative">

            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                <div>

                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Destek Merkezi</p>

                    <p class="text-sm text-slate-600 mt-2">

                        Sık sorulan sorular, rehberler ve destek talebi oluşturma.

                    </p>

                </div>

                <div class="flex flex-wrap gap-2">

                    @if($canTickets)

                        <a href="{{ route('portal.tickets.create') }}" class="btn btn-solid-accent">

                            <i class="fa-regular fa-life-ring"></i>

                            Destek Talebi Oluştur

                        </a>

                    @else

                        <a href="{{ route('portal.addons.index') }}" class="btn btn-outline-accent">

                            <i class="fa-solid fa-lock"></i>

                            Paketinizde Kapalı

                        </a>

                    @endif

                </div>

            </div>



            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">

                <div class="rounded-xl border border-rose-200/70 bg-rose-50/60 p-4">

                    <div class="flex items-center gap-3">

                        <span class="h-10 w-10 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center">

                            <i class="fa-regular fa-circle-question"></i>

                        </span>

                        <div>

                            <p class="text-sm font-semibold text-slate-800">Sık Sorulanlar</p>

                            <p class="text-xs text-slate-600">Hızlı çözümler</p>

                        </div>

                    </div>

                </div>

                <div class="rounded-xl border border-sky-200/70 bg-sky-50/60 p-4">

                    <div class="flex items-center gap-3">

                        <span class="h-10 w-10 rounded-xl bg-sky-100 text-sky-700 flex items-center justify-center">

                            <i class="fa-regular fa-file-lines"></i>

                        </span>

                        <div>

                            <p class="text-sm font-semibold text-slate-800">Rehber</p>

                            <p class="text-xs text-slate-600">Adım adım kullanım</p>

                        </div>

                    </div>

                </div>

                <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/60 p-4">

                    <div class="flex items-center gap-3">

                        <span class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center">

                            <i class="fa-regular fa-clock"></i>

                        </span>

                        <div>

                            <p class="text-sm font-semibold text-slate-800">Yanıt Süresi</p>

                            <p class="text-xs text-slate-600">Genelde aynı gün</p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div class="panel-card p-6">

        <div class="flex items-center justify-between gap-3">

            <div>

                <h3 class="text-sm font-semibold text-slate-800">Sık Sorulanlar</h3>

                <p class="text-xs text-slate-500 mt-1">En çok karşılaşılan konular ve hızlı çözümler.</p>

            </div>

            <span class="text-xs text-slate-400">Güncel</span>

        </div>



        <div class="mt-4 grid grid-cols-1 gap-3">

            <div class="rounded-xl border border-slate-200 bg-white p-4">

                <div class="flex items-start gap-3">

                    <span class="mt-0.5 h-9 w-9 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center border border-rose-100">

                        <i class="fa-solid fa-plug"></i>

                    </span>

                    <div>

                        <p class="font-medium text-slate-800">Mağaza bağlantısı kurulamıyor, ne yapmalıyım?</p>

                        <p class="mt-1 text-sm text-slate-600">API anahtarlarınızı ve mağaza ID bilgilerinizi kontrol edin. Gerekirse bağlantıyı kapatıp yeniden aktif edin.</p>

                    </div>

                </div>

            </div>



            <div class="rounded-xl border border-slate-200 bg-white p-4">

                <div class="flex items-start gap-3">

                    <span class="mt-0.5 h-9 w-9 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center border border-sky-100">

                        <i class="fa-solid fa-box-open"></i>

                    </span>

                    <div>

                        <p class="font-medium text-slate-800">Ürünlerim neden görünmüyor?</p>

                        <p class="mt-1 text-sm text-slate-600">Entegrasyonun aktif olduğundan emin olun, ardından senkronu çalıştırın. Filtreleri (kategori/arama) temizleyip tekrar deneyin.</p>

                    </div>

                </div>

            </div>



            <div class="rounded-xl border border-slate-200 bg-white p-4">

                <div class="flex items-start gap-3">

                    <span class="mt-0.5 h-9 w-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center border border-emerald-100">

                        <i class="fa-solid fa-file-invoice"></i>

                    </span>

                    <div>

                        <p class="font-medium text-slate-800">Fatura adresimi nasıl güncellerim?</p>

                        <p class="mt-1 text-sm text-slate-600">Panel → Genel Ayarlar sayfasından güncelleyebilirsiniz.</p>

                    </div>

                </div>

            </div>

        </div>

    </div>

@endsection





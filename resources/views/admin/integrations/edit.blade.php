@extends('layouts.admin')

@section('header')
    {{ $marketplace->name }} Ayarları
@endsection

@section('content')
@php
    $code = $marketplace->code;
    $extra = $credential?->extra_credentials ?? [];
    $storeName = old('store_name', $extra['store_name'] ?? '');
    $fixedDescription = old('fixed_description', $extra['fixed_description'] ?? '');
    $isActive = old('is_active', $credential->is_active ?? false);
    $settings = $marketplace->settings ?? [];
    $videoUrl = $settings['video_url'] ?? null;
    $guideUrl = $settings['guide_url'] ?? null;
    $serviceKeyHelpUrl = $settings['service_key_help_url'] ?? null;

    $guideMap = [
        'hepsiburada' => [
            'steps' => [
                [
                    'title' => '1- API Bilgilerinizi Tanımlayın',
                    'body' => 'Hepsiburada entegrasyonunu gerçekleştirebilmek için ilk olarak API bilgisi almanız gerekmektedir. Bu bilgiye Hepsiburada Partner panelinizden ulaşabilirsiniz.',
                    'note' => 'Not: Hepsiburada üzerinde Entegratör kullanıcı adı olarak kodanka_dev seçimini yapmanız gerekmektedir.',
                ],
                [
                    'title' => '2- Ürünlerinizi Aktarın',
                    'body' => 'Ürünlerinizi aktarmadan önce Kargo ve Süreç Seçimleri ile Sabit Ürün Açıklaması alanlarıyla ilgili ayarlamaları yapabilirsiniz.',
                ],
            ],
        ],
        'ciceksepeti' => [
            'steps' => [
                [
                    'title' => '1- API Bilgilerinizi Tanımlayın',
                    'body' => 'Çiçeksepeti entegrasyonunu gerçekleştirebilmek için ilk olarak API bilgisi almanız gerekmektedir. Bu bilgiye Çiçeksepeti yönetim panelinizden ulaşabilirsiniz.',
                ],
                [
                    'title' => '2- Ürünlerinizi Aktarın',
                    'body' => 'Ürünlerinizi aktarmadan önce Sabit Ürün Açıklaması alanını doldurup entegrasyonunuzu aktive edebilirsiniz.',
                ],
            ],
        ],
        'default' => [
            'steps' => [
                [
                    'title' => '1- API Bilgilerinizi Tanımlayın',
                    'body' => 'Pazaryeri entegrasyonunu kullanabilmek için önce API bilgilerinizi tanımlayın.',
                ],
                [
                    'title' => '2- Ürünlerinizi Aktarın',
                    'body' => 'API bağlantısı tamamlandıktan sonra ürünlerinizi ve siparişlerinizi senkronize edebilirsiniz.',
                ],
            ],
        ],
    ];

    $guide = $guideMap[$code] ?? $guideMap['default'];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <form method="POST" action="{{ route('admin.integrations.update', $marketplace) }}" class="lg:col-span-2 space-y-6">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="panel-card px-4 py-3 border border-rose-200 text-rose-700">
                <ul class="list-disc list-inside text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="panel-card p-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">API Tanımlama</p>
                    <h3 class="text-lg font-semibold text-slate-900 mt-2">API Bilgilerini Tanımlama</h3>
                </div>
            </div>

            <div class="space-y-4">
                @if($code === 'hepsiburada')
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Mağaza Adı <span class="text-rose-500">*</span></label>
                        <input type="text" name="store_name" value="{{ $storeName }}" class="mt-1 w-full border-slate-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Mağaza ID <span class="text-rose-500">*</span></label>
                        <input type="text" name="supplier_id" value="{{ old('supplier_id', $credential->supplier_id ?? '') }}" class="mt-1 w-full border-slate-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Servis Anahtarı</label>
                        <p class="text-xs text-slate-400 mb-1">
                            Servis anahtarının nasıl alındığını detaylı olarak görmek için
                            @if($serviceKeyHelpUrl && $serviceKeyHelpUrl !== '#')
                                <a href="{{ $serviceKeyHelpUrl }}" target="_blank" rel="noopener" class="text-blue-600 font-semibold">tıklayınız</a>.
                            @else
                                tıklayınız.
                            @endif
                        </p>
                        <input type="text" name="api_key" value="{{ old('api_key', $credential->api_key ?? '') }}" class="mt-1 w-full border-slate-300 rounded-md">
                    </div>
                @elseif($code === 'ciceksepeti')
                    <div>
                        <label class="block text-sm font-medium text-slate-700">API Anahtarı (KEY) <span class="text-rose-500">*</span></label>
                        <input type="text" name="api_key" value="{{ old('api_key', $credential->api_key ?? '') }}" class="mt-1 w-full border-slate-300 rounded-md">
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium text-slate-700">API Anahtarı</label>
                        <input type="text" name="api_key" value="{{ old('api_key', $credential->api_key ?? '') }}" class="mt-1 w-full border-slate-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">API Secret</label>
                        <input type="text" name="api_secret" value="{{ old('api_secret', $credential->api_secret ?? '') }}" class="mt-1 w-full border-slate-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Satıcı / Mağaza ID</label>
                        <input type="text" name="supplier_id" value="{{ old('supplier_id', $credential->supplier_id ?? '') }}" class="mt-1 w-full border-slate-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Mağaza ID (opsiyonel)</label>
                        <input type="text" name="store_id" value="{{ old('store_id', $credential->store_id ?? '') }}" class="mt-1 w-full border-slate-300 rounded-md">
                    </div>
                @endif

                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Entegrasyon Durumu</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        <label class="flex items-center gap-2 border border-slate-200 rounded-lg px-4 py-3 cursor-pointer">
                            <input type="radio" name="is_active" value="1" class="text-blue-600" @checked((bool) $isActive === true)>
                            <span class="text-sm font-semibold text-slate-700">Entegrasyon Açık</span>
                        </label>
                        <label class="flex items-center gap-2 border border-slate-200 rounded-lg px-4 py-3 cursor-pointer">
                            <input type="radio" name="is_active" value="0" class="text-blue-600" @checked((bool) $isActive === false)>
                            <span class="text-sm font-semibold text-slate-700">Entegrasyon Kapalı</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="pt-5">
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-semibold">
                    Ayarları Kaydet
                </button>
            </div>
        </div>

        @if($code === 'ciceksepeti')
            <div class="panel-card p-5" id="fixed-description">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h4 class="text-base font-semibold text-slate-900">Sabit Ürün Açıklaması</h4>
                        <span class="text-slate-400 text-xs">?</span>
                    </div>
                    <a href="#fixed-description" class="inline-flex items-center gap-2 border border-blue-200 text-blue-600 text-sm font-semibold px-4 py-2 rounded-lg">
                        <span class="text-lg leading-none">+</span>
                        SABİT AÇIKLAMA EKLE
                    </a>
                </div>
                <div class="mt-4">
                    <textarea name="fixed_description" rows="4" class="w-full border-slate-300 rounded-md" placeholder="Ürün açıklaması için sabit metin girin.">{{ $fixedDescription }}</textarea>
                    <p class="text-xs text-slate-500 mt-2">Bu açıklama seçili pazaryerine gönderilen ürünlerin altına eklenir.</p>
                </div>
            </div>
        @endif
    </form>

    <div class="panel-card p-6">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900">Kurulum Rehberi</h3>
            @if($videoUrl && $videoUrl !== '#')
                <a href="{{ $videoUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-rose-100 text-rose-600 px-3 py-1.5 rounded-full text-xs font-semibold">
                    <span class="text-lg leading-none">▶</span>
                    VİDEOLU ANLATIM
                </a>
            @else
                <span class="inline-flex items-center gap-2 bg-slate-100 text-slate-500 px-3 py-1.5 rounded-full text-xs font-semibold">
                    <span class="text-lg leading-none">▶</span>
                    VİDEOLU ANLATIM (Yakında)
                </span>
            @endif
        </div>

        <div class="mt-4 max-h-72 overflow-y-auto pr-2 space-y-4 text-sm text-slate-600">
            @foreach($guide['steps'] as $step)
                <div class="space-y-2">
                    <p class="font-semibold text-slate-900">{{ $step['title'] }}</p>
                    <p>{{ $step['body'] }}</p>
                    @if(!empty($step['note']))
                        <div class="flex items-start gap-2 text-rose-600 text-xs">
                            <span class="mt-0.5">!</span>
                            <span>{{ $step['note'] }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
            <p class="text-xs text-orange-500 font-semibold">Devamını Okumak İçin Kaydırın</p>
        </div>

        <div class="mt-5">
            @if($guideUrl && $guideUrl !== '#')
                <a href="{{ $guideUrl }}" target="_blank" rel="noopener" class="w-full border border-blue-200 text-blue-600 px-4 py-2 rounded-lg font-semibold text-sm text-center inline-block">
                    DETAYLI KURULUM REHBERİNİ İNCELE
                </a>
            @else
                <div class="w-full border border-slate-200 text-slate-500 px-4 py-2 rounded-lg font-semibold text-sm text-center">
                    DETAYLI KURULUM REHBERİ (Yakında)
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

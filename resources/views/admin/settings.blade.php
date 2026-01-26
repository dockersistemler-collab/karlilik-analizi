@extends('layouts.admin')

@section('header')
    Genel Ayarlar
@endsection

@section('content')
    <div class="panel-card p-6">
        <div class="max-w-3xl mx-auto">
            <div class="mb-6">
                <input type="text" placeholder="Ara..." class="w-full px-4 py-3 border border-slate-200 rounded-lg bg-white">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl border border-slate-100 p-4 text-sm text-slate-600 space-y-2">
                    <div class="font-semibold text-slate-800">Tüm Ayarlar</div>
                    <div>Firma Ayarları</div>
                    <div>Fatura Ayarları</div>
                    <div>Ürün Listesi Ayarları</div>
                    <div>Pazaryeri Ayarları</div>
                    <div>Kargo Etiket Ayarları</div>
                    <div>Fatura Açıklama Alanı Tanımları</div>
                    <div>Bildirim Ayarları</div>
                    <div>Ürün Ayarları</div>
                </div>

                <div class="md:col-span-2 bg-white rounded-xl border border-slate-100 p-6">
                    <form class="space-y-4" method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Firma Logosu</label>
                            <div class="flex items-center gap-4">
                                @if($user?->company_logo_path)
                                    <img src="{{ asset('storage/' . $user->company_logo_path) }}" alt="Firma Logosu" class="h-14 w-14 rounded-xl object-cover border border-slate-200">
                                @else
                                    <div class="h-14 w-14 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-xs text-slate-400">
                                        Logo
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <input type="file" name="company_logo" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                                    <p class="text-xs text-slate-400 mt-1">Önerilen: 512x512 px, PNG/JPG</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Slogan</label>
                            <input type="text" name="company_slogan" value="{{ old('company_slogan', $user?->company_slogan) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Firma Adı <span class="text-rose-500">*</span></label>
                            <input type="text" name="company_name" value="{{ old('company_name', $user?->company_name) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Telefon <span class="text-rose-500">*</span></label>
                            <input type="text" name="company_phone" value="{{ old('company_phone', $user?->company_phone) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Bilgilendirme E-posta Adresi</label>
                            <input type="email" name="notification_email" value="{{ old('notification_email', $user?->notification_email) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Kullanıcı Giriş E-posta Adresi</label>
                            <input type="email" value="{{ $user?->email }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-slate-50" disabled>
                            <p class="text-xs text-slate-400 mt-1">Bu alan profil sayfasından güncellenir.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Firma Adresi</label>
                            <textarea rows="3" name="company_address" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">{{ old('company_address', $user?->company_address) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Firma Websitesi</label>
                            <input type="text" name="company_website" value="{{ old('company_website', $user?->company_website) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="invoice_number_tracking" value="1" class="h-4 w-4 text-blue-600 border-slate-300 rounded" @checked(old('invoice_number_tracking', $user?->invoice_number_tracking))>
                            <label class="text-sm text-slate-700">Fatura No Takibi Yapılsın mı?</label>
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-semibold">
                                Ayarları Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.super-admin')

@section('header')
    Sistem Ayarları
@endsection

@section('content')
    <div class="panel-card p-6 max-w-4xl mb-6">
        <h3 class="text-sm font-semibold text-slate-800 mb-2">Müşteri Ayarları</h3>
        <p class="text-sm text-slate-600 mb-4">
            Seçtiğiniz müşterinin firma ve fatura ayarlarını buradan güncelleyebilirsiniz.
        </p>

        <form method="GET" action="{{ route('super-admin.settings.index') }}" class="mb-6">
            <label class="block text-sm font-medium text-slate-700 mb-1">Müşteri Seç</label>
            <select name="user_id" class="w-full max-w-lg" onchange="this.form.submit()">
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected($selectedUser && $selectedUser->id === $user->id)>
                        {{ $user->company_name ?: $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>
        </form>

        @if($selectedUser)
            <form class="space-y-4" method="POST" action="{{ route('super-admin.settings.client.update', $selectedUser) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Firma Logosu</label>
                    <div class="flex items-center gap-4">
                        @if($selectedUser->company_logo_path)
                            <img src="{{ asset('storage/' . $selectedUser->company_logo_path) }}" alt="Firma Logosu" class="h-14 w-14 rounded-xl object-cover border border-slate-200">
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
                    <input type="text" name="company_slogan" value="{{ old('company_slogan', $selectedUser->company_slogan) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Firma Adı</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $selectedUser->company_name) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Telefon</label>
                    <input type="text" name="company_phone" value="{{ old('company_phone', $selectedUser->company_phone) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Bilgilendirme E-posta Adresi</label>
                    <input type="email" name="notification_email" value="{{ old('notification_email', $selectedUser->notification_email) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kullanıcı Giriş E-posta Adresi</label>
                    <input type="email" value="{{ $selectedUser->email }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-slate-50" disabled>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Firma Adresi</label>
                    <textarea rows="3" name="company_address" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">{{ old('company_address', $selectedUser->company_address) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Firma Websitesi</label>
                    <input type="text" name="company_website" value="{{ old('company_website', $selectedUser->company_website) }}" class="w-full border border-slate-200 rounded-lg px-3 py-2 bg-white">
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="invoice_number_tracking" value="1" class="h-4 w-4 text-blue-600 border-slate-300 rounded" @checked(old('invoice_number_tracking', $selectedUser->invoice_number_tracking))>
                    <label class="text-sm text-slate-700">Fatura No Takibi Yapılsın mı?</label>
                </div>
                <div class="pt-2">
                    <button type="submit" class="btn btn-solid-accent">
                        Ayarları Kaydet
                    </button>
                </div>
            </form>
        @else
            <p class="text-sm text-slate-500">Güncellenecek müşteri bulunamadı.</p>
        @endif
    </div>

    <div class="panel-card p-6 max-w-3xl mb-6">
        <h3 class="text-sm font-semibold text-slate-800 mb-2">Rapor Dışa Aktarım</h3>
        <p class="text-sm text-slate-600 mb-4">Tüm rapor sayfalarındaki dışa aktarma butonlarını kontrol eder.</p>

        <form method="POST" action="{{ route('super-admin.settings.report-exports') }}" class="flex items-center justify-between">
            @csrf
            <div class="flex items-center gap-3">
                <input type="checkbox" name="reports_exports_enabled" value="1" class="h-4 w-4 text-blue-600 border-slate-300 rounded" @checked($reportExportsEnabled)>
                <label class="text-sm text-slate-700">Rapor dışa aktarma açık</label>
            </div>
            <button type="submit" class="btn btn-outline">Kaydet</button>
        </form>
    </div>

    <div class="panel-card p-6 max-w-3xl">
        <h3 class="text-sm font-semibold text-slate-800 mb-2">Tavsiye Programı</h3>
        <p class="text-sm text-slate-600 mb-4">
            Tavsiye programını etkinleştirip dönemsel olarak ödülleri ve limitleri yönetebilirsiniz.
        </p>

        <form method="POST" action="{{ route('super-admin.settings.referral') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Program Adı</label>
                <input type="text" name="name" value="{{ old('name', $program->name ?? 'Webreen Tavsiye Programı') }}" class="mt-1 w-full" required>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-slate-700">Açıklama</label>
                <textarea name="description" rows="3" class="mt-1 w-full">{{ old('description', $program->description ?? '') }}</textarea>
            </div>

            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" class="rounded" @checked(old('is_active', $program->is_active ?? true))>
                <span class="text-sm text-slate-600">Program aktif</span>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Tavsiye Eden Ödülü</label>
                <div class="flex gap-2 mt-1">
                    <select name="referrer_reward_type" class="flex-1">
                        <option value="duration" @selected(old('referrer_reward_type', $program->referrer_reward_type ?? 'duration') === 'duration')>Ay Kullanım</option>
                        <option value="percent" @selected(old('referrer_reward_type', $program->referrer_reward_type ?? 'duration') === 'percent')>% İndirim</option>
                    </select>
                    <input type="number" step="0.01" name="referrer_reward_value" value="{{ old('referrer_reward_value', $program->referrer_reward_value ?? 1) }}" class="w-32">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Tavsiye Alan Ödülü</label>
                <div class="flex gap-2 mt-1">
                    <select name="referred_reward_type" class="flex-1">
                        <option value="duration" @selected(old('referred_reward_type', $program->referred_reward_type ?? 'duration') === 'duration')>Ay Kullanım</option>
                        <option value="percent" @selected(old('referred_reward_type', $program->referred_reward_type ?? 'duration') === 'percent')>% İndirim</option>
                    </select>
                    <input type="number" step="0.01" name="referred_reward_value" value="{{ old('referred_reward_value', $program->referred_reward_value ?? 1) }}" class="w-32">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Yıllık Kullanım Limiti</label>
                <input type="number" name="max_uses_per_referrer_per_year" value="{{ old('max_uses_per_referrer_per_year', $program->max_uses_per_referrer_per_year ?? 0) }}" class="mt-1 w-full">
                <p class="text-xs text-slate-500 mt-1">0 = limitsiz</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Program Dönemi</label>
                <div class="flex gap-2 mt-1">
                    <input type="date" name="starts_at" value="{{ old('starts_at', optional($program?->starts_at)->format('Y-m-d')) }}" class="flex-1">
                    <input type="date" name="ends_at" value="{{ old('ends_at', optional($program?->ends_at)->format('Y-m-d')) }}" class="flex-1">
                </div>
            </div>

            <div class="md:col-span-2 flex items-center gap-3 mt-2">
                <button type="submit">Kaydet</button>
            </div>
        </form>
    </div>
@endsection

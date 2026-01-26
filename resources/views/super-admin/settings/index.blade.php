@extends('layouts.super-admin')

@section('header')
    Sistem Ayarları
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow p-6 max-w-3xl mb-6">
        <h3 class="text-sm font-semibold text-slate-800 mb-2">Genel Ayarlar</h3>
        <p class="text-sm text-slate-600">
            Bu bölümde SMTP, ödeme sağlayıcıları, genel branding ve güvenlik ayarları yönetilecek.
            Şimdilik iskelet yapı hazırlandı.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 max-w-3xl">
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

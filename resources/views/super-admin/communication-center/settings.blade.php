@extends('layouts.super-admin')

@section('header')
    Iletisim Merkezi Ayarlari
@endsection

@section('content')
    <div class="panel-card p-6 max-w-3xl">
        <form method="POST" action="{{ route('super-admin.communication-center.settings.update') }}" class="space-y-4">
            @csrf
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="ai_enabled" value="1" @checked((bool)($setting->ai_enabled ?? true))>
                <span>YZ Onerileri Aktif</span>
            </label>
            <div>
                <label class="block text-sm">Bildirim E-postasi</label>
                <input name="notification_email" class="w-full mt-1" value="{{ old('notification_email', $setting->notification_email ?? '') }}">
            </div>
            <div>
                <label class="block text-sm">Zamanlayici Araligi (dk)</label>
                <input type="number" min="1" max="60" name="cron_interval_minutes" class="w-full mt-1" value="{{ old('cron_interval_minutes', $setting->cron_interval_minutes ?? 5) }}">
            </div>
            <div class="grid grid-cols-2 gap-3">
                @php($weights = (array)($setting->priority_weights ?? []))
                @php($weightLabels = [
                    'time_left' => 'Kalan Sure',
                    'store_rating_risk' => 'Magaza Puan Riski',
                    'sales_velocity' => 'Satis Hizi',
                    'margin' => 'Marj',
                    'buybox_critical' => 'Buybox Kritiklik',
                    'critical_minutes' => 'Kritik Esik (dk)',
                ])
                @foreach(['time_left','store_rating_risk','sales_velocity','margin','buybox_critical','critical_minutes'] as $key)
                    <div>
                        <label class="block text-sm">{{ $weightLabels[$key] ?? $key }}</label>
                        <input
                            type="number"
                            min="{{ $key === 'critical_minutes' ? 1 : 0 }}"
                            max="{{ $key === 'critical_minutes' ? 1440 : 10 }}"
                            name="priority_weights[{{ $key }}]"
                            class="w-full mt-1"
                            value="{{ old('priority_weights.'.$key, $weights[$key] ?? ($key === 'time_left' ? 3 : ($key === 'critical_minutes' ? 30 : 0))) }}"
                        >
                    </div>
                @endforeach
            </div>
            <button class="btn btn-solid-accent">Kaydet</button>
        </form>
    </div>
@endsection

@extends('layouts.super-admin')

@section('header')
    İletişim SLA Kuralları
@endsection

@section('content')
    <div class="space-y-5">
        <div class="panel-card p-6">
            <h3 class="text-sm font-semibold mb-3">Yeni SLA Kuralı</h3>
            <form method="POST" action="{{ route('super-admin.communication-center.sla.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                @csrf
                <div>
                    <label class="text-xs">Pazaryeri</label>
                    <select name="marketplace_id" class="w-full mt-1">
                        <option value="">Genel</option>
                        @foreach($marketplaces as $marketplace)
                            <option value="{{ $marketplace->id }}">{{ $marketplace->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs">Kanal</label>
                    <select name="channel" class="w-full mt-1" required>
                        @php($channelLabels = ['question' => 'Soru', 'message' => 'Mesaj', 'review' => 'Yorum', 'return' => 'İade'])
                        @foreach(['question','message','review','return'] as $channel)
                            <option value="{{ $channel }}">{{ $channelLabels[$channel] ?? $channel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs">SLA Dakika</label>
                    <input type="number" name="sla_minutes" class="w-full mt-1" required min="1">
                </div>
                <div>
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Aktif</span>
                    </label>
                </div>
                <button class="btn btn-solid-accent">Ekle</button>
            </form>
        </div>

        <div class="panel-card p-6">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2">Pazaryeri</th>
                        <th class="py-2">Kanal</th>
                        <th class="py-2">SLA (dk)</th>
                        <th class="py-2">Durum</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @php($channelLabels = ['question' => 'Soru', 'message' => 'Mesaj', 'review' => 'Yorum', 'return' => 'İade'])
                    @foreach($slaRules as $sla)
                        <tr class="border-b">
                            <td class="py-2">{{ $sla->marketplace?->name ?? 'Genel' }}</td>
                            <td class="py-2">{{ $channelLabels[$sla->channel] ?? $sla->channel }}</td>
                            <td class="py-2">{{ $sla->sla_minutes }}</td>
                            <td class="py-2">{{ $sla->is_active ? 'Aktif' : 'Pasif' }}</td>
                            <td class="py-2 text-right">
                                <form method="POST" action="{{ route('super-admin.communication-center.sla.destroy', $sla) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline" onclick="return confirm('Silinsin mi?')" type="submit">Sil</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection


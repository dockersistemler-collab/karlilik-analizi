@extends('layouts.admin')

@section('header')
    Kontrol Kulesi Sinyalleri
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-4">
        <form method="GET" action="{{ route('portal.control-tower.signals') }}" class="grid grid-cols-1 md:grid-cols-5 gap-2">
            <input type="date" name="date" value="{{ $date->toDateString() }}" class="border border-slate-200 rounded px-3 py-2">
            <input type="text" name="type" value="{{ request('type') }}" placeholder="type" class="border border-slate-200 rounded px-3 py-2">
            <select name="severity" class="border border-slate-200 rounded px-3 py-2">
                <option value="">Tum severity</option>
                @foreach(['info','warning','critical'] as $sev)
                    <option value="{{ $sev }}" @selected(request('severity') === $sev)>{{ strtoupper($sev) }}</option>
                @endforeach
            </select>
            <select name="marketplace" class="border border-slate-200 rounded px-3 py-2">
                <option value="">Tum pazaryerleri</option>
                @foreach(['trendyol','hepsiburada','amazon','n11'] as $mp)
                    <option value="{{ $mp }}" @selected(request('marketplace') === $mp)>{{ strtoupper($mp) }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('portal.control-tower.signals') }}" class="btn btn-outline">Temizle</a>
            </div>
        </form>
    </div>

    @include('admin.control-tower.components.ct-signal-list', [
        'signals' => $signals,
        'title' => 'Sinyaller',
    ])
</div>
@endsection

@extends('layouts.admin')

@section('header')
    {{ $title }}
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-3 flex gap-2">
        <a href="{{ route('portal.control-tower.index', ['date' => $date->toDateString()]) }}" class="btn btn-outline">Kontrol Kulesi</a>
        <a href="{{ route('portal.control-tower.signals', ['date' => $date->toDateString()]) }}" class="btn btn-outline">Sinyaller</a>
    </div>

    @include('admin.control-tower.components.ct-signal-list', [
        'signals' => $signals,
        'title' => $title.' Sinyalleri',
        'empty' => 'Bu drilldown için sinyal bulunmuyor.',
    ])
</div>
@endsection

@extends('layouts.admin')

@section('header')
    Action Recommendation
@endsection

@section('content')
<div class="space-y-4">
    <div class="panel-card p-4 flex justify-between items-start gap-3">
        <div>
            <h2 class="text-lg font-semibold">{{ $recommendation->title }}</h2>
            <p class="text-sm text-slate-500 uppercase">{{ $recommendation->marketplace }} | {{ $recommendation->action_type }}</p>
            <p class="mt-2 text-sm">{{ $recommendation->description }}</p>
        </div>
        <div class="text-right">
            <div class="text-xs text-slate-500">Status</div>
            <div class="font-semibold">{{ $recommendation->status }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <div class="panel-card p-4">
            <h3 class="font-semibold mb-2">Reason</h3>
            <pre class="text-xs bg-slate-50 border border-slate-200 rounded p-3 overflow-auto">{{ json_encode($recommendation->reason, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        <div class="panel-card p-4">
            <h3 class="font-semibold mb-2">Suggested Payload</h3>
            <pre class="text-xs bg-slate-50 border border-slate-200 rounded p-3 overflow-auto">{{ json_encode($recommendation->suggested_payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    </div>

    <div class="panel-card p-4">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold mb-2">Beklenen Etki</h3>
            <form method="POST" action="{{ route('portal.action-engine.impact.refresh', $recommendation) }}">
                @csrf
                <button class="btn btn-outline-accent">Simulasyonu Yenile</button>
            </form>
        </div>
        @if($recommendation->impact)
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-3 text-sm">
                <div class="border border-slate-200 rounded p-2">
                    <div class="text-xs text-slate-500">Confidence</div>
                    <div class="font-semibold">{{ number_format((float) $recommendation->impact->confidence, 2) }}</div>
                </div>
                <div class="border border-slate-200 rounded p-2">
                    <div class="text-xs text-slate-500">Risk Effect</div>
                    <div class="font-semibold">{{ number_format((float) $recommendation->impact->risk_effect, 2) }}</div>
                </div>
                <div class="border border-slate-200 rounded p-2">
                    <div class="text-xs text-slate-500">Calculated At</div>
                    <div class="font-semibold">{{ optional($recommendation->impact->calculated_at)->format('d.m.Y H:i') ?: '-' }}</div>
                </div>
            </div>
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 mt-3">
                <div>
                    <div class="text-xs text-slate-500 mb-1">Delta</div>
                    <pre class="text-xs bg-slate-50 border border-slate-200 rounded p-3 overflow-auto">{{ json_encode($recommendation->impact->delta, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div>
                    <div class="text-xs text-slate-500 mb-1">Assumptions</div>
                    <pre class="text-xs bg-slate-50 border border-slate-200 rounded p-3 overflow-auto">{{ json_encode($recommendation->impact->assumptions, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @else
            <p class="text-sm text-slate-500">Henüz etki simülasyonu yok.</p>
        @endif
    </div>

    @if($recommendation->status === 'open')
    <div class="panel-card p-4 flex gap-2">
        <form method="POST" action="{{ route('portal.action-engine.apply', $recommendation) }}">
            @csrf
            <button class="btn btn-solid-accent">Apply</button>
        </form>
        <form method="POST" action="{{ route('portal.action-engine.dismiss', $recommendation) }}">
            @csrf
            <button class="px-4 py-2 border border-slate-200 rounded text-slate-700">Dismiss</button>
        </form>
    </div>
    @endif
</div>
@endsection

@props([
    'title' => '',
    'value' => '-',
    'sub' => null,
    'tone' => 'default',
])

@php
    $toneClass = match($tone) {
        'good' => 'text-emerald-600',
        'warn' => 'text-amber-600',
        'bad' => 'text-rose-600',
        default => 'text-slate-900',
    };
@endphp

<div class="panel-card p-4">
    <div class="text-xs text-slate-500">{{ $title }}</div>
    <div class="text-2xl font-semibold {{ $toneClass }}">{{ $value }}</div>
    @if($sub)
        <div class="text-xs text-slate-500 mt-1">{{ $sub }}</div>
    @endif
</div>


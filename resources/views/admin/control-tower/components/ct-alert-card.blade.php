@props([
    'title' => '',
    'message' => '',
    'severity' => 'info',
])

@php
    $classes = match($severity) {
        'critical' => 'border-rose-200 bg-rose-50 text-rose-800',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-800',
        default => 'border-sky-200 bg-sky-50 text-sky-800',
    };
@endphp

<div class="panel-card border p-3 {{ $classes }}">
    <div class="text-sm font-semibold">{{ $title }}</div>
    <div class="text-xs mt-1">{{ $message }}</div>
</div>


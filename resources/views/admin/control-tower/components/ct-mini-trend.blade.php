@props([
    'values' => [],
    'width' => 180,
    'height' => 42,
    'stroke' => '#2563eb',
])

@php
    $series = collect($values)->map(fn ($v) => (float) $v)->values();
    $w = (int) $width;
    $h = (int) $height;
    $min = $series->min() ?? 0.0;
    $max = $series->max() ?? 0.0;
    $span = max(0.0001, $max - $min);
    $count = max(1, $series->count());
    $stepX = $count > 1 ? ($w / ($count - 1)) : $w;
    $points = $series->map(function ($value, $idx) use ($min, $span, $h, $stepX) {
        $x = $idx * $stepX;
        $y = $h - (($value - $min) / $span) * $h;
        return round($x, 2).','.round($y, 2);
    })->implode(' ');
@endphp

<svg width="{{ $w }}" height="{{ $h }}" viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="trend">
    @if($series->count() > 1)
        <polyline fill="none" stroke="{{ $stroke }}" stroke-width="2" points="{{ $points }}" />
    @endif
</svg>


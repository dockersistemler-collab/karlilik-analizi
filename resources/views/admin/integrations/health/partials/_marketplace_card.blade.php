@php

    $status = $marketplace['status'] ?? 'OK';

    $statusClass = match ($status) {

        'DOWN' => 'bg-rose-100 text-rose-700 border-rose-200',

        'DEGRADED' => 'bg-amber-100 text-amber-700 border-amber-200',

        default => 'bg-emerald-100 text-emerald-700 border-emerald-200',

    };

@endphp



<div class="panel-card p-4 flex flex-col gap-3">

    <div class="flex items-center justify-between gap-3">

        <div>

            <div class="text-base font-semibold text-slate-800">{{ $marketplace['marketplace_name'] }}</div>

            <div class="text-xs text-slate-500">{{ $marketplace['marketplace_code'] }}</div>

        </div>

        <span class="text-xs font-semibold px-2 py-1 rounded-full border {{ $statusClass }}">{{ $status }}</span>

    </div>



    <div class="text-xs text-slate-500 space-y-1">

        <div>Son basarili: {{ $marketplace['last_success_at']?->diffForHumans() ?? 'Yok' }}</div>

        <div>Son deneme: {{ $marketplace['last_attempt_at']?->diffForHumans() ?? 'Yok' }}</div>

        <div>Son 24s hata: {{ $marketplace['error_count_24h'] ?? 0 }}</div>

    </div>



    @if(!empty($marketplace['last_error']))

        <div class="text-xs text-rose-700 bg-rose-50 border border-rose-100 rounded-md p-2 max-h-10 overflow-hidden">

            {{ \Illuminate\Support\Str::limit($marketplace['last_error']['message'] ?? '', 160) }}

        </div>

    @endif



    <div class="text-xs text-slate-600">

        <div class="font-semibold">Token</div>

        <div>Durum: {{ ($marketplace['token_valid'] ?? false) ? 'Gecerli' : 'Gecersiz' }}</div>

        <div>Bitis: {{ $marketplace['token_expires_at']?->toDateTimeString() ?? 'Yok' }}</div>

    </div>



    <div class="border-t border-slate-100 pt-3 text-xs text-slate-600">

        <div class="font-semibold mb-1">Senkronlar</div>

        @foreach($marketplace['syncs'] as $key => $sync)

            <div class="flex flex-col gap-1 mb-2">

                <div class="font-semibold text-slate-700">{{ strtoupper(str_replace('_', ' ', $key)) }}</div>

                <div>Son basarili: {{ $sync['last_success_at']?->diffForHumans() ?? 'Yok' }}</div>

                <div>Son deneme: {{ $sync['last_attempt_at']?->diffForHumans() ?? 'Yok' }}</div>

                <div>Son hata: {{ $sync['last_error'] ? \Illuminate\Support\Str::limit($sync['last_error']['message'] ?? '', 120) : 'Yok' }}</div>

                <div>24s hata: {{ $sync['error_count_24h'] ?? 0 }}</div>

            </div>

        @endforeach

    </div>



    <div class="flex flex-wrap gap-2">

        @foreach($marketplace['actions'] as $action)

            <a href="{{ $action['url'] }}" class="btn btn-outline-accent text-xs">{{ $action['label'] }}</a>

        @endforeach

    </div>

</div>


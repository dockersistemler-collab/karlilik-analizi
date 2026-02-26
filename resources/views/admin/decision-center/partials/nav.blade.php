@php
    $currentMarketplace = (string) request('marketplace', ($marketplace ?? ''));
    $currentDateFrom = (string) request('date_from', ($dateFrom ?? now()->subDays(13)->toDateString()));
    $currentDateTo = (string) request('date_to', ($dateTo ?? now()->toDateString()));
    $currentSku = (string) request('sku', ($sku ?? ''));
    $active = $active ?? 'center';
    $sharedQuery = array_filter([
        'marketplace' => $currentMarketplace !== '' ? $currentMarketplace : null,
        'date_from' => $currentDateFrom !== '' ? $currentDateFrom : null,
        'date_to' => $currentDateTo !== '' ? $currentDateTo : null,
        'sku' => $currentSku !== '' ? $currentSku : null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<div class="panel-card p-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="period-tabs inline-flex gap-1">
            <a href="{{ route('portal.decision-center.index', $sharedQuery) }}" class="period-tab px-3 inline-flex items-center {{ $active === 'center' ? 'is-active' : '' }}">Decision Center</a>
            <a href="{{ route('portal.profit-engine.index', $sharedQuery) }}" class="period-tab px-3 inline-flex items-center {{ $active === 'profit' ? 'is-active' : '' }}">Profit</a>
            <a href="{{ route('portal.marketplace-risk.index', $sharedQuery) }}" class="period-tab px-3 inline-flex items-center {{ $active === 'risk' ? 'is-active' : '' }}">Risk</a>
            <a href="{{ route('portal.action-engine.index', $sharedQuery) }}" class="period-tab px-3 inline-flex items-center {{ $active === 'action' ? 'is-active' : '' }}">Action</a>
        </div>
    </div>

    <form method="GET" action="{{ route('portal.decision-center.index') }}" class="mt-3 grid grid-cols-1 md:grid-cols-5 gap-2">
        <select name="marketplace" class="border border-slate-200 rounded px-3 py-2">
            <option value="">Tum Pazaryerleri</option>
            @foreach(($marketplaces ?? collect()) as $item)
                <option value="{{ $item->code }}" @selected($currentMarketplace === $item->code)>{{ $item->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ $currentDateFrom }}" class="border border-slate-200 rounded px-3 py-2">
        <input type="date" name="date_to" value="{{ $currentDateTo }}" class="border border-slate-200 rounded px-3 py-2">
        <input type="text" name="sku" value="{{ $currentSku }}" class="border border-slate-200 rounded px-3 py-2" placeholder="SKU (opsiyonel)">
        <div class="flex gap-2">
            <button class="btn btn-solid-accent">Uygula</button>
            <a href="{{ route('portal.decision-center.index') }}" class="btn btn-outline">Temizle</a>
        </div>
    </form>
</div>

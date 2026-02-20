@extends('layouts.admin')



@section('header')

    Ã‡ok Satan ÃœrÃ¼nler

@endsection



@section('content')

    @php

        $activePlan = auth()->user()?->getActivePlan();
$ownerUser = auth()->user();

        $canExport = $ownerUser ? app(\App\Services\Entitlements\EntitlementService::class)->hasModule($ownerUser, 'feature.exports') : false;

    @endphp

    <div class="panel-card p-6 mb-6 report-filter-panel">

        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-end gap-3 report-filter-form">

            <div class="min-w-[180px] report-filter-field">

                <label class="block text-xs font-medium text-slate-500 mb-1">SatÃ½Ã¾ KanalÃ½</label>

                <select name="marketplace_id" class="report-filter-control">

                    <option value="">TÃ¼mÃ¼</option>

                    @foreach($marketplaces as $marketplace)

                        <option value="{{ $marketplace->id }}" @selected(($filters['marketplace_id'] ?? null) == $marketplace->id)>

                            {{ $marketplace->name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="min-w-[260px] report-filter-field">

                <label class="block text-xs font-medium text-slate-500 mb-1">BaÃ¾langÃ½Ã§</label>

                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="report-filter-control">

            </div>

            <div class="min-w-[150px] report-filter-field">

                <label class="block text-xs font-medium text-slate-500 mb-1">BitiÃ¾</label>

                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="report-filter-control">

            </div>

            <div class="min-w-[150px] report-filter-field">

                <label class="block text-xs font-medium text-slate-500 mb-1">HÃ½zlÃ½ SeÃ§im</label>

                <div class="flex flex-wrap gap-2">
                    @foreach($quickRanges as $key => $label)
                        <button type="submit"
                                name="quick_range"
                                value="{{ $key }}"
                                class="report-filter-chip text-xs {{ ($filters['quick_range'] ?? '') === $key ? 'is-active' : '' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

            </div>

            <div class="report-filter-actions">

                <button type="submit" class="report-filter-btn report-filter-btn-primary">Filtrele</button>

                <a href="{{ route('portal.reports.top-products') }}" class="report-filter-btn report-filter-btn-secondary">Temizle</a>

            </div>

            @if($reportExportsEnabled && $canExport)

                <details class="relative">

                    <summary class="report-filter-btn report-filter-btn-secondary list-none cursor-pointer">DÃ½Ã¾a Aktar</summary>

                    <div class="absolute right-0 mt-2 w-44 bg-white border border-slate-200 rounded-lg shadow-lg p-2 z-10">

                        <a href="{{ route('portal.reports.top-products.export', request()->query()) }}" class="block px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-md">CSV</a>

                        <a href="{{ route('portal.reports.top-products.export', request()->query()) }}" class="block px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-md">Excel</a>

                    </div>

                </details>

            @endif

        </form>

    </div>



    <div class="panel-card p-6">

        <div class="flex items-center justify-between mb-4">

            <h3 class="text-sm font-semibold text-slate-700">En Ã‡ok Satan ÃœrÃ¼nler</h3>

            <span class="text-xs text-slate-400">Ãlk 100 Ã¼rÃ¼n listelenir.</span>

        </div>

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead class="text-xs uppercase text-slate-400">

                    <tr>

                        <th class="text-left py-2 pr-4">Stok Kodu</th>

                        <th class="text-left py-2 pr-4">ÃœrÃ¼n AdÃ½</th>

                        <th class="text-right py-2 pr-4">SatÃ½Ã¾ Adedi</th>

                        <th class="text-right py-2">Toplam Tutar</th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse($rows as $row)

                        <tr>

                            <td class="py-3 pr-4 text-slate-600">{{ $row['stock_code'] ?? '-' }}</td>

                            <td class="py-3 pr-4 text-slate-800 font-semibold">{{ $row['name'] }}</td>

                            <td class="py-3 pr-4 text-right text-slate-700">{{ number_format($row['quantity']) }}</td>

                            <td class="py-3 text-right text-slate-700">{{ number_format($row['total'], 2, ',', '.') }} ?</td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="4" class="py-4 text-center text-slate-500">KayÃ½t bulunamadÃ½.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

@endsection







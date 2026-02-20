@extends('layouts.admin')



@section('header')

    Komisyon Raporu

@endsection



@section('content')

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

                <a href="{{ route('portal.reports.commission') }}" class="report-filter-btn report-filter-btn-secondary">Temizle</a>

            </div>

        </form>

    </div>



    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        @foreach($report['cards'] as $card)

            <div class="panel-card p-5">

                <p class="text-xs text-slate-500">{{ $card['name'] }}</p>

                <p class="text-2xl font-semibold text-slate-800">{{ number_format($card['total'], 2, ',', '.') }} ?</p>

            </div>

        @endforeach

        <div class="panel-card p-5 border-dashed border-slate-200">

            <p class="text-xs text-slate-500">Toplam Komisyon</p>

            <p class="text-2xl font-semibold text-slate-800">{{ number_format($report['total'], 2, ',', '.') }} ?</p>

        </div>

    </div>

@endsection







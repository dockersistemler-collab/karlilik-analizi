@extends('layouts.admin')



@section('header')

    Komisyon Raporu

@endsection



@section('content')

    <div class="panel-card p-6 mb-6">

        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-end gap-3">

            <div class="min-w-[180px]">

                <label class="block text-xs font-medium text-slate-500 mb-1">Satýþ Kanalý</label>

                <select name="marketplace_id" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

                    <option value="">Tümü</option>

                    @foreach($marketplaces as $marketplace)

                        <option value="{{ $marketplace->id }}" @selected(($filters['marketplace_id'] ?? null) == $marketplace->id)>

                            {{ $marketplace->name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="min-w-[150px]">

                <label class="block text-xs font-medium text-slate-500 mb-1">Baþlangýç</label>

                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

            </div>

            <div class="min-w-[150px]">

                <label class="block text-xs font-medium text-slate-500 mb-1">Bitiþ</label>

                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

            </div>

            <div class="min-w-[150px]">

                <label class="block text-xs font-medium text-slate-500 mb-1">Hýzlý Seçim</label>

                <select name="quick_range" class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-white">

                    <option value="">Seç</option>

                    @foreach($quickRanges as $key => $label)

                        <option value="{{ $key }}" @selected(($filters['quick_range'] ?? '') === $key)>{{ $label }}</option>

                    @endforeach

                </select>

            </div>

            <div class="flex items-center gap-2 lg:ml-auto">

                <button type="submit" class="btn btn-solid-accent">Filtrele</button>

                <a href="{{ route('portal.reports.commission') }}" class="btn btn-outline">Temizle</a>

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






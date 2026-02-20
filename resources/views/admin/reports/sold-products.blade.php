@extends('layouts.admin')



@section('header')

    SatÃ½lan ÃœrÃ¼nler Raporu

@endsection



@section('content')

    <div class="panel-card p-6 mb-6 report-filter-panel">

        <form method="GET" class="flex flex-wrap lg:flex-nowrap items-end gap-3 report-filter-form">

            <div class="min-w-[160px] report-filter-field">

                <label class="block text-xs font-medium text-slate-500 mb-1">BaÃ¾langÃ½Ã§</label>

                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="report-filter-control">

            </div>

            <div class="min-w-[160px] report-filter-field">

                <label class="block text-xs font-medium text-slate-500 mb-1">BitiÃ¾</label>

                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="report-filter-control">

            </div>

            <div class="report-filter-actions">

                <button type="submit" class="report-filter-btn report-filter-btn-primary">Filtrele</button>

                <a href="{{ route('portal.reports.sold-products') }}" class="report-filter-btn report-filter-btn-secondary">Temizle</a>

                <a href="{{ route('portal.reports.sold-products.print', request()->query()) }}" target="_blank" class="report-filter-btn report-filter-btn-secondary">YazdÃ½r</a>

            </div>

        </form>

    </div>



    <div class="panel-card p-6">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead class="text-xs uppercase text-slate-400">

                    <tr>

                        <th class="text-left py-2 pr-4">Stok Kodu</th>

                        <th class="text-left py-2 pr-4">ÃœrÃ¼n AdÃ½</th>

                        <th class="text-left py-2 pr-4">SeÃ§enek</th>

                        <th class="text-right py-2">SatÃ½Ã¾ Adedi</th>

                    </tr>

                </thead>

                <tbody class="divide-y divide-slate-100">

                    @forelse($rows as $row)

                        <tr>

                            <td class="py-3 pr-4 text-slate-600">{{ $row['stock_code'] ?? '-' }}</td>

                            <td class="py-3 pr-4 text-slate-800 font-semibold">{{ $row['name'] }}</td>

                            <td class="py-3 pr-4 text-slate-600">{{ $row['variant'] ?? '-' }}</td>

                            <td class="py-3 text-right text-slate-700">{{ number_format($row['quantity']) }}</td>

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







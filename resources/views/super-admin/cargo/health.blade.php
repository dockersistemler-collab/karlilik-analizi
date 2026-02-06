@extends('layouts.super-admin')



@section('header')

    Cargo Health

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="space-y-6">

            <div class="grid gap-4 md:grid-cols-2">

                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">

                    <div class="text-xs text-amber-700">Unmapped Carrier (7 gün)</div>

                    <div class="text-2xl font-semibold text-amber-900">{{ $unmappedCount }}</div>

                </div>

                <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">

                    <div class="text-xs text-rose-700">Provider Not Installed (7 gün)</div>

                    <div class="text-2xl font-semibold text-rose-900">{{ $providerMissingCount }}</div>

                </div>

            </div>



            <div>

                <h3 class="text-sm font-semibold text-slate-700 mb-3">Top Carrier (ham isim)</h3>

                <div class="overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="text-left text-slate-500">

                            <tr>

                                <th class="py-2 pr-4">Carrier</th>

                                <th class="py-2 pr-4">Adet</th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($topCarriers as $carrier)

                                <tr>

                                    <td class="py-3 pr-4 font-mono text-xs text-slate-700">{{ $carrier->carrier_name_raw }}</td>

                                    <td class="py-3 pr-4 text-slate-600">{{ $carrier->total }}</td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="2" class="py-6 text-center text-slate-500">Kayıt bulunamadı.</td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

@endsection








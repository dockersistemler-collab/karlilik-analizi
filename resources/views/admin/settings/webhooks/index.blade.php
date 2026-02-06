@extends('layouts.admin')



@section('header')

    Webhooklar

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-5xl mx-auto space-y-6">

            <div class="flex items-center justify-end gap-2">

                <a href="{{ route('portal.docs.einvoice') }}#webhooks" class="btn btn-outline">Dokümantasyon</a>

                @if($hasAccess)

                    <a href="{{ route('portal.webhooks.create') }}" class="btn btn-solid-accent">Yeni Endpoint</a>

                @else

                    <a href="{{ route('portal.modules.upsell', ['code' => \App\Services\Webhooks\WebhookService::MODULE_CODE]) }}" class="btn btn-outline">Satın Al</a>

                @endif

            </div>



            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">

                    {{ session('success') }}

                </div>

            @endif

            @if (session('info'))

                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">

                    {{ session('info') }}

                </div>

            @endif

            @if (session('error'))

                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">

                    {{ session('error') }}

                </div>

            @endif



            @if(session('created_webhook_secret'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">

                    <div class="text-sm font-semibold text-emerald-800">Webhook secret (bir kere gösterilir)</div>

                    <div class="mt-2 font-mono text-xs break-all text-emerald-900">{{ session('created_webhook_secret') }}</div>

                </div>

            @endif



            @if(!$hasAccess)

                <div class="bg-white rounded-xl border border-slate-100 p-6">

                    <div class="text-lg font-semibold text-slate-900">{{ $module?->name ?? 'E-Fatura Webhookları' }}</div>

                    <p class="text-sm text-slate-600 mt-2">

                        Webhook endpoint oluşturmak ve teslimat (delivery) çalıştırmak için bu modülü yıllık olarak satın almanız gerekir.

                    </p>



                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                        <div>

                            <div class="text-sm font-semibold text-slate-800">{{ $module?->name ?? 'E-Fatura Webhookları' }}</div>

                            <div class="text-xs text-slate-500 mt-1">Yıllık kullanım</div>

                        </div>

                        @if($module)

                            <form method="POST" action="{{ route('portal.my-modules.renew', $module) }}" class="flex items-center gap-2">

                                @csrf

                                <input type="hidden" name="period" value="yearly" />

                                <button type="submit" class="btn btn-solid-accent">Yıllık Satın Al</button>

                            </form>

                        @else

                            <div class="text-sm text-rose-600">Modül kataloğu kaydı bulunamadı (feature.einvoice_webhooks).</div>

                        @endif

                    </div>

                </div>

            @endif



            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <div class="text-lg font-semibold text-slate-900">Endpointler</div>



                <div class="mt-4 overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="text-left text-slate-500">

                            <tr>

                                <th class="py-2 pr-4">Ad</th>

                                <th class="py-2 pr-4">URL</th>

                                <th class="py-2 pr-4">Eventler</th>

                                <th class="py-2 pr-4">Durum</th>

                                <th class="py-2 pr-4"></th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($endpoints as $e)

                                <tr>

                                    <td class="py-3 pr-4 font-semibold text-slate-800">{{ $e->name }}</td>

                                    <td class="py-3 pr-4 text-slate-700 font-mono text-xs break-all">{{ $e->url }}</td>

                                    <td class="py-3 pr-4 text-slate-700 text-xs">

                                        {{ is_array($e->events) ? implode(', ', $e->events) : '-' }}

                                    </td>

                                    <td class="py-3 pr-4 text-slate-700">

                                        @php

                                            $metric = $endpointMetrics[$e->id] ?? ['attempts' => 0, 'fails' => 0];

                                            $disabledReason = $e->disabled_reason;

                                        @endphp

                                        @if(!$e->is_active && $e->disabled_at)

                                            <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs text-rose-700">Otomatik devre dışı</span>

                                            <div class="mt-1 text-xs text-slate-500">{{ $e->disabled_at?->format('d.m.Y H:i') }}</div>

                                            @if($disabledReason)

                                                <div class="mt-1 text-xs text-slate-500">{{ $disabledReason }}</div>

                                            @endif

                                            <div class="mt-1 text-xs text-slate-500">1s: {{ $metric['fails'] }}/{{ $metric['attempts'] }}</div>

                                        @elseif($e->is_active)

                                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700">Aktif</span>

                                        @else

                                            <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-700">Pasif</span>

                                        @endif

                                    </td>

                                    <td class="py-3 pr-4 text-right whitespace-nowrap">

                                        @if($hasAccess)

                                            @if(!$e->is_active && $e->disabled_at)

                                                <form method="POST" action="{{ route('portal.webhooks.enable', $e) }}" class="inline">

                                                    @csrf

                                                    <button type="submit" class="btn btn-outline">Tekrar Aktif Et</button>

                                                </form>

                                            @endif

                                            <a href="{{ route('portal.webhooks.deliveries', $e) }}" class="btn btn-outline">Loglar</a>

                                            <a href="{{ route('portal.webhooks.edit', $e) }}" class="btn btn-outline">Düzenle</a>

                                        @else

                                            <span class="text-xs text-slate-400">Erişim yok</span>

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="5" class="py-6 text-center text-slate-500">Endpoint yok.</td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

@endsection





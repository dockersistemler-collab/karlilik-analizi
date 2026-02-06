@extends('layouts.admin')



@section('header', 'Billing Event Detay')



@section('content')

    @if(session('success'))

        <div class="alert alert-success mb-4">{{ session('success') }}</div>

    @endif

    @if(session('error'))

        <div class="alert alert-danger mb-4">{{ session('error') }}</div>

    @endif

    <div class="panel-card p-4 mb-4">

        <div class="flex flex-col gap-4">

            <div class="flex flex-col gap-2">

                <div class="text-xs text-slate-500">Event ID</div>

                <div class="text-slate-800 font-semibold">{{ $event->id }}</div>

            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div>

                    <div class="text-xs text-slate-500">Tenant</div>

                    <div class="text-slate-800 font-semibold">{{ $tenant?->name ?? '-' }}</div>

                    <div class="text-xs text-slate-500">{{ $tenant?->email ?? ('ID: '.$event->tenant_id) }}</div>

                </div>

                <div>

                    <div class="text-xs text-slate-500">Type</div>

                    <div class="text-slate-800 font-semibold">{{ $event->type ?? '-' }}</div>

                </div>

                <div>

                    <div class="text-xs text-slate-500">Status</div>

                    <div class="text-slate-800 font-semibold">{{ $event->status ?? '-' }}</div>

                </div>

                <div>

                    <div class="text-xs text-slate-500">Provider</div>

                    <div class="text-slate-800 font-semibold">{{ $event->provider ?? '-' }}</div>

                </div>

                <div>

                    <div class="text-xs text-slate-500">Amount</div>

                    <div class="text-slate-800 font-semibold">

                        {{ $event->amount !== null ? number_format((float) $event->amount, 2) : '-' }} {{ $event->currency ? strtoupper($event->currency) : '' }}

                    </div>

                </div>

                <div>

                    <div class="text-xs text-slate-500">Created At</div>

                    <div class="text-slate-800 font-semibold">{{ optional($event->created_at)->format('d.m.Y H:i:s') }}</div>

                </div>

            </div>

            <div class="flex flex-wrap items-center gap-3">

                <div>

                    <div class="text-xs text-slate-500">Correlation ID</div>

                    <div class="text-slate-800 font-semibold">{{ $event->correlation_id ?? '-' }}</div>

                </div>

                @if($event->correlation_id)

                    <a href="{{ route('super-admin.observability.billing-events.index', ['correlation_id' => $event->correlation_id]) }}" class="btn btn-outline">

                        Flow

                    </a>

                    <button type="button" class="btn btn-outline-accent" data-copy="{{ $event->correlation_id }}">Kopyala</button>

                @endif

            </div>

        </div>

    </div>



    <div class="panel-card p-4 mb-4">

        <div class="text-sm font-semibold text-slate-700 mb-3">Actions</div>

        <div class="flex flex-wrap items-center gap-3">

            @if($canReprocessWebhook)

                <form method="POST" action="{{ route('super-admin.observability.billing-events.reprocess-webhook', $event) }}" onsubmit="return confirm('Emin misiniz? Bu islem ilgili olayi tekrar calistiracaktir.')">

                    @csrf

                    <button type="submit" class="btn btn-outline" {{ $webhookQueued ? 'disabled' : '' }}>

                        {{ $webhookQueued ? 'Isleniyor...' : "Webhook'u tekrar isle" }}

                    </button>

                </form>

            @endif

            @if($canRetryJob)

                <form method="POST" action="{{ route('super-admin.observability.billing-events.retry-job', $event) }}" onsubmit="return confirm('Emin misiniz? Bu islem ilgili olayi tekrar calistiracaktir.')">

                    @csrf

                    <button type="submit" class="btn btn-outline" {{ $jobQueued ? 'disabled' : '' }}>

                        {{ $jobQueued ? 'Isleniyor...' : "Job'u tekrar calistir" }}

                    </button>

                </form>

            @endif

            @if(!$canReprocessWebhook && !$canRetryJob)

                <div class="text-sm text-slate-500">Bu event icin tekrar isleme desteklenmiyor.</div>

            @endif

        </div>

    </div>



    <div class="panel-card p-4 mb-4">

        <div class="flex items-center justify-between mb-3">

            <div class="text-sm font-semibold text-slate-700">Son Aksiyonlar</div>

            <span class="text-xs text-slate-500">Son 10 kayit</span>

        </div>

        @if($recentActions->isEmpty())

            <div class="text-sm text-slate-500">Aksiyon bulunamadi.</div>

        @else

            <div class="overflow-auto">

                <table class="min-w-full text-sm">

                    <thead>

                        <tr class="text-left text-xs uppercase text-slate-400">

                            <th class="py-2">Tarih</th>

                            <th class="py-2">Tip</th>

                            <th class="py-2">Durum</th>

                            <th class="py-2">Talep Eden</th>

                            <th class="py-2">Hata</th>

                        </tr>

                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @foreach($recentActions as $action)

                            @php

                                $statusLabel = $action->status ?? '-';

                                $badgeClass = match ($statusLabel) {

                                    'succeeded', 'success' => 'badge badge-success',

                                    'failed', 'error' => 'badge badge-danger',

                                    'queued', 'pending' => 'badge badge-warning',

                                    'ignored' => 'badge badge-muted',

                                    default => 'badge badge-muted',

                                };

                            @endphp

                            <tr>

                                <td class="py-2 text-slate-600">{{ optional($action->created_at)->format('d.m.Y H:i') }}</td>

                                <td class="py-2 text-slate-700">{{ $action->action_type }}</td>

                                <td class="py-2">

                                    <span class="{{ $badgeClass }}">

                                        {{ $statusLabel }}

                                    </span>

                                </td>

                                <td class="py-2 text-slate-600">{{ $action->requestedBy?->name ?? ('ID: '.$action->requested_by_admin_id) }}</td>

                                <td class="py-2 text-slate-500">{{ $action->error_message ?? '-' }}</td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @endif

    </div>



    <div class="panel-card p-4 mb-4">

        <div class="text-sm font-semibold text-slate-700 mb-3">Payload</div>

        <details class="rounded border border-slate-200 bg-slate-50 p-3" open>

            <summary class="cursor-pointer text-sm text-slate-600">JSON Goruntule</summary>

            <pre class="mt-3 text-xs text-slate-700 overflow-auto">{{ json_encode($event->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

        </details>

    </div>



    <div class="panel-card p-4">

        <div class="flex items-center justify-between mb-3">

            <div class="text-sm font-semibold text-slate-700">Related events by correlation_id</div>

            <span class="text-xs text-slate-500">Son 50 kayit</span>

        </div>

        @if($relatedEvents->isEmpty())

            <div class="text-sm text-slate-500">Iliskili event bulunamadi.</div>

        @else

            <ol class="space-y-3">

                @foreach($relatedEvents as $related)

                    <li class="flex items-start gap-3">

                        <div class="mt-1 h-2 w-2 rounded-full bg-slate-400"></div>

                        <div class="flex-1 rounded border border-slate-200 bg-white p-3">

                            <div class="flex flex-wrap items-center gap-2 text-sm">

                                <span class="font-semibold text-slate-800">{{ $related->type ?? '-' }}</span>

                                <span class="text-slate-500">{{ $related->status ?? '-' }}</span>

                                <span class="text-slate-400">·</span>

                                <span class="text-slate-600">{{ optional($related->created_at)->format('d.m.Y H:i') }}</span>

                            </div>

                            <div class="mt-2 text-xs text-slate-500">

                                Provider: {{ $related->provider ?? '-' }}

                            </div>

                            <div class="mt-2">

                                <a class="btn btn-outline-accent" href="{{ route('super-admin.observability.billing-events.show', $related) }}">

                                    Detay

                                </a>

                            </div>

                        </div>

                    </li>

                @endforeach

            </ol>

        @endif

    </div>



    <script>

        document.querySelectorAll('[data-copy]').forEach((button) => {

            button.addEventListener('click', () => {

                const value = button.getAttribute('data-copy') || '';

                if (!value) return;

                if (navigator.clipboard && navigator.clipboard.writeText) {

                    navigator.clipboard.writeText(value);

                }

            });

        });

    </script>

@endsection





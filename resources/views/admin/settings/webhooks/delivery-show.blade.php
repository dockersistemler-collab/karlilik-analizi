@extends('layouts.admin')



@section('header')

    Webhook Teslimat Detayı

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-5xl mx-auto space-y-6">

            <div class="flex items-center justify-between">

                <div>

                    <div class="text-lg font-semibold text-slate-900">Delivery #{{ $delivery->id }}</div>

                    <div class="text-xs text-slate-500">{{ $delivery->event }}</div>

                </div>

                @if($delivery->endpoint)

                    <a href="{{ route('portal.webhooks.deliveries', $delivery->endpoint) }}" class="btn btn-outline">Geri</a>

                @else

                    <a href="{{ route('portal.webhooks.index') }}" class="btn btn-outline">Geri</a>

                @endif

            </div>



            <div class="bg-white rounded-xl border border-slate-100 p-6 space-y-4">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">

                    <div>

                        <div class="text-slate-500">Durum</div>

                        <div class="font-semibold text-slate-800">{{ $delivery->status }}</div>

                    </div>

                    <div>

                        <div class="text-slate-500">Deneme</div>

                        <div class="font-semibold text-slate-800">{{ $delivery->attempt }}</div>

                    </div>

                    <div>

                        <div class="text-slate-500">HTTP</div>

                        <div class="font-semibold text-slate-800">{{ $delivery->http_status ?? '-' }}</div>

                    </div>

                    <div>

                        <div class="text-slate-500">Süre</div>

                        <div class="font-semibold text-slate-800">{{ $delivery->duration_ms ? ($delivery->duration_ms.'ms') : '-' }}</div>

                    </div>

                    <div>

                        <div class="text-slate-500">Request ID</div>

                        <div class="font-mono text-xs text-slate-800 break-all">{{ $delivery->request_id ?? '-' }}</div>

                    </div>

                    <div>

                        <div class="text-slate-500">Next Retry</div>

                        <div class="font-semibold text-slate-800">{{ $delivery->next_retry_at?->toISOString() ?? '-' }}</div>

                    </div>

                    <div>

                        <div class="text-slate-500">Signature</div>

                        <div class="font-mono text-xs text-slate-800 break-all">

                            @if($delivery->signature_timestamp && $delivery->signature_v1)

                                t={{ $delivery->signature_timestamp }},v1={{ $delivery->signature_v1 }}

                            @else

                                -

                            @endif

                        </div>

                    </div>

                </div>



                @if($delivery->last_error)

                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">

                        <div class="font-semibold">Hata</div>

                        <div class="mt-1 whitespace-pre-wrap">{{ $delivery->last_error }}</div>

                    </div>

                @endif



                <div>

                    <div class="text-sm font-semibold text-slate-900">Request Headers</div>

                    <pre class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-4 overflow-x-auto text-xs text-slate-800">{{ json_encode($delivery->request_headers_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>

                </div>

                <div>

                    <div class="text-sm font-semibold text-slate-900">Request Body (Log)</div>

                    <pre class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-4 overflow-x-auto text-xs text-slate-800">{{ $delivery->request_body ?? '-' }}</pre>

                </div>



                <div>

                    <div class="text-sm font-semibold text-slate-900">Payload</div>

                    @php

                        $payloadForLog = $delivery->payload_log_json   $delivery->payload_json;

                    @endphp

                    <pre class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-4 overflow-x-auto text-xs text-slate-800">{{ json_encode($payloadForLog, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>

                </div>



                <div>

                    <div class="text-sm font-semibold text-slate-900">Response</div>

                    <pre class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-4 overflow-x-auto text-xs text-slate-800">{{ $delivery->response_body ?? '-' }}</pre>

                </div>



                <div>

                    <div class="text-sm font-semibold text-slate-900">Response Headers</div>

                    <pre class="mt-2 rounded-xl border border-slate-200 bg-slate-50 p-4 overflow-x-auto text-xs text-slate-800">{{ json_encode($delivery->response_headers_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>

                </div>

            </div>

        </div>

    </div>

@endsection





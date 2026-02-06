@extends('layouts.super-admin')



@section('header', 'E-Posta Kayitlari')



@section('content')

    <div class="panel-card p-4 mb-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">

            <h3 class="text-sm font-semibold text-slate-800">Filtreler</h3>

        </div>

        <form method="GET" action="{{ route('super-admin.mail-logs.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Key</label>

                <input type="text" name="key" value="{{ $key }}" placeholder="subscription.started">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Kategori</label>

                <input type="text" name="category" value="{{ $category }}" placeholder="billing">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Status</label>

                <select name="status">

                    <option value="">Tumu</option>

                    <option value="success" {{ $status === 'success' ? 'selected' : '' }}>success</option>

                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>failed</option>

                    <option value="blocked" {{ $status === 'blocked' ? 'selected' : '' }}>blocked</option>

                    <option value="deduped" {{ $status === 'deduped' ? 'selected' : '' }}>deduped</option>

                    <option value="skipped" {{ $status === 'skipped' ? 'selected' : '' }}>skipped</option>

                </select>

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">E-posta</label>

                <input type="text" name="email" value="{{ $email }}" placeholder="email">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Tarih Baslangic</label>

                <input type="date" name="date_from" value="{{ $dateFrom }}">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Tarih Bitis</label>

                <input type="date" name="date_to" value="{{ $dateTo }}">

            </div>

            <div>

                <button type="submit" class="btn btn-solid-accent">Uygula</button>

            </div>

            <div>

                <a href="{{ route('super-admin.mail-logs.index') }}" class="text-slate-500 hover:text-slate-700">Sıfırla</a>

            </div>

            <div>

                <a class="btn btn-outline-accent" href="{{ route('super-admin.mail-logs.export', request()->query()) }}">CSV Çıktı</a>

            </div>

        </form>

    </div>



    <div class="panel-card p-0">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr>

                        <th class="text-left">Zaman</th>

                        <th class="text-left">Key</th>

                        <th class="text-left">Kategori</th>

                        <th class="text-left">Kullanici</th>

                        <th class="text-left">Status</th>

                        <th class="text-left">Hata</th>

                        <th class="text-left">Meta Ozet</th>

                        <th class="text-left">Detay</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($logs as $log)

                        @php

                            $meta = is_array($log->metadata_json ?? null) ? $log->metadata_json : [];

                            $metaSummary = [];

                            foreach (['invoice_id', 'order_id', 'subscription_id'] as $metaKey) {

                                if (array_key_exists($metaKey, $meta) && $meta[$metaKey] !== null && $meta[$metaKey] !== '') {

                                    $metaSummary[] = $metaKey.': '.$meta[$metaKey];

                                }

                            }

                            $metaSummaryText = empty($metaSummary) ? '-' : implode(', ', $metaSummary);

                            $status = $log->status ?? '-';

                            $badgeClass = match ($status) {

                                'success' => 'badge badge-success',

                                'failed' => 'badge badge-danger',

                                'blocked' => 'badge badge-warning',

                                'deduped' => 'badge badge-muted',

                                'skipped' => 'badge badge-muted',

                                default => 'badge badge-muted',

                            };

                        @endphp

                        <tr>

                            <td class="text-slate-700">

                                <div>{{ optional($log->created_at)->format('d.m.Y H:i') }}</div>

                                <div class="text-xs text-slate-500">Sent: {{ optional($log->sent_at)->format('d.m.Y H:i') ?? '-' }}</div>

                            </td>

                            <td class="text-slate-700">{{ $log->key }}</td>

                            <td class="text-slate-700">{{ $log->category ?? '-' }}</td>

                            <td>

                                <div class="font-semibold text-slate-800">{{ $log->user_name ?? '-' }}</div>

                                <div class="text-xs text-slate-500">{{ $log->user_email ?? '-' }}</div>

                            </td>

                            <td>

                                <span class="{{ $badgeClass }}">{{ $status }}</span>

                            </td>

                            <td class="text-slate-700">{{ \Illuminate\Support\Str::limit($log->error ?? '-', 40) }}</td>

                            <td class="text-slate-700">

                                {{ \Illuminate\Support\Str::limit($metaSummaryText, 60) }}

                            </td>

                            <td>

                                <a class="btn btn-outline-accent" href="{{ route('super-admin.mail-logs.show', $log) }}">

                                    Detay

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="8" class="text-center text-slate-500 py-6">Kayit bulunamadi.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <div class="mt-4">

            {{ $logs->links() }}

        </div>

    </div>

@endsection








@extends('layouts.super-admin')

@section('header')
    Hakediş Yönetimi
@endsection

@section('content')
    <div class="panel-card p-6">
        <div class="mb-5">
            <div class="text-lg font-semibold text-slate-900">Hakediş Kontrol Merkezi Yetkileri</div>
            <p class="text-sm text-slate-500 mt-1">
                Bu ekrandan müşteri panelindeki Hakediş alanını açıp kapatabilirsiniz.
            </p>
            @unless($moduleDefined)
                <p class="text-xs text-amber-700 mt-2">
                    `feature.hakedis` modülü otomatik tanımlı değildi, ilk açma işleminde otomatik oluşturulur.
                </p>
            @endunless
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">Müşteri</th>
                        <th class="py-2 pr-4">E-posta</th>
                        <th class="py-2 pr-4">Tenant</th>
                        <th class="py-2 pr-4">Müşteri Paneli</th>
                        <th class="py-2 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $client)
                        @php
                            $tenantId = (int) ($client->tenant_id ?: $client->id);
                            $enabled = isset($enabledTenants[$tenantId]);
                        @endphp
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-semibold text-slate-900">{{ $client->name }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $client->email }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $tenantId }}</td>
                            <td class="py-3 pr-4">
                                @if($enabled)
                                    <span class="inline-flex px-2 py-1 rounded-md text-xs bg-emerald-50 text-emerald-700 border border-emerald-200">Açık</span>
                                @else
                                    <span class="inline-flex px-2 py-1 rounded-md text-xs bg-slate-50 text-slate-700 border border-slate-200">Kapalı</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-right">
                                <form method="POST" action="{{ route('super-admin.settlements.visibility', $client) }}">
                                    @csrf
                                    <input type="hidden" name="visible" value="{{ $enabled ? '0' : '1' }}">
                                    <button type="submit" class="btn btn-outline">
                                        {{ $enabled ? 'Kapat' : 'Aç' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-500">Müşteri bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $clients->links() }}
        </div>
    </div>
@endsection


@extends('layouts.admin')



@section('header')

    API Erişimi

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-4xl mx-auto space-y-6">

            <div class="flex items-center justify-end gap-2">

                @if($hasAccess)

                    <a href="{{ route('portal.settings.api.logs') }}" class="btn btn-outline">Loglar</a>

                @endif

                <a href="{{ route('portal.docs.einvoice') }}" class="btn btn-outline">Dokümantasyon</a>

            </div>



            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">

                    {{ session('success') }}

                </div>

            @endif

            @if (session('error'))

                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">

                    {{ session('error') }}

                </div>

            @endif

            @if (session('info'))

                <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">

                    {{ session('info') }}

                </div>

            @endif

            @if ($errors->any())

                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">

                    <div class="font-semibold">Lütfen bilgileri kontrol edin.</div>

                    <ul class="mt-2 list-disc list-inside space-y-1">

                        @foreach ($errors->all() as $e)

                            <li>{{ $e }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif



            @if(!$hasAccess)

                <div class="bg-white rounded-xl border border-slate-100 p-6">

                    <div class="text-lg font-semibold text-slate-900">E-Fatura API Erişimi</div>

                    <p class="text-sm text-slate-600 mt-2">

                        E-Fatura verilerinize API üzerinden erişmek için bu modülü yıllık olarak satın alabilirsiniz.

                    </p>



                    <div class="mt-3">

                        <a href="{{ route('portal.docs.einvoice') }}" class="text-sm text-blue-700 hover:text-blue-900 underline">

                            Dokümantasyonu Gör

                        </a>

                    </div>



                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

                        <div>

                            <div class="text-sm font-semibold text-slate-800">{{ $apiModule?->name ?? 'E-Fatura API Erişimi' }}</div>

                            <div class="text-xs text-slate-500 mt-1">Yıllık kullanım</div>

                        </div>

                        @if($apiModule)

                            <form method="POST" action="{{ route('portal.my-modules.renew', $apiModule) }}" class="flex items-center gap-2">

                                @csrf

                                <input type="hidden" name="period" value="yearly" />

                                <button type="submit" class="btn btn-solid-accent">Yıllık Satın Al</button>

                            </form>

                        @else

                            <div class="text-sm text-rose-600">Modül kataloğu kaydı bulunamadı (feature.einvoice_api).</div>

                        @endif

                    </div>



                    <div class="mt-4 text-xs text-slate-500">

                        Not: Satın alma sonrası bu sayfadan token oluşturabilirsiniz.

                    </div>

                </div>

            @else

            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <div class="text-lg font-semibold text-slate-900">Yeni Token</div>

                <div class="mt-2">

                    <a href="{{ route('portal.docs.einvoice') }}" class="text-sm text-blue-700 hover:text-blue-900 underline">

                        Dokümantasyon

                    </a>

                </div>



                @if(session('created_token'))

                    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4">

                        <div class="text-sm font-semibold text-emerald-800">Token oluşturuldu (bir kere gösterilir)</div>

                        <div class="mt-2 font-mono text-xs break-all text-emerald-900">{{ session('created_token') }}</div>

                    </div>

                @endif



                <form method="POST" action="{{ route('portal.settings.api.tokens.store') }}" class="mt-4 space-y-4">

                    @csrf

                    <div>

                        <label class="block text-sm font-medium text-slate-700">Token Adı</label>

                        <input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2" required />

                        @error('name')

                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>

                        @enderror

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">Token Süresi</label>

                        <select name="expires_in_days" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2">

                            @php

                                $expiresValue = (int) old('expires_in_days', 90);

                            @endphp

                            <option value="30" @selected($expiresValue === 30)>30 gün</option>

                            <option value="90" @selected($expiresValue === 90)>90 gün (önerilen)</option>

                            <option value="180" @selected($expiresValue === 180)>180 gün</option>

                            <option value="365" @selected($expiresValue === 365)>365 gün</option>

                        </select>

                        @error('expires_in_days')

                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>

                        @enderror

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-slate-700">IP Kısıtı (Allowlist)</label>

                        <textarea name="ip_allowlist" rows="4" class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-xs" placeholder="1.2.3.4&#10;5.6.7.0/24">{{ old('ip_allowlist') }}</textarea>

                        <div class="mt-2 text-xs text-slate-500">

                            Satır satır IP veya CIDR (IPv4/IPv6). Maksimum 20 kayıt.

                        </div>

                        @error('ip_allowlist')

                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>

                        @enderror

                    </div>

                    <div>

                        <div class="text-sm font-medium text-slate-700">Yetkiler</div>

                        <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">

                            @foreach($abilities as $key => $label)

                                <label class="inline-flex items-center gap-2 text-sm text-slate-700">

                                    <input type="checkbox" name="abilities[]" value="{{ $key }}" @checked(in_array($key, old('abilities', ['einvoices:read']), true)) />

                                    <span>{{ $label }} ({{ $key }})</span>

                                </label>

                            @endforeach

                        </div>

                        @error('abilities')

                            <div class="mt-2 text-sm text-red-600">{{ $message }}</div>

                        @enderror

                    </div>

                    <button type="submit" class="btn btn-solid-accent">Token Oluştur</button>

                </form>

            </div>

            @endif



            @if($hasAccess)

                <div class="bg-white rounded-xl border border-slate-100 p-6">

                    <div class="text-lg font-semibold text-slate-900">Mevcut Tokenlar</div>

                    <div class="mt-4 overflow-x-auto">

                        <table class="min-w-full text-sm">

                            <thead class="text-left text-slate-500">

                                <tr>

                                    <th class="py-2 pr-4">Ad</th>

                                    <th class="py-2 pr-4">Yetkiler</th>

                                    <th class="py-2 pr-4">Süre</th>

                                    <th class="py-2 pr-4">IP Kısıtı</th>

                                    <th class="py-2 pr-4">Son Kullanım</th>

                                    <th class="py-2 pr-4"></th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                @forelse($tokens as $t)

                                    <tr>

                                        <td class="py-3 pr-4 font-semibold text-slate-800">{{ $t->name }}</td>

                                        <td class="py-3 pr-4 text-slate-700 font-mono text-xs">

                                            {{ is_array($t->abilities) ? implode(', ', $t->abilities) : (string) $t->abilities }}

                                        </td>

                                        <td class="py-3 pr-4 text-slate-700 whitespace-nowrap">

                                            @php

                                                $expiresAt = $t->expires_at ? \Illuminate\Support\Carbon::parse($t->expires_at) : null;

                                                $daysLeft = $expiresAt ? now()->diffInDays($expiresAt, false) : null;

                                            @endphp

                                            @if($expiresAt)

                                                @if($daysLeft !== null && $daysLeft < 0)

                                                    <span class="inline-flex items-center rounded-full border border-rose-200 bg-rose-50 px-2 py-0.5 text-xs text-rose-700">

                                                        Süresi doldu

                                                    </span>

                                                @else

                                                    <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-700">

                                                        {{ $daysLeft ?? '-' }} gün

                                                    </span>

                                                @endif

                                            @else

                                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-700">

                                                    -

                                                </span>

                                            @endif

                                        </td>

                                        <td class="py-3 pr-4 text-slate-700 whitespace-nowrap">

                                            @php

                                                $allow = $t->ip_allowlist_json ?? null;

                                                $allowDecoded = is_array($allow) ? $allow : (is_string($allow) ? json_decode($allow, true) : null);

                                                $allowCount = is_array($allowDecoded) ? count(array_filter($allowDecoded)) : 0;

                                            @endphp

                                            @if($allowCount > 0)

                                                <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-2 py-0.5 text-xs text-sky-700">

                                                    Var ({{ $allowCount }})

                                                </span>

                                            @else

                                                <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-2 py-0.5 text-xs text-slate-700">

                                                    Yok

                                                </span>

                                            @endif

                                        </td>

                                        <td class="py-3 pr-4 text-slate-700">{{ $t->last_used_at?->format('d.m.Y H:i') ?? '-' }}</td>

                                        <td class="py-3 pr-4 text-right">

                                            <form method="POST" action="{{ route('portal.settings.api.tokens.destroy', $t->id) }}">

                                                @csrf

                                                @method('DELETE')

                                                <button type="submit" class="btn btn-outline">İptal Et</button>

                                            </form>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="6" class="py-6 text-center text-slate-500">Token yok.</td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            @endif

        </div>

    </div>

@endsection





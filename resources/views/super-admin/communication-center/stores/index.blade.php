@extends('layouts.super-admin')

@section('header')
    İletişim Mağazaları
@endsection

@section('content')
    <div class="panel-card p-6">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Kullanıcı</th>
                    <th class="py-2">Pazaryeri</th>
                    <th class="py-2">Mağaza</th>
                    <th class="py-2">Harici Kimlik</th>
                    <th class="py-2">Aktif</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($stores as $store)
                    <tr class="border-b">
                        <td class="py-2">{{ $store->user?->name }}</td>
                        <td class="py-2">{{ $store->marketplace?->name }}</td>
                        <td class="py-2">{{ $store->store_name }}</td>
                        <td class="py-2">{{ $store->store_external_id }}</td>
                        <td class="py-2">{{ $store->is_active ? 'Evet' : 'Hayır' }}</td>
                        <td class="py-2 text-right">
                            <details>
                                <summary class="btn btn-outline">Düzenle</summary>
                                <form method="POST" action="{{ route('super-admin.communication-center.stores.update', $store) }}" class="mt-3 space-y-2 max-w-md">
                                    @csrf
                                    <input name="store_name" class="w-full" value="{{ $store->store_name }}" required placeholder="Mağaza adı">
                                    <input name="store_external_id" class="w-full" value="{{ $store->store_external_id }}" placeholder="Harici kimlik">
                                    <input name="base_url" class="w-full" value="{{ data_get($store->credentials, 'base_url') }}" placeholder="Base URL (https://...)">
                                    <select name="auth_type" class="w-full">
                                        @php($authType = (string) data_get($store->credentials, 'auth_type', 'auto'))
                                        <option value="auto" @selected($authType === 'auto')>Otomatik (Token/Basic)</option>
                                        <option value="bearer" @selected($authType === 'bearer')>Bearer Token</option>
                                        <option value="basic" @selected($authType === 'basic')>Basic Auth</option>
                                        <option value="header" @selected($authType === 'header')>API Key Header</option>
                                    </select>
                                    <input name="api_key" class="w-full" placeholder="API Anahtarı">
                                    <input name="api_secret" class="w-full" placeholder="API Secret">
                                    <input name="access_token" class="w-full" placeholder="Access Token (Bearer)">
                                    <input name="threads_endpoint" class="w-full" value="{{ data_get($store->credentials, 'threads_endpoint') }}" placeholder="/communication/threads">
                                    <input name="messages_endpoint" class="w-full" value="{{ data_get($store->credentials, 'messages_endpoint') }}" placeholder="/communication/threads/{external_thread_id}/messages">
                                    <input name="send_reply_endpoint" class="w-full" value="{{ data_get($store->credentials, 'send_reply_endpoint') }}" placeholder="/communication/threads/{external_thread_id}/reply">
                                    <select name="send_reply_method" class="w-full">
                                        @php($replyMethod = strtoupper((string) data_get($store->credentials, 'send_reply_method', 'POST')))
                                        <option value="POST" @selected($replyMethod === 'POST')>POST</option>
                                        <option value="PUT" @selected($replyMethod === 'PUT')>PUT</option>
                                        <option value="PATCH" @selected($replyMethod === 'PATCH')>PATCH</option>
                                    </select>
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" name="is_active" value="1" @checked($store->is_active)>
                                        <span>Aktif</span>
                                    </label>
                                    <button class="btn btn-solid-accent">Kaydet</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $stores->links() }}</div>
    </div>
@endsection

@extends('layouts.admin')

@section('title', $marketplace->name)
@section('page-title', 'Pazaryeri Detay: ' . $marketplace->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.marketplaces.edit', $marketplace) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
        <i class="fas fa-edit mr-2"></i> Düzenle
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Genel Bilgiler</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">Pazaryeri Adı</dt>
                <dd class="font-semibold">{{ $marketplace->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Kod</dt>
                <dd><code class="bg-gray-100 px-2 py-1 rounded">{{ $marketplace->code }}</code></dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">API URL</dt>
                <dd class="text-sm break-all">{{ $marketplace->api_url }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Durum</dt>
                <dd>
                    <span class="px-2 py-1 text-xs rounded {{ $marketplace->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $marketplace->is_active ? 'Aktif' : 'Pasif' }}
                    </span>
                </dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">API Bilgileri</h3>
        @if($marketplace->credential)
            <dl class="space-y-2">
                <div>
                    <dt class="text-sm text-gray-500">API Key</dt>
                    <dd class="font-mono text-xs">{{ $marketplace->credential->api_key ? str_repeat('*', 20) : '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Supplier ID</dt>
                    <dd>{{ $marketplace->credential->supplier_id ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Store ID</dt>
                    <dd>{{ $marketplace->credential->store_id ?? '-' }}</dd>
                </div>
            </dl>
        @else
            <p class="text-gray-500">API bilgileri tanımlanmamış</p>
        @endif
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">İstatistikler</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">Toplam Ürün</dt>
                <dd class="text-2xl font-bold">{{ $marketplace->products->count() }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Toplam Sipariş</dt>
                <dd class="text-2xl font-bold">{{ $marketplace->orders->count() }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
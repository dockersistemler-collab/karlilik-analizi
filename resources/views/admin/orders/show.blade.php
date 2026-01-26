@extends('layouts.admin')

@section('header')
    Sipariş Detay
@endsection

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Sipariş Bilgileri</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">Sipariş No</dt>
                <dd><code class="bg-gray-100 px-2 py-1 rounded">{{ $order->marketplace_order_id }}</code></dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Pazaryeri</dt>
                <dd class="font-semibold">{{ $order->marketplace->name }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Sipariş Tarihi</dt>
                <dd>{{ $order->order_date->format('d.m.Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Durum</dt>
                <dd>
                    <span class="px-2 py-1 text-xs rounded 
                        @if($order->status == 'pending') bg-yellow-100 text-yellow-800
                        @elseif($order->status == 'approved') bg-blue-100 text-blue-800
                        @elseif($order->status == 'shipped') bg-purple-100 text-purple-800
                        @elseif($order->status == 'delivered') bg-green-100 text-green-800
                        @elseif($order->status == 'cancelled') bg-red-100 text-red-800
                        @elseif($order->status == 'returned') bg-orange-100 text-orange-800
                        @endif">
                        {{ ucfirst($order->status) }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Toplam Tutar</dt>
                <dd class="text-2xl font-bold text-green-600">{{ number_format($order->total_amount, 2) }} {{ $order->currency }}</dd>
            </div>
            @if($order->commission_amount)
            <div>
                <dt class="text-sm text-gray-500">Komisyon</dt>
                <dd>{{ number_format($order->commission_amount, 2) }} {{ $order->currency }}</dd>
            </div>
            @endif
            @if($order->net_amount)
            <div>
                <dt class="text-sm text-gray-500">Net Tutar</dt>
                <dd class="font-semibold">{{ number_format($order->net_amount, 2) }} {{ $order->currency }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Müşteri Bilgileri</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">Ad Soyad</dt>
                <dd class="font-semibold">{{ $order->customer_name }}</dd>
            </div>
            @if($order->customer_email)
            <div>
                <dt class="text-sm text-gray-500">E-posta</dt>
                <dd>{{ $order->customer_email }}</dd>
            </div>
            @endif
            @if($order->customer_phone)
            <div>
                <dt class="text-sm text-gray-500">Telefon</dt>
                <dd>{{ $order->customer_phone }}</dd>
            </div>
            @endif
            @if($order->shipping_address)
            <div>
                <dt class="text-sm text-gray-500">Teslimat Adresi</dt>
                <dd class="text-sm">{{ $order->shipping_address }}</dd>
            </div>
            @endif
        </dl>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Kargo Bilgileri</h3>
        <dl class="space-y-2">
            <div>
                <dt class="text-sm text-gray-500">Kargo Firması</dt>
                <dd>{{ $order->cargo_company ?? 'Henüz belirlenmedi' }}</dd>
            </div>
            <div>
                <dt class="text-sm text-gray-500">Takip No</dt>
                <dd>{{ $order->tracking_number ?? 'Henüz belirlenmedi' }}</dd>
            </div>
            @if($order->shipped_at)
            <div>
                <dt class="text-sm text-gray-500">Kargoya Verilme Tarihi</dt>
                <dd>{{ $order->shipped_at->format('d.m.Y H:i') }}</dd>
            </div>
            @endif
            @if($order->delivered_at)
            <div>
                <dt class="text-sm text-gray-500">Teslim Tarihi</dt>
                <dd>{{ $order->delivered_at->format('d.m.Y H:i') }}</dd>
            </div>
            @endif
        </dl>

        <form action="{{ route('admin.orders.update', $order) }}" method="POST" class="mt-4 pt-4 border-t">
            @csrf
            @method('PUT')
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kargo Firması</label>
                    <input type="text" name="cargo_company" value="{{ old('cargo_company', $order->cargo_company) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Takip No</label>
                    <input type="text" name="tracking_number" value="{{ old('tracking_number', $order->tracking_number) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Durum</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Beklemede</option>
                        <option value="approved" {{ $order->status == 'approved' ? 'selected' : '' }}>Onaylandı</option>
                        <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Kargoda</option>
                        <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Teslim Edildi</option>
                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>İptal</option>
                        <option value="returned" {{ $order->status == 'returned' ? 'selected' : '' }}>İade</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Not</label>
                    <textarea name="note" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm" placeholder="Durum güncelleme notu (opsiyonel)">{{ old('note') }}</textarea>
                </div>
                <button type="submit" class="btn btn-solid-accent w-full">
                    Güncelle
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Durum Geçmişi</h3>
        <div class="space-y-3">
            @forelse($order->statusLogs as $log)
                <div class="border-b pb-2">
                    <div class="text-sm font-semibold text-gray-900">
                        {{ ucfirst($log->old_status ?? 'başlangıç') }} → {{ ucfirst($log->new_status) }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $log->created_at->format('d.m.Y H:i') }}
                        @if($log->user)
                            • {{ $log->user->name }}
                        @endif
                    </div>
                    @if($log->note)
                        <div class="text-sm text-gray-600 mt-1">{{ $log->note }}</div>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">Durum geçmişi bulunmuyor.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold mb-4">Sipariş Kalemleri</h3>
        @if($order->items && is_array($order->items))
            <div class="space-y-2">
                @foreach($order->items as $item)
                <div class="border-b pb-2">
                    <div class="font-semibold text-sm">{{ $item['name'] ?? 'Ürün Adı' }}</div>
                    <div class="text-xs text-gray-500">
                        Adet: {{ $item['quantity'] ?? 0 }} |
                        Fiyat: {{ number_format($item['price'] ?? 0, 2) }} {{ $order->currency }}
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">Sipariş kalemleri bilgisi mevcut değil</p>
        @endif
    </div>
</div>
@endsection

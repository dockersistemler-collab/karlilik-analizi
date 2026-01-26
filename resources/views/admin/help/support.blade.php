@extends('layouts.admin')

@section('header')
    Destek Merkezi
@endsection

@section('content')
    <div class="panel-card p-6 mb-6">
        <p class="text-sm text-slate-600">
            Sık sorulan sorular ve destek seçenekleri.
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
            <a href="{{ route('admin.tickets.create') }}" class="btn btn-outline-accent">
                Destek Talebi Oluştur
            </a>
        </div>
    </div>

    <div class="panel-card p-6">
        <h3 class="text-sm font-semibold text-slate-800">Sık Sorulanlar</h3>
        <div class="mt-3 space-y-3 text-sm text-slate-600">
            <div class="border-b border-slate-100 pb-3">
                <p class="font-medium text-slate-800">Mağaza bağlantısı kurulamıyor, ne yapmalıyım?</p>
                <p class="mt-1">API anahtarlarınızı ve mağaza ID bilgilerinizi kontrol edin.</p>
            </div>
            <div class="border-b border-slate-100 pb-3">
                <p class="font-medium text-slate-800">Ürünlerim neden görünmüyor?</p>
                <p class="mt-1">Entegrasyon aktif olduğundan emin olun ve son senkronu çalıştırın.</p>
            </div>
            <div>
                <p class="font-medium text-slate-800">Fatura adresimi nasıl güncellerim?</p>
                <p class="mt-1">Panel → Genel Ayarlar sayfasından güncelleyebilirsiniz.</p>
            </div>
        </div>
    </div>
@endsection

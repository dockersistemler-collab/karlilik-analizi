@extends('layouts.admin')

@section('header')
    Destek Talebi Oluştur
@endsection

@section('content')
    <div class="panel-card p-6 max-w-3xl">
        @if($errors->any())
            <div class="panel-card p-4 mb-6 border-red-200 text-red-700">
                <ul class="list-disc list-inside text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flex items-center justify-between gap-3 mb-6">
            <div>
                <h3 class="text-lg font-semibold text-slate-900">Yeni Destek Talebi</h3>
                <p class="text-sm text-slate-500 mt-1">Sorununuzu detaylandırın, hızlıca dönüş sağlayalım.</p>
            </div>
            <a href="{{ route('admin.tickets.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Destek Merkezi</a>
        </div>

        <form method="POST" action="{{ route('admin.tickets.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700">Konu</label>
                <input type="text" name="subject" value="{{ old('subject') }}" class="mt-1 w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-full text-slate-900 placeholder-slate-400" placeholder="Örn: Ürün senkronizasyon hatası" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Öncelik</label>
                <select name="priority" class="mt-1 w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-full text-slate-900">
                    <option value="low">Düşük</option>
                    <option value="medium" @selected(old('priority') === 'medium')>Orta</option>
                    <option value="high" @selected(old('priority') === 'high')>Yüksek</option>
                    <option value="urgent" @selected(old('priority') === 'urgent')>Acil</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Mesaj</label>
                <textarea name="body" rows="6" class="mt-1 w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400" placeholder="Sorununuzu adım adım açıklayın..." required>{{ old('body') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Dosya Ekleri</label>
                <input type="file" name="attachments[]" multiple class="mt-1 w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <p class="text-xs text-slate-400 mt-2">Ekran görüntüsü veya log dosyası ekleyebilirsiniz.</p>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-solid-accent">
                    Gönder
                </button>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline-accent">Vazgeç</a>
            </div>
        </form>
    </div>
@endsection
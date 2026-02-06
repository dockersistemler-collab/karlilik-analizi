@extends('layouts.admin')

@section('header')
    Destek Talebi Oluştur
@endsection

@section('content')
    <div class="panel-card p-6 mb-6 relative overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-sky-50 via-white to-emerald-50"></div>
        <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-sky-200/35 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 h-80 w-80 rounded-full bg-emerald-200/30 blur-3xl"></div>

        <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Yeni Talep</p>
                <h3 class="text-xl font-semibold text-slate-900 mt-2">Sorununuzu detaylandırın.</h3>
                <p class="text-sm text-slate-600 mt-1 max-w-2xl">Ne kadar detay verirseniz, o kadar hızlı çözebiliriz.</p>
            </div>
            <a href="{{ route('portal.tickets.index') }}" class="btn btn-outline-accent">
                <i class="fa-solid fa-arrow-left"></i>
                Taleplerim
            </a>
        </div>
    </div>

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

        <form method="POST" action="{{ route('portal.tickets.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-slate-700">Konu</label>
                <input type="text" name="subject" value="{{ old('subject') }}" class="mt-1 w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-full text-slate-900 placeholder-slate-400" placeholder="Örn: Ürün senkronizasyon hatası" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Öncelik</label>
                <select name="priority" class="mt-1 w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-full text-slate-900">
                    <option value="low" @selected(old('priority') === 'low')>Düşük</option>
                    <option value="medium" @selected(old('priority', 'medium') === 'medium')>Orta</option>
                    <option value="high" @selected(old('priority') === 'high')>Yüksek</option>
                    <option value="urgent" @selected(old('priority') === 'urgent')>Acil</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700">Mesaj</label>
                <textarea name="body" rows="6" class="mt-1 w-full px-3.5 py-2 text-sm bg-white border border-slate-200 rounded-2xl text-slate-900 placeholder-slate-400" placeholder="Sorununuzu adım adım açıklayın..." required>{{ old('body') }}</textarea>
                <p class="text-xs text-slate-400 mt-2">İpucu: Hata mesajı, ekran görüntüsü ve hangi adımda oluştuğunu yazın.</p>
            </div>

            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 h-9 w-9 rounded-xl bg-white border border-slate-200 text-slate-700 flex items-center justify-center">
                        <i class="fa-regular fa-paperclip"></i>
                    </span>
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-slate-700">Dosya Ekleri</label>
                        <input type="file" name="attachments[]" multiple class="mt-2 w-full text-sm text-slate-700">
                        <p class="text-xs text-slate-500 mt-2">Ekran görüntüsü veya log dosyası ekleyebilirsiniz.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="btn btn-solid-accent">
                    Gönder
                </button>
                <a href="{{ route('portal.tickets.index') }}" class="btn btn-outline-accent">Vazgeç</a>
            </div>
        </form>
    </div>
@endsection



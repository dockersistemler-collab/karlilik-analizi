<div class="panel-card p-6 max-w-3xl">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Yerleşim</label>
            <select name="placement" class="w-full" required>
                @foreach($placements as $key => $label)
                    <option value="{{ $key }}" @selected(old('placement', $banner->placement ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Başlık</label>
            <input type="text" name="title" value="{{ old('title', $banner->title ?? '') }}" class="w-full">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Mesaj</label>
            <textarea name="message" rows="3" class="w-full">{{ old('message', $banner->message ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Link URL</label>
            <input type="text" name="link_url" value="{{ old('link_url', $banner->link_url ?? '') }}" class="w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Link Metni</label>
            <input type="text" name="link_text" value="{{ old('link_text', $banner->link_text ?? '') }}" class="w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Arka Plan Rengi</label>
            <input type="text" name="bg_color" value="{{ old('bg_color', $banner->bg_color ?? '#0f172a') }}" class="w-full">
            <p class="text-xs text-slate-400 mt-1">Örnek: #0f172a</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Yazı Rengi</label>
            <input type="text" name="text_color" value="{{ old('text_color', $banner->text_color ?? '#ffffff') }}" class="w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Başlangıç</label>
            <input type="date" name="starts_at" value="{{ old('starts_at', optional($banner->starts_at ?? null)->format('Y-m-d')) }}" class="w-full">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Bitiş</label>
            <input type="date" name="ends_at" value="{{ old('ends_at', optional($banner->ends_at ?? null)->format('Y-m-d')) }}" class="w-full">
            <p class="text-xs text-slate-400 mt-1">Geri sayım için bitiş tarihi gerekli.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Sıralama</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" class="w-full">
        </div>
        <div class="flex items-center gap-3 mt-6">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded" @checked(old('is_active', $banner->is_active ?? true))>
            <span class="text-sm text-slate-600">Aktif</span>
        </div>
        <div class="flex items-center gap-3 mt-6">
            <input type="checkbox" name="show_countdown" value="1" class="h-4 w-4 rounded" @checked(old('show_countdown', $banner->show_countdown ?? false))>
            <span class="text-sm text-slate-600">Geri sayım göster</span>
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1">Görsel</label>
            <input type="file" name="image" class="w-full">
            @if(!empty($banner?->image_path))
                <div class="mt-2 flex items-center gap-3">
                    <img src="{{ asset('storage/' . $banner->image_path) }}" alt="Banner" class="h-12 rounded-lg border border-slate-200">
                    <label class="text-sm text-slate-600 flex items-center gap-2">
                        <input type="checkbox" name="remove_image" value="1" class="h-4 w-4 rounded">
                        Görseli kaldır
                    </label>
                </div>
            @endif
        </div>
    </div>
</div>

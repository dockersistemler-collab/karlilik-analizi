<div class="panel-card p-6 max-w-3xl">
    <form method="POST" action="{{ $action }}" class="space-y-4">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif
        <div>
            <label class="block text-sm">Kapsam (Müşteri)</label>
            <select name="user_id" class="w-full mt-1">
                <option value="">Genel</option>
                @foreach($clients as $client)
                    <option value="{{ $client->id }}" @selected(old('user_id', $template->user_id ?? '') == $client->id)>{{ $client->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm">Kategori</label>
            @php($categoryLabels = ['shipping' => 'Kargo', 'return' => 'İade', 'product' => 'Ürün', 'warranty' => 'Garanti', 'general' => 'Genel'])
            <select name="category" class="w-full mt-1" required>
                @foreach(['shipping','return','product','warranty','general'] as $category)
                    <option value="{{ $category }}" @selected(old('category', $template->category ?? 'general') === $category)>{{ $categoryLabels[$category] ?? $category }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm">Başlık</label>
            <input name="title" class="w-full mt-1" required value="{{ old('title', $template->title ?? '') }}">
        </div>
        <div>
            <label class="block text-sm">Mesaj</label>
            <textarea name="body" rows="6" class="w-full mt-1" required>{{ old('body', $template->body ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm">Pazaryerleri</label>
            @php($selectedMarketplaces = (array) old('marketplaces', $template->marketplaces ?? []))
            <div class="grid grid-cols-2 gap-2 mt-2">
                @foreach($marketplaces as $marketplace)
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="marketplaces[]" value="{{ $marketplace->code }}" @checked(in_array($marketplace->code, $selectedMarketplaces, true))>
                        <span>{{ $marketplace->name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        <label class="inline-flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $template->is_active ?? true))>
            <span>Aktif</span>
        </label>
        <button class="btn btn-solid-accent">Kaydet</button>
    </form>
</div>


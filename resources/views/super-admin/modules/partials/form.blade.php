<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Kod</label>
    <input type="text" name="code" value="{{ old('code', $module->code) }}" class="w-full" placeholder="feature.priority_support">
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Ad</label>
    <input type="text" name="name" value="{{ old('name', $module->name) }}" class="w-full" placeholder="Öncelikli Destek">
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Açıklama</label>
    <textarea name="description" rows="3" class="w-full">{{ old('description', $module->description) }}</textarea>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-3">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Tip</label>
        <select name="type" class="w-full">
            <option value="feature" @selected(old('type', $module->type) === 'feature')>feature</option>
            <option value="integration" @selected(old('type', $module->type) === 'integration')>integration</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Faturalama</label>
        <select name="billing_type" class="w-full">
            <option value="recurring" @selected(old('billing_type', $module->billing_type) === 'recurring')>recurring</option>
            <option value="one_time" @selected(old('billing_type', $module->billing_type) === 'one_time')>one_time</option>
            <option value="usage" @selected(old('billing_type', $module->billing_type) === 'usage')>usage</option>
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Sıra</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $module->sort_order) }}" class="w-full">
    </div>
</div>

<div class="flex items-center gap-3">
    <input type="checkbox" name="is_active" value="1" class="h-4 w-4" @checked(old('is_active', $module->is_active))>
    <label class="text-sm text-slate-700">Aktif</label>
</div>


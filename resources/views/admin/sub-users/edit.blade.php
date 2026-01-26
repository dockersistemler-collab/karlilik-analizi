@extends('layouts.admin')

@section('header')
    Alt Kullanıcı Düzenle
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.sub-users.update', $subUser) }}" class="panel-card p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700">İsim</label>
                <input type="text" name="name" class="mt-1 w-full" value="{{ old('name', $subUser->name) }}" required>
                @error('name')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">E-posta</label>
                <input type="email" name="email" class="mt-1 w-full" value="{{ old('email', $subUser->email) }}" required>
                @error('email')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Yeni Şifre (opsiyonel)</label>
                <input type="password" name="password" class="mt-1 w-full">
                @error('password')
                    <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700">Yeni Şifre Tekrar</label>
                <input type="password" name="password_confirmation" class="mt-1 w-full">
            </div>
            <div class="md:col-span-2 flex items-center gap-2">
                <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded" {{ old('is_active', $subUser->is_active) ? 'checked' : '' }}>
                <label for="is_active" class="text-sm text-slate-700">Aktif</label>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-800 mb-3">Yetkiler</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                @foreach($permissions as $key => $label)
                    <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2">
                        <input type="checkbox" name="permissions[]" value="{{ $key }}" class="rounded"
                            {{ in_array($key, old('permissions', $selected)) ? 'checked' : '' }}>
                        <span class="text-slate-700">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('permissions')
                <p class="text-xs text-red-500 mt-2">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-3">
            <button type="submit">Güncelle</button>
            <a href="{{ route('admin.sub-users.index') }}" class="text-slate-500 hover:text-slate-700">Vazgeç</a>
        </div>
    </form>
@endsection

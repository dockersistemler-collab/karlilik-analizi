@extends('layouts.admin')

@section('header')
    Kategori Düzenle
@endsection

@section('content')
    @include('admin.products.partials.catalog-tabs')

    <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="panel-card p-6 space-y-5 max-w-xl">
        @csrf
        @method('PUT')
        <div>
            <label class="block text-sm font-medium text-slate-700">Kategori Adı</label>
            <input type="text" name="name" class="mt-1 w-full" value="{{ old('name', $category->name) }}" required>
            @error('name')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex items-center gap-3">
            <button type="submit">Güncelle</button>
            <a href="{{ route('admin.categories.index') }}" class="text-slate-500 hover:text-slate-700">Vazgeç</a>
        </div>
    </form>
@endsection

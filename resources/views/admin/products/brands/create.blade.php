@extends('layouts.admin')

@section('header')
    Marka Ekle
@endsection

@section('content')
    @include('admin.products.partials.catalog-tabs')

    <form method="POST" action="{{ route('admin.brands.store') }}" class="panel-card p-6 space-y-5 max-w-xl">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700">Marka Adı</label>
            <input type="text" name="name" class="mt-1 w-full" value="{{ old('name') }}" required>
            @error('name')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex items-center gap-3">
            <button type="submit">Kaydet</button>
            <a href="{{ route('admin.brands.index') }}" class="text-slate-500 hover:text-slate-700">Vazgeç</a>
        </div>
    </form>
@endsection

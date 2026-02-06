@extends('layouts.admin')

@section('header')
    Şifre Değiştirme
@endsection

@section('content')
    <form method="POST" action="{{ route('portal.subuser.password.update') }}" class="panel-card p-6 space-y-5 max-w-xl">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-slate-700">Mevcut Şifre</label>
            <input type="password" name="current_password" class="mt-1 w-full" required>
            @error('current_password')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Yeni Şifre</label>
            <input type="password" name="password" class="mt-1 w-full" required>
            @error('password')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700">Yeni Şifre Tekrar</label>
            <input type="password" name="password_confirmation" class="mt-1 w-full" required>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit">Güncelle</button>
            <a href="{{ route('portal.dashboard') }}" class="text-slate-500 hover:text-slate-700">Vazgeç</a>
        </div>
    </form>
@endsection


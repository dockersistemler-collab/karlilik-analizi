@extends('layouts.super-admin')



@section('header')

    Yeni Banner

@endsection



@section('content')

    @if ($errors->any())

        <div class="panel-card px-4 py-3 mb-4 border-rose-200 text-rose-600">

            {{ $errors->first() }}

        </div>

    @endif

    <form method="POST" action="{{ route('super-admin.banners.store') }}" enctype="multipart/form-data" class="space-y-4">

        @csrf

        @include('super-admin.banners._form', ['banner' => null])

        <div class="flex items-center gap-3">

            <button type="submit" class="btn btn-solid-accent">Kaydet</button>

            <a href="{{ route('super-admin.banners.index') }}" class="btn btn-outline-accent">Vazgeç</a>

        </div>

    </form>

@endsection








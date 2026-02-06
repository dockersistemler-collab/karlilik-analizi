@extends('layouts.super-admin')



@section('header')

    Modül Ekle

@endsection



@section('content')

    <div class="panel-card p-6 max-w-3xl">

        <form method="POST" action="{{ route('super-admin.modules.store') }}" class="space-y-4">

            @csrf

            @include('super-admin.modules.partials.form', ['module' => $module])

            <div class="pt-2 flex gap-3">

                <button type="submit" class="btn btn-outline-accent">Kaydet</button>

                <a href="{{ route('super-admin.modules.index') }}" class="btn btn-outline">İptal</a>

            </div>

        </form>

    </div>

@endsection










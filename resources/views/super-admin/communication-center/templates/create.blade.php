@extends('layouts.super-admin')

@section('header')
    Şablon Oluştur
@endsection

@section('content')
    @include('super-admin.communication-center.templates.partials.form', [
        'action' => route('super-admin.communication-center.templates.store'),
        'method' => 'POST',
        'template' => null,
    ])
@endsection


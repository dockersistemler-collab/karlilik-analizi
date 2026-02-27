@extends('layouts.super-admin')

@section('header')
    Şablon Düzenle
@endsection

@section('content')
    @include('super-admin.communication-center.templates.partials.form', [
        'action' => route('super-admin.communication-center.templates.update', $template),
        'method' => 'PUT',
        'template' => $template,
    ])
@endsection


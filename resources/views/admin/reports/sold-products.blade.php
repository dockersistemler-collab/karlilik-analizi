@extends('layouts.admin')

@section('header')
    Satılan Ürün Listesi
@endsection

@section('content')
    @include('admin.reports.partials.report-shell', [
        'title' => 'Satılan Ürün Listesi',
        'subtitle' => 'Satışa konu olan ürünlerin detaylı listesi.',
        'badge' => 'Beta',
    ])
@endsection

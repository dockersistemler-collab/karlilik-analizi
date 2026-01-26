@extends('layouts.admin')

@section('header')
    Çok Satan Ürünler
@endsection

@section('content')
    @include('admin.reports.partials.report-shell', [
        'title' => 'Çok Satan Ürünler',
        'subtitle' => 'Ürün bazlı satış adedi ve ciro kırılımı.',
        'badge' => 'Beta',
    ])
@endsection

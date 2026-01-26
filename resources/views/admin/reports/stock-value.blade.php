@extends('layouts.admin')

@section('header')
    Stoktaki Ürün Tutarları Raporu
@endsection

@section('content')
    @include('admin.reports.partials.report-shell', [
        'title' => 'Stoktaki Ürün Tutarları Raporu',
        'subtitle' => 'Stok değerine göre ürün dağılımı ve toplam tutar.',
        'badge' => 'Beta',
    ])
@endsection

@extends('layouts.admin')

@section('header')
    Kategori Bazlı Satış
@endsection

@section('content')
    @include('admin.reports.partials.report-shell', [
        'title' => 'Kategori Bazlı Satış',
        'subtitle' => 'Kategori kırılımında satış adetleri ve ciro.',
        'badge' => 'Beta',
    ])
@endsection

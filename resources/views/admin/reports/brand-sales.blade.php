@extends('layouts.admin')

@section('header')
    Marka Bazlı Satış
@endsection

@section('content')
    @include('admin.reports.partials.report-shell', [
        'title' => 'Marka Bazlı Satış',
        'subtitle' => 'Marka bazında satış performansı.',
        'badge' => 'Beta',
    ])
@endsection

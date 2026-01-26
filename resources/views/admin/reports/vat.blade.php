@extends('layouts.admin')

@section('header')
    KDV Raporu
@endsection

@section('content')
    @include('admin.reports.partials.report-shell', [
        'title' => 'KDV Raporu',
        'subtitle' => 'KDV oranlarına göre satış ve vergi dağılımı.',
        'badge' => 'Beta',
    ])
@endsection

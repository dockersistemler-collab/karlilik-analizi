@extends('layouts.admin')

@section('header')
    Komisyon Raporu
@endsection

@section('content')
    @include('admin.reports.partials.report-shell', [
        'title' => 'Komisyon Raporu',
        'subtitle' => 'Pazaryeri komisyonlarının toplam ve dağılımı.',
        'badge' => 'Beta',
    ])
@endsection

@php
    $isSuperAdmin = auth()->user()?->isSuperAdmin();
@endphp

@extends($isSuperAdmin ? 'layouts.super-admin' : 'layouts.admin')

@section('header')
    Profili Düzenle
@endsection

@section('content')
    <div class="max-w-5xl space-y-6">
        <div class="panel-card p-6">
            <div class="max-w-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="panel-card p-6">
            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="panel-card p-6">
            <div class="max-w-2xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection

@extends('layouts.admin')

@section('header', 'Destek')

@section('content')
    <div class="panel-card p-6 space-y-3 text-sm text-slate-600">
        <div class="text-slate-800 font-semibold">Destek ekibiyle iletisime gecin</div>
        <div>Herhangi bir sorun yasarsaniz bizimle iletisime gecebilirsiniz.</div>
        <div>
            <a href="mailto:destek@webreen.com" class="btn btn-outline-accent">E-posta gonder</a>
        </div>
    </div>
@endsection


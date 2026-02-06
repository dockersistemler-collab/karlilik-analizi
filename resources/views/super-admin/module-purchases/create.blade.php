@extends('layouts.super-admin')



@section('header')

    Manuel Modül Satışı

@endsection



@section('content')

    <div class="panel-card p-6 max-w-3xl">

        <form method="POST" action="{{ route('super-admin.module-purchases.store') }}" class="space-y-4">

            @csrf

            <div>

                <label class="block text-sm font-medium text-slate-700 mb-1">Müşteri</label>

                <select name="user_id" class="w-full">

                    @foreach($users as $u)

                        <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>

                    @endforeach

                </select>

            </div>



            <div>

                <label class="block text-sm font-medium text-slate-700 mb-1">Modül</label>

                <select name="module_id" class="w-full">

                    @foreach($modules as $m)

                        <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->code }})</option>

                    @endforeach

                </select>

            </div>



            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">

                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Dönem</label>

                    <select name="period" class="w-full">

                        <option value="monthly">monthly</option>

                        <option value="yearly">yearly</option>

                        <option value="one_time">one_time</option>

                    </select>

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Tutar</label>

                    <input type="number" step="0.01" name="amount" class="w-full" placeholder="199.90">

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700 mb-1">Para Birimi</label>

                    <input type="text" name="currency" class="w-full" value="TRY">

                </div>

            </div>



            <div>

                <label class="block text-sm font-medium text-slate-700 mb-1">Not</label>

                <textarea name="note" rows="3" class="w-full" placeholder="Manuel satış notu..."></textarea>

            </div>



            <div class="pt-2 flex gap-3">

                <button type="submit" class="btn btn-outline-accent">Oluştur</button>

                <a href="{{ route('super-admin.module-purchases.index') }}" class="btn btn-outline">İptal</a>

            </div>

        </form>

    </div>

@endsection










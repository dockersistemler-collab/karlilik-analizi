@extends('layouts.super-admin')





@section('header')


    Kullanıcıyı Düzenle


@endsection





@section('content')


    <div class="panel-card p-6 max-w-3xl">


        <form method="POST" action="{{ route('super-admin.users.update', $user) }}" class="space-y-4">


            @csrf


            @method('PUT')





            <div>


                <label class="block text-sm font-medium text-slate-700">Ad Soyad</label>


                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="mt-1 w-full border-slate-300 rounded-md" required>


            </div>





            <div>


                <label class="block text-sm font-medium text-slate-700">E-posta</label>


                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="mt-1 w-full border-slate-300 rounded-md" required>


            </div>





            <div>


                <label class="block text-sm font-medium text-slate-700">Rol</label>


                <select name="role" class="mt-1 w-full border-slate-300 rounded-md" required>


                    <option value="client" @selected(old('role', $user->role) === 'client')>Client</option>


                    <option value="super_admin" @selected(old('role', $user->role) === 'super_admin')>Super Admin</option>


                </select>


            </div>





            <div class="flex items-center gap-2">


                <input type="checkbox" name="is_active" value="1" class="rounded" @checked(old('is_active', $user->is_active))>


                <label class="text-sm text-slate-700">Aktif</label>


            </div>





            <div class="pt-4 border-t border-slate-200">


                <p class="text-sm font-semibold text-slate-800 mb-3">Paket Ata</p>


                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">


                    <div>


                        <label class="block text-sm font-medium text-slate-700">Paket</label>


                        <select name="plan_id" class="mt-1 w-full border-slate-300 rounded-md">


                            <option value="">Seçiniz</option>


                            @foreach($plans as $plan)


                                <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->name }}</option>


                            @endforeach


                        </select>


                    </div>


                    <div>


                        <label class="block text-sm font-medium text-slate-700">Faturalama</label>


                        <select name="billing_period" class="mt-1 w-full border-slate-300 rounded-md">


                            <option value="monthly" @selected(old('billing_period') === 'monthly')>Aylık</option>


                            <option value="yearly" @selected(old('billing_period') === 'yearly')>Yıllık</option>


                        </select>


                    </div>


                </div>


                <label class="inline-flex items-center gap-2 mt-3">


                    <input type="checkbox" name="assign_plan" value="1" class="rounded">


                    <span class="text-sm text-slate-700">Seçilen paketi kullanıcıya ata (mevcut aboneliği iptal eder)</span>

                </label>


            </div>





            <div class="flex items-center gap-3">


                <button type="submit" class="btn btn-solid-accent">


                    Kaydet


                </button>


                <a href="{{ route('super-admin.users.index') }}" class="btn btn-outline-accent">


                    Geri Dön


                </a>


            </div>


        </form>


    </div>


@endsection















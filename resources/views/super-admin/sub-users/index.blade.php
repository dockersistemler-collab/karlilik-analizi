@extends('layouts.super-admin')



@section('header')

    Alt Kullanıcılar

@endsection



@section('content')

    <div class="panel-card p-6">

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4 text-sm">

            <input type="text" name="search" class="w-full" placeholder="İsim / E-posta" value="{{ $filters['search'] ?? '' }}">

            <select name="owner_id" class="w-full">

                <option value="">Müşteri</option>

                @foreach($owners as $owner)

                    <option value="{{ $owner->id }}" @selected(($filters['owner_id'] ?? '') == $owner->id)>{{ $owner->name }}</option>

                @endforeach

            </select>

            <select name="status" class="w-full">

                <option value="">Durum</option>

                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option>

                <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Pasif</option>

            </select>

            <button type="submit">Filtrele</button>

        </form>



        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr class="text-left">

                        <th>Alt Kullanıcı</th>

                        <th>Müşteri</th>

                        <th>E-posta</th>

                        <th>Durum</th>

                        <th>Son Giriş</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($subUsers as $subUser)

                        <tr class="border-t border-slate-100">

                            <td class="font-medium text-slate-900">{{ $subUser->name }}</td>

                            <td class="text-slate-600">{{ $subUser->owner?->name ?? '-' }}</td>

                            <td class="text-slate-600">{{ $subUser->email }}</td>

                            <td>

                                <span class="px-2 py-1 rounded text-xs {{ $subUser->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">

                                    {{ $subUser->is_active ? 'Aktif' : 'Pasif' }}

                                </span>

                            </td>

                            <td class="text-slate-500">{{ $subUser->last_login_at?->format('d.m.Y H:i') ?? '-' }}</td>

                        </tr>

                    @empty

                        <tr class="border-t border-slate-100">

                            <td colspan="5" class="py-6 text-center text-slate-500">Alt kullanıcı bulunamadı.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>



        <div class="mt-4">

            {{ $subUsers->links() }}

        </div>

    </div>

@endsection








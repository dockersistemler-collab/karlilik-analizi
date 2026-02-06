@extends('layouts.super-admin')



@section('header')

    Kullanıcılar

@endsection



@section('content')

    <div class="panel-card p-4 mb-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">

            <h3 class="text-sm font-semibold text-slate-800">Filtreler</h3>

        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">

            <div class="md:col-span-2">

                <label class="block text-xs text-slate-500 mb-1">Arama</label>

                <input type="text" name="q" value="{{ $search }}" class="w-full border-slate-300 rounded-md" placeholder="İsim veya e-posta">

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">Rol</label>

                <select name="role" class="w-full border-slate-300 rounded-md">

                    <option value="">Tümü</option>

                    @foreach($roles as $roleKey => $roleLabel)

                        <option value="{{ $roleKey }}" @selected($role === $roleKey)>{{ $roleLabel }}</option>

                    @endforeach

                </select>

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">Durum</label>

                <select name="status" class="w-full border-slate-300 rounded-md">

                    <option value="">Tümü</option>

                    <option value="active" @selected($status === 'active')>Aktif</option>

                    <option value="inactive" @selected($status === 'inactive')>Pasif</option>

                </select>

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">Kayıt (min)</label>

                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full border-slate-300 rounded-md">

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">Kayıt (max)</label>

                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full border-slate-300 rounded-md">

            </div>

            <div class="md:col-span-6 flex items-center gap-3">

                <button type="submit" class="btn btn-solid-accent">Filtrele</button>

                <a href="{{ route('super-admin.users.index') }}" class="text-slate-500 hover:text-slate-700">Sıfırla</a>

            </div>

        </form>

    </div>



    <div class="panel-card p-0 overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-slate-50">

                <tr>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ad</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">E-posta</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Rol</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Durum</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">İşlem</th>

                </tr>

            </thead>

            <tbody class="divide-y divide-slate-200">

                @forelse($users as $user)

                    <tr>

                        <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $user->name }}</td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ $user->email }}</td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ $user->role }}</td>

                        <td class="px-6 py-4 text-sm">

                            <span class="px-2 py-1 rounded text-xs {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">

                                {{ $user->is_active ? 'Aktif' : 'Pasif' }}

                            </span>

                        </td>

                        <td class="px-6 py-4 text-sm">

                            <a href="{{ route('super-admin.users.edit', $user) }}" class="text-blue-600 hover:text-blue-900">

                                Düzenle

                            </a>

                            @if(!$user->isSuperAdmin())

                                <div class="mt-3">

                                    <form method="POST" action="{{ route('super-admin.support-view.start', $user) }}" class="space-y-2">

                                        @csrf

                                        <textarea name="reason" rows="2" required

                                            class="w-full text-xs border border-slate-200 rounded-lg px-2 py-1"

                                            placeholder="Destek gerekçesi (ör. Talep #123)"></textarea>

                                        <button type="submit" class="text-xs px-3 py-1 rounded-lg border border-amber-300 text-amber-700 hover:bg-amber-50">

                                            Destek Modu (Görüntüle)

                                        </button>

                                    </form>

                                </div>

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" class="px-6 py-4 text-center text-slate-500">Kullanıcı bulunamadı.</td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>



    <div class="mt-4">

        {{ $users->links() }}

    </div>

@endsection








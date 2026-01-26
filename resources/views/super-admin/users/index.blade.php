@extends('layouts.super-admin')

@section('header')
    Kullanıcılar
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow overflow-hidden">
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

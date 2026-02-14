@extends('layouts.super-admin')



@section('header')

    Müşteriler

@endsection



@section('content')

    <div class="panel-card p-4 mb-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">

            <h3 class="text-sm font-semibold text-slate-800">Müşteri Listesi</h3>

            <a href="{{ route('super-admin.customers.create') }}" class="btn btn-outline-accent">

                Müşteri Ekle

            </a>

        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">

            <div class="md:col-span-2">

                <label class="block text-xs text-slate-500 mb-1">Ara</label>

                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="w-full border-slate-300 rounded-md" placeholder="İsim veya e-posta">

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">E-posta</label>

                <input type="text" name="email" value="{{ $filters['email'] ?? '' }}" class="w-full border-slate-300 rounded-md" placeholder="mail@domain.com">

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">Telefon</label>

                <input type="text" name="phone" value="{{ $filters['phone'] ?? '' }}" class="w-full border-slate-300 rounded-md" placeholder="05xx">

            </div>

            <div>

                <label class="block text-xs text-slate-500 mb-1">Client</label>

                <select name="user_id" class="w-full border-slate-300 rounded-md">

                    <option value="">Tümü</option>

                    <option value="none" @selected(($filters['user_id'] ?? '') === 'none')>Bağlı değil</option>

                    @foreach($clients as $client)

                        <option value="{{ $client->id }}" @selected(($filters['user_id'] ?? '') == $client->id)>

                            {{ $client->name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="flex items-end gap-3">

                <button type="submit" class="btn btn-solid-accent">Filtrele</button>

                <a href="{{ route('super-admin.customers.index') }}" class="text-slate-500 hover:text-slate-700">Sıfırla</a>

            </div>

        </form>

    </div>



    <div class="panel-card p-0 overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-slate-50">

                <tr>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Müşteri</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Client</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">E-posta</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Telefon</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">İşlem</th>

                </tr>

            </thead>

            <tbody class="divide-y divide-slate-200">

                @forelse($customers as $customer)

                    <tr>

                        <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $customer->name }}</td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ $customer->user?->name ?? '-' }}</td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ $customer->email }}</td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ $customer->phone ?? '-' }}</td>

                        <td class="px-6 py-4 text-sm">

                            <div class="flex items-center gap-3">

                                <a href="{{ route('super-admin.customers.show', $customer) }}" class="text-blue-600 hover:text-blue-900">Görüntüle</a>

                                <a href="{{ route('super-admin.customers.edit', $customer) }}" class="text-slate-600 hover:text-slate-900">Düzenle</a>

                                <form method="POST" action="{{ route('super-admin.customers.destroy', $customer) }}">

                                    @csrf

                                    @method('DELETE')

                                    <button type="submit" class="text-red-500 hover:text-red-700" onclick="return confirm('Müşteri silinsin mi?')">Sil</button>

                                </form>

                            </div>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" class="px-6 py-4 text-center text-slate-500">Müşteri bulunamadı.</td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>



    <div class="mt-4">

        {{ $customers->links() }}

    </div>

@endsection









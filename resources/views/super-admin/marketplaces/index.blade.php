@extends('layouts.super-admin')



@section('header')

    Pazaryerleri

@endsection



@section('content')

    <div class="mb-6">

        <a href="{{ route('super-admin.marketplaces.create') }}" class="btn btn-solid-accent">

            Yeni Pazaryeri

        </a>

    </div>



    <div class="panel-card p-0 overflow-hidden">

        <table class="min-w-full">

            <thead class="bg-slate-50">

                <tr>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ad</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Kod</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">API URL</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Durum</th>

                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">İşlem</th>

                </tr>

            </thead>

            <tbody class="divide-y divide-slate-200">

                @forelse($marketplaces as $marketplace)

                    <tr>

                        <td class="px-6 py-4 text-sm font-medium text-slate-800">{{ $marketplace->name }}</td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ $marketplace->code }}</td>

                        <td class="px-6 py-4 text-sm text-slate-600">{{ $marketplace->api_url }}</td>

                        <td class="px-6 py-4 text-sm">

                            <span class="px-2 py-1 rounded text-xs {{ $marketplace->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">

                                {{ $marketplace->is_active ? 'Aktif' : 'Pasif' }}

                            </span>

                        </td>

                        <td class="px-6 py-4 text-sm">

                            <a href="{{ route('super-admin.marketplaces.show', $marketplace) }}" class="text-blue-600 hover:text-blue-900 mr-3">

                                Görüntüle

                            </a>

                            <a href="{{ route('super-admin.marketplaces.edit', $marketplace) }}" class="text-amber-600 hover:text-amber-800 mr-3">

                                Düzenle

                            </a>

                            <form action="{{ route('super-admin.marketplaces.destroy', $marketplace) }}" method="POST" class="inline">

                                @csrf

                                @method('DELETE')

                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Silmek istediğinize emin misiniz?')">

                                    Sil

                                </button>

                            </form>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" class="px-6 py-4 text-center text-slate-500">Pazaryeri bulunamadı.</td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>



    <div class="mt-4">

        {{ $marketplaces->links() }}

    </div>

@endsection












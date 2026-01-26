@extends('layouts.admin')

@section('title', 'Pazaryerleri')
@section('page-title', 'Pazaryerleri')

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.marketplaces.create') }}" class="btn btn-solid-accent">
        <i class="fas fa-plus mr-2"></i> Yeni Pazaryeri Ekle
    </a>
</div>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pazaryeri</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kod</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">API URL</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">API Bilgileri</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($marketplaces as $marketplace)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">{{ $marketplace->id }}</td>
                <td class="px-6 py-4 whitespace-nowrap font-semibold">{{ $marketplace->name }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <code class="bg-gray-100 px-2 py-1 rounded">{{ $marketplace->code }}</code>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $marketplace->api_url }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($marketplace->credentials)
                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">
                            <i class="fas fa-check-circle"></i> Tanımlı
                        </span>
                    @else
                        <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">
                            <i class="fas fa-times-circle"></i> Eksik
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded {{ $marketplace->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $marketplace->is_active ? 'Aktif' : 'Pasif' }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a href="{{ route('admin.marketplaces.show', $marketplace) }}" class="text-blue-600 hover:text-blue-900 mr-3">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('admin.marketplaces.edit', $marketplace) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="{{ route('admin.marketplaces.destroy', $marketplace) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">Henüz pazaryeri bulunmuyor</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
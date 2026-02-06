@extends('layouts.admin')



@section('title', 'Pazaryeri Düzenle')

@section('page-title', 'Pazaryeri Düzenle: ' . $marketplace->name)



@section('content')

<div class="bg-white rounded-lg shadow p-6">

    <form action="{{ route('portal.marketplaces.update', $marketplace) }}" method="POST">

        @csrf

        @method('PUT')



        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-2">Pazaryeri Adı</label>

                <input type="text" name="name" value="{{ old('name', $marketplace->name) }}" 

                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>

                @error('name')

                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>

                @enderror

            </div>



            <div>

                <label class="block text-sm font-medium text-gray-700 mb-2">Kod</label>

                <input type="text" name="code" value="{{ old('code', $marketplace->code) }}" 

                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>

                @error('code')

                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>

                @enderror

            </div>



            <div class="md:col-span-2">

                <label class="block text-sm font-medium text-gray-700 mb-2">API URL</label>

                <input type="url" name="api_url" value="{{ old('api_url', $marketplace->api_url) }}" 

                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

                @error('api_url')

                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>

                @enderror

            </div>



            <div class="md:col-span-2">

                <label class="flex items-center">

                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $marketplace->is_active) ? 'checked' : '' }} 

                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200">

                    <span class="ml-2 text-sm text-gray-600">Aktif</span>

                </label>

            </div>

        </div>



        <hr class="my-6">



        <h3 class="text-lg font-semibold mb-4">API Bilgileri</h3>



        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            <div>

                <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>

                <input type="text" name="api_key" value="{{ old('api_key', $marketplace->credential->api_key ?? '') }}" 

                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

            </div>



            <div>

                <label class="block text-sm font-medium text-gray-700 mb-2">API Secret</label>

                <input type="text" name="api_secret" value="{{ old('api_secret', $marketplace->credential->api_secret ?? '') }}" 

                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

            </div>



            <div>

                <label class="block text-sm font-medium text-gray-700 mb-2">Supplier ID</label>

                <input type="text" name="supplier_id" value="{{ old('supplier_id', $marketplace->credentials->supplier_id ?? '') }}" 

                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

            </div>



            <div>

                <label class="block text-sm font-medium text-gray-700 mb-2">Store ID</label>

                <input type="text" name="store_id" value="{{ old('store_id', $marketplace->credentials->store_id ?? '') }}" 

                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

            </div>

        </div>



        <div class="flex justify-end space-x-4">

            <a href="{{ route('portal.marketplaces.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">

                İptal

            </a>

            <button type="submit" class="btn btn-solid-accent">

                Güncelle

            </button>

        </div>

    </form>

</div>

@endsection





@extends('layouts.super-admin')

@section('header')
    İletişim Şablonları
@endsection

@section('content')
    <div class="panel-card p-6">
        <div class="mb-4">
            <a href="{{ route('super-admin.communication-center.templates.create') }}" class="btn btn-solid-accent">Yeni Şablon</a>
        </div>
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Başlık</th>
                    <th class="py-2">Kategori</th>
                    <th class="py-2">Kapsam</th>
                    <th class="py-2">Durum</th>
                    <th class="py-2"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $template)
                    <tr class="border-b">
                        <td class="py-2">{{ $template->title }}</td>
                        <td class="py-2">
                            @php($cat = ['shipping'=>'Kargo','return'=>'İade','product'=>'Ürün','warranty'=>'Garanti','general'=>'Genel'])
                            {{ $cat[$template->category] ?? $template->category }}
                        </td>
                        <td class="py-2">{{ $template->user_id ? 'Müşteriye Özel' : 'Genel' }}</td>
                        <td class="py-2">{{ $template->is_active ? 'Aktif' : 'Pasif' }}</td>
                        <td class="py-2 text-right">
                            <a href="{{ route('super-admin.communication-center.templates.edit', $template) }}" class="btn btn-outline">Düzenle</a>
                            <form method="POST" action="{{ route('super-admin.communication-center.templates.destroy', $template) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline" onclick="return confirm('Silinsin mi?')" type="submit">Sil</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">{{ $templates->links() }}</div>
    </div>
@endsection


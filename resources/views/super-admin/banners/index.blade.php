@extends('layouts.super-admin')

@section('header')
    Banner Yönetimi
@endsection

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h3 class="text-sm font-semibold text-slate-800">Bannerlar</h3>
            <p class="text-xs text-slate-500">Müşteri paneli ve public site için banner yönetin.</p>
        </div>
        <a href="{{ route('super-admin.banners.create') }}" class="btn btn-solid-accent">
            <i class="fa-solid fa-plus text-xs mr-2"></i>
            Yeni Banner
        </a>
    </div>

    <div class="panel-card overflow-hidden">
        <table class="min-w-full">
            <thead>
                <tr class="text-left text-xs uppercase text-slate-500">
                    <th>Başlık</th>
                    <th>Yerleşim</th>
                    <th>Durum</th>
                    <th>Planlama</th>
                    <th class="text-right">İşlem</th>
                </tr>
            </thead>
            <tbody>
                @forelse($banners as $banner)
                    <tr>
                        <td class="font-medium text-slate-900">
                            {{ $banner->title ?: 'Banner' }}
                            @if($banner->message)
                                <div class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($banner->message, 60) }}</div>
                            @endif
                        </td>
                        <td class="text-sm text-slate-600">
                            {{ $banner->placement === 'admin_header' ? 'Müşteri Paneli' : 'Public Site' }}
                        </td>
                        <td>
                            <span class="text-xs font-semibold {{ $banner->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                                {{ $banner->is_active ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td class="text-xs text-slate-500">
                            @if($banner->starts_at || $banner->ends_at)
                                {{ optional($banner->starts_at)->format('d.m.Y') ?? '-' }} / {{ optional($banner->ends_at)->format('d.m.Y') ?? '-' }}
                            @else
                                Süresiz
                            @endif
                        </td>
                        <td class="text-right whitespace-nowrap">
                            <a href="{{ route('super-admin.banners.edit', $banner) }}" class="btn btn-outline-accent">Düzenle</a>
                            <form method="POST" action="{{ route('super-admin.banners.destroy', $banner) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-accent" onclick="return confirm('Banner silinsin mi?')">
                                    Sil
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-slate-500">Henüz banner yok.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

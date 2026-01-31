@extends('layouts.super-admin')

@section('header')
    Modüller
@endsection

@section('content')
    <div class="bg-white rounded-xl border border-slate-100 p-6">
        <div class="flex items-center justify-between gap-4 mb-5">
            <div>
                <div class="text-lg font-semibold text-slate-900">Modül Tanımları</div>
                <p class="text-sm text-slate-500 mt-1">Müşteri bazlı modül satışı için modülleri buradan yönetebilirsiniz.</p>
            </div>
            <a href="{{ route('super-admin.modules.create') }}" class="btn btn-outline-accent">
                <i class="fa-solid fa-plus"></i>
                Modül Ekle
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4">Kod</th>
                        <th class="py-2 pr-4">Ad</th>
                        <th class="py-2 pr-4">Tip</th>
                        <th class="py-2 pr-4">Faturalama</th>
                        <th class="py-2 pr-4">Durum</th>
                        <th class="py-2 pr-4"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($modules as $module)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 font-mono text-xs text-slate-700">{{ $module->code }}</td>
                            <td class="py-3 pr-4 font-semibold text-slate-900">{{ $module->name }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $module->type }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $module->billing_type }}</td>
                            <td class="py-3 pr-4">
                                @if($module->is_active)
                                    <span class="inline-flex px-2 py-1 rounded-md text-xs bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif</span>
                                @else
                                    <span class="inline-flex px-2 py-1 rounded-md text-xs bg-slate-50 text-slate-700 border border-slate-200">Pasif</span>
                                @endif
                            </td>
                            <td class="py-3 pr-4 text-right">
                                <a class="btn btn-outline" href="{{ route('super-admin.modules.edit', $module) }}">Düzenle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-slate-500">Henüz modül yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection


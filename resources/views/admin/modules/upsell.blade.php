@extends('layouts.admin')

@section('header')
    Modül Satın Alma
@endsection

@section('content')
    <div class="panel-card p-6">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-xl border border-slate-100 p-6">
                <div class="text-sm text-slate-500">İstenen modül</div>
                <div class="mt-1 text-xl font-semibold text-slate-900">
                    @if($module)
                        {{ $module->name }}
                    @elseif($marketplace)
                        {{ $marketplace->name }} Entegrasyonu
                    @else
                        {{ $code }}
                    @endif
                </div>

                @if($module?->description)
                    <p class="mt-3 text-slate-600">{{ $module->description }}</p>
                @else
                    <p class="mt-3 text-slate-600">
                        Bu modül hesabınız için aktif değil. Satın alarak veya paket yükselterek açabilirsiniz.
                    </p>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('pricing') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800">
                        Paketleri Gör
                    </a>
                    <a href="{{ route('admin.addons.index') }}" class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">
                        Eklentiler
                    </a>
                </div>

                <div class="mt-6 rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600">
                    <div class="font-semibold text-slate-800">Kod</div>
                    <div class="mt-1 font-mono text-xs">{{ $code }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection


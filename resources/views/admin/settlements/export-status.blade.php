@extends('layouts.admin')

@section('header')
    Settlement Export Durumu
@endsection

@section('content')
    <div class="panel-card p-5 max-w-2xl">
        <div class="text-sm text-slate-500 mb-1">İşlem Token</div>
        <div class="font-mono text-xs text-slate-700 mb-4">{{ $token }}</div>

        <div class="text-sm mb-4">
            Durum:
            <span class="font-semibold">{{ strtoupper((string) ($state['status'] ?? 'unknown')) }}</span>
        </div>

        @if(($state['status'] ?? '') === 'ready')
            <a href="{{ route('portal.settlements.exports.download', ['token' => $token]) }}" class="btn btn-outline-accent">
                CSV İndir
            </a>
        @elseif(($state['status'] ?? '') === 'failed')
            <div class="text-sm text-red-700">
                Export başarısız: {{ $state['error'] ?? 'Bilinmeyen hata' }}
            </div>
        @else
            <div class="text-sm text-slate-600 mb-4">
                Export hazırlanıyor. Sayfa otomatik yenilenecek.
            </div>
            <a href="{{ route('portal.settlements.exports.show', ['token' => $token]) }}" class="btn btn-outline">
                Yenile
            </a>

            @push('scripts')
                <script>
                    setTimeout(function () {
                        window.location.reload();
                    }, 4000);
                </script>
            @endpush
        @endif
    </div>
@endsection


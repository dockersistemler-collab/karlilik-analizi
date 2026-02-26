@props([
    'signals' => [],
    'title' => 'Sinyaller',
    'empty' => 'Sinyal bulunmuyor.',
])

@php
    $severityClass = function (string $severity): string {
        return match ($severity) {
            'critical' => 'bg-rose-50 text-rose-700 border-rose-200',
            'warning' => 'bg-amber-50 text-amber-700 border-amber-200',
            default => 'bg-sky-50 text-sky-700 border-sky-200',
        };
    };
@endphp

<div class="panel-card p-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-slate-800">{{ $title }}</h3>
        <a href="{{ route('portal.control-tower.signals') }}" class="btn btn-outline px-2 py-1 text-xs">Tüm Sinyaller</a>
    </div>

    <div class="space-y-2">
        @forelse($signals as $idx => $signal)
            @php
                $signalId = (int) data_get($signal, 'id', 0);
                $severity = (string) data_get($signal, 'severity', 'info');
                $type = (string) data_get($signal, 'type', '-');
                $titleText = (string) data_get($signal, 'title', 'Sinyal');
                $message = (string) data_get($signal, 'message', '');
                $drivers = (array) data_get($signal, 'drivers', []);
                $action = (array) data_get($signal, 'action_hint', []);
                $modalId = 'ct-why-'.$idx.'-'.$signalId;
            @endphp

            <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] border {{ $severityClass($severity) }}">
                                {{ strtoupper($severity) }}
                            </span>
                            <span class="text-xs text-slate-500">{{ $type }}</span>
                        </div>
                        <div class="font-semibold text-sm text-slate-900">{{ $titleText }}</div>
                        <div class="text-xs text-slate-600 mt-1">{{ $message }}</div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="btn btn-outline px-2 py-1 text-xs" data-ct-open="{{ $modalId }}">Neden?</button>
                        @if(!empty(data_get($action, 'url')))
                            <a href="{{ data_get($action, 'url') }}" class="btn btn-solid-accent px-2 py-1 text-xs">Aksiyon Çalıştır</a>
                        @endif
                        @if($signalId > 0 && !data_get($signal, 'is_resolved', false))
                            <form method="POST" action="{{ route('portal.control-tower.signals.resolve', $signalId) }}">
                                @csrf
                                <button class="btn btn-outline px-2 py-1 text-xs">Çöz</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div id="{{ $modalId }}" class="fixed inset-0 z-50 hidden">
                <div class="absolute inset-0 bg-slate-900/45" data-ct-close="{{ $modalId }}"></div>
                <div class="relative mx-auto mt-20 w-[94%] max-w-2xl rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl">
                    <div class="flex items-center justify-between">
                        <h4 class="font-semibold text-sm text-slate-900">Neden? {{ $titleText }}</h4>
                        <button type="button" class="btn btn-outline px-2 py-1 text-xs" data-ct-close="{{ $modalId }}">Kapat</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                        <div>
                            <div class="text-xs text-slate-500 mb-1">Sürücüler</div>
                            <pre class="text-xs bg-slate-50 border border-slate-200 rounded-lg p-2 overflow-auto">{{ json_encode($drivers, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 mb-1">Aksiyon İpucu</div>
                            <pre class="text-xs bg-slate-50 border border-slate-200 rounded-lg p-2 overflow-auto">{{ json_encode($action, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-sm text-slate-500">{{ $empty }}</div>
        @endforelse
    </div>
</div>

@once
    <script>
        document.addEventListener('click', function (event) {
            var openId = event.target.getAttribute('data-ct-open');
            if (openId) {
                var modal = document.getElementById(openId);
                if (modal) modal.classList.remove('hidden');
            }
            var closeId = event.target.getAttribute('data-ct-close');
            if (closeId) {
                var modalClose = document.getElementById(closeId);
                if (modalClose) modalClose.classList.add('hidden');
            }
        });
    </script>
@endonce

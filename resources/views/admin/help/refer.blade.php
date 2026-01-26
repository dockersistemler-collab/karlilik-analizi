@extends('layouts.admin')

@section('header')
    Tavsiye Et
@endsection

@section('content')
    @php
        $program = null;
        $stats = [
            'rewarded_count' => 0,
            'remaining' => null,
            'last_rewarded_at' => null,
        ];

        if (\Illuminate\Support\Facades\Schema::hasTable('referral_programs')) {
            $program = \App\Models\ReferralProgram::active()->latest()->first();
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('referrals') && auth()->check()) {
            $rewardedQuery = \App\Models\Referral::query()
                ->where('referrer_id', auth()->id())
                ->where('status', 'rewarded')
                ->where('rewarded_at', '>=', now()->subYear());

            $stats['rewarded_count'] = $rewardedQuery->count();
            $stats['last_rewarded_at'] = $rewardedQuery->latest('rewarded_at')->value('rewarded_at');

            if ($program && $program->max_uses_per_referrer_per_year > 0) {
                $stats['remaining'] = max($program->max_uses_per_referrer_per_year - $stats['rewarded_count'], 0);
            }
        }
    @endphp

    <div class="panel-card p-6 max-w-3xl">
        <p class="text-sm text-slate-600">
            {{ $program?->description ?? config('referral.program_text') }}
        </p>

        <div class="mt-4">
            <label class="block text-sm font-medium text-slate-700">Davet Linki</label>
            <div class="mt-2 flex flex-col md:flex-row gap-2">
                <input id="referral-link" type="text" class="w-full" value="{{ url('/register?ref=' . (auth()->id() ?? '')) }}" readonly>
                <button id="copy-referral" type="button" class="btn btn-outline-accent">
                    Kopyala
                </button>
            </div>
            <p id="copy-feedback" class="text-xs text-slate-500 mt-2 hidden">Link kopyalandı.</p>
        </div>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="panel-card p-4">
                <p class="text-xs uppercase text-slate-400">Tavsiye Eden Ödülü</p>
                <p class="text-lg font-semibold text-slate-900 mt-1">
                    {{ $program?->referrerRewardLabel() ?? '1 ay kullanım' }}
                </p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs uppercase text-slate-400">Tavsiye Alan Ödülü</p>
                <p class="text-lg font-semibold text-slate-900 mt-1">
                    {{ $program?->referredRewardLabel() ?? '1 ay kullanım' }}
                </p>
            </div>
            <div class="panel-card p-4">
                <p class="text-xs uppercase text-slate-400">Kalan Hak</p>
                <p class="text-lg font-semibold text-slate-900 mt-1">
                    @if($program && $program->max_uses_per_referrer_per_year > 0)
                        {{ $stats['remaining'] }} / {{ $program->max_uses_per_referrer_per_year }}
                    @else
                        Limitsiz
                    @endif
                </p>
            </div>
        </div>

        <div class="mt-4 panel-card p-4">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 text-sm text-slate-600">
                <div>
                    <span class="text-slate-400">Son ödül tarihi:</span>
                    <span class="font-medium text-slate-900">
                        {{ $stats['last_rewarded_at'] ? \Carbon\Carbon::parse($stats['last_rewarded_at'])->format('d.m.Y') : 'Henüz yok' }}
                    </span>
                </div>
                <div>
                    <span class="text-slate-400">Son 1 yılda ödül:</span>
                    <span class="font-medium text-slate-900">{{ $stats['rewarded_count'] }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const copyBtn = document.getElementById('copy-referral');
    const linkInput = document.getElementById('referral-link');
    const feedback = document.getElementById('copy-feedback');

    if (copyBtn && linkInput) {
        copyBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(linkInput.value);
                feedback?.classList.remove('hidden');
                setTimeout(() => feedback?.classList.add('hidden'), 1500);
            } catch (error) {
                linkInput.select();
                document.execCommand('copy');
                feedback?.classList.remove('hidden');
                setTimeout(() => feedback?.classList.add('hidden'), 1500);
            }
        });
    }
</script>
@endpush

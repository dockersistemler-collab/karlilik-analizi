@extends('layouts.super-admin')



@section('header')

    Tavsiye Geçmişi

@endsection



@section('content')

    <div class="panel-card p-6 mb-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">

            <h3 class="text-sm font-semibold text-slate-800">Filtreler</h3>

            <a href="{{ route('super-admin.referrals.export', request()->query()) }}" class="btn btn-outline-accent">

                CSV Dışa Aktar

            </a>

        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">

            <input type="number" name="referrer_id" value="{{ $filters['referrer_id'] ?? '' }}" placeholder="Tavsiye Eden ID" class="w-full">

            <input type="text" name="referred_email" value="{{ $filters['referred_email'] ?? '' }}" placeholder="Davet edilen e-posta" class="w-full">

            <select name="status" class="w-full">

                <option value="">Tüm Durumlar</option>

                <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Beklemede</option>

                <option value="rewarded" @selected(($filters['status'] ?? '') === 'rewarded')>Ödüllendi</option>

                <option value="limit_reached" @selected(($filters['status'] ?? '') === 'limit_reached')>Limit Aşıldı</option>

                <option value="inactive_program" @selected(($filters['status'] ?? '') === 'inactive_program')>Program Kapalı</option>

            </select>

            <select name="program_id" class="w-full">

                <option value="">Tüm Programlar</option>

                @foreach($programs as $program)

                    <option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') == $program->id)>

                        {{ $program->name }}

                    </option>

                @endforeach

            </select>

            <button class="btn btn-solid-accent">Filtrele</button>

        </form>

    </div>



    <div class="panel-card p-0 overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-slate-50 text-slate-500 uppercase text-xs">

                <tr>

                    <th class="px-4 py-3 text-left">Tavsiye Eden</th>

                    <th class="px-4 py-3 text-left">Davet Edilen</th>

                    <th class="px-4 py-3 text-left">Program</th>

                    <th class="px-4 py-3 text-left">Durum</th>

                    <th class="px-4 py-3 text-left">Ödüller</th>

                    <th class="px-4 py-3 text-left">Tarih</th>

                    <th class="px-4 py-3"></th>

                </tr>

            </thead>

            <tbody class="divide-y">

                @forelse($referrals as $referral)

                    <tr>

                        <td class="px-4 py-3">

                            <div class="text-slate-900 font-medium">{{ $referral->referrer?->name ?? '-' }}</div>

                            <div class="text-xs text-slate-400">ID: {{ $referral->referrer_id }}</div>

                        </td>

                        <td class="px-4 py-3">

                            <div class="text-slate-900 font-medium">{{ $referral->referredUser?->name ?? '-' }}</div>

                            <div class="text-xs text-slate-400">{{ $referral->referred_email ?? '-' }}</div>

                        </td>

                        <td class="px-4 py-3 text-slate-600">{{ $referral->program?->name ?? '-' }}</td>

                        @php

                            $statusClasses = [

                                'pending' => 'bg-slate-100 text-slate-600',

                                'rewarded' => 'bg-slate-300 text-slate-800',

                                'limit_reached' => 'bg-slate-200 text-slate-700',

                                'inactive_program' => 'bg-slate-200 text-slate-700',

                            ];

                        @endphp

                        <td class="px-4 py-3 text-slate-600">

                            <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $statusClasses[$referral->status] ?? 'bg-slate-100 text-slate-600' }}">

                                {{ $referral->status }}

                            </span>

                        </td>

                        <td class="px-4 py-3 text-slate-600">

                            @if($referral->status === 'rewarded')

                                @php

                                    $referrerReward = $referral->referrer_reward_type === 'percent'

                                        ? '%'.$referral->referrer_reward_value.' indirim'

                                        : $referral->referrer_reward_value.' ay kullanım';

                                    $referredReward = $referral->referred_reward_type === 'percent'

                                        ? '%'.$referral->referred_reward_value.' indirim'

                                        : $referral->referred_reward_value.' ay kullanım';

                                @endphp

                                <div class="text-xs text-slate-400">Eden: {{ $referrerReward }}</div>

                                <div class="text-xs text-slate-400">Alan: {{ $referredReward }}</div>

                            @else

                                -

                            @endif

                        </td>

                        <td class="px-4 py-3 text-slate-600">

                            {{ optional($referral->created_at)->format('d.m.Y') }}

                        </td>

                        <td class="px-4 py-3 text-right">

                            <a href="{{ route('super-admin.referrals.show', $referral) }}" class="text-blue-600 hover:text-blue-900">Detay</a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6" class="px-4 py-6 text-center text-slate-500">Kayıt bulunamadı.</td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>



    <div class="mt-4">

        {{ $referrals->links() }}

    </div>

@endsection











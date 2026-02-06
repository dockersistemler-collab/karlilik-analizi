@extends('layouts.admin')



@section('header', 'Planlar ve Fiyatlandirma')



@section('content')

    <div class="panel-card p-4 mb-4">

        <div class="flex flex-col gap-2">

            <div>

                <h3 class="text-sm font-semibold text-slate-800">Mevcut Plan</h3>

                <p class="text-sm text-slate-600 mt-1">

                    {{ $plansCatalog[$currentPlanCode]['name'] ?? ucfirst($currentPlanCode) }}

                </p>

            </div>

            @if($subscription)

                <div class="text-xs text-slate-500">

                    Abonelik Durumu: {{ $subscription->status ?? '-' }}

                </div>

                <div class="text-xs text-slate-500">

                    Abonelik Referans: {{ \Illuminate\Support\Str::limit($subscription->iyzico_subscription_reference_code ?? '-', 14, '...') }}

                </div>

                @if($subscription->next_payment_at)

                    <div class="text-xs text-slate-500">

                        Sonraki Odeme: {{ $subscription->next_payment_at->format('d.m.Y H:i') }}

                    </div>

                @endif

            @endif

        </div>

    </div>



    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        @foreach($plansCatalog as $planCode => $plan)

            @php

                $isCurrent = $planCode === $currentPlanCode;

                $isHighlighted = $highlightFeature && in_array($highlightFeature, $plan['features'] ?? [], true);

            @endphp

            <div class="panel-card p-5 border {{ $plan['recommended'] ? 'border-amber-300' : 'border-slate-200' }} {{ $isHighlighted ? 'ring-2 ring-blue-300' : '' }}">

                <div class="flex items-center justify-between mb-2">

                    <h3 class="text-lg font-semibold text-slate-800">{{ $plan['name'] }}</h3>

                    @if($plan['recommended'])

                        <span class="badge badge-muted text-amber-700">Onerilen</span>

                    @endif

                </div>

                <div class="text-3xl font-semibold text-slate-900">

                    {{ number_format($plan['price_monthly'], 0, ',', '.') }} TL

                    <span class="text-sm text-slate-500 font-normal">/ay</span>

                </div>



                <ul class="mt-4 space-y-2 text-sm text-slate-600">

                    @foreach($plan['features'] as $feature)

                        <li class="flex items-center gap-2">

                            <i class="fa-solid fa-check text-emerald-500"></i>

                            {{ $featureLabels[$feature] ?? $feature }}

                        </li>

                    @endforeach

                </ul>



                <div class="mt-5">

                    @if($plan['contact_sales'])

                        <a href="{{ route('portal.help.support') }}" class="btn btn-outline-accent w-full">Satis ile Gorusun</a>

                    @elseif($isCurrent)

                        <button class="btn btn-solid-accent w-full" disabled>Mevcut Plan</button>

                    @else

                        @if($subscription && ($subscription->status === 'ACTIVE' || $subscription->status === 'active'))

                            <form method="POST" action="{{ route('portal.billing.subscription.upgrade') }}">

                                @csrf

                                <input type="hidden" name="plan_code" value="{{ $planCode }}">

                                <button type="submit" class="btn btn-outline-accent w-full">Plani Degistir</button>

                            </form>

                        @else

                            <form method="POST" action="{{ route('portal.billing.subscribe') }}">

                                @csrf

                                <input type="hidden" name="plan_code" value="{{ $planCode }}">

                                <button type="submit" class="btn btn-solid-accent w-full">Abone Ol</button>

                            </form>

                        @endif

                    @endif

                </div>

                @if($subscription && ($subscription->status === 'ACTIVE' || $subscription->status === 'active') && $isCurrent)

                    <div class="mt-2">

                        <form method="POST" action="{{ route('portal.billing.subscription.cancel') }}">

                            @csrf

                            <button type="submit" class="btn btn-outline-accent w-full">Iptal Et</button>

                        </form>

                    </div>

                @endif

            </div>

        @endforeach

    </div>

@endsection





@section('header', 'Plan Yukseltme')

@section('content')
    <div class="panel-card p-6 max-w-3xl">
        <h3 class="text-lg font-semibold text-slate-800">{{ $featureLabel }}</h3>
        <p class="text-sm text-slate-600 mt-2">{{ $featureDescription }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @if(\Illuminate\Support\Facades\Route::has('portal.subscription'))
                <a href="{{ route('portal.subscription') }}" class="btn btn-solid-accent">Planlari Gor</a>
            @elseif(\Illuminate\Support\Facades\Route::has('portal.billing.plans'))
                <a href="{{ route('portal.billing.plans', array_filter(['feature' => request('feature')])) }}" class="btn btn-solid-accent">Planlari Gor</a>
            @elseif(\Illuminate\Support\Facades\Route::has('portal.billing'))
                <a href="{{ route('portal.billing') }}" class="btn btn-solid-accent">Planlari Gor</a>
            @else
                <a href="{{ route('portal.help.support') }}" class="btn btn-solid-accent">Destekle Iletisime Gec</a>
            @endif
        </div>
    </div>
@endsection



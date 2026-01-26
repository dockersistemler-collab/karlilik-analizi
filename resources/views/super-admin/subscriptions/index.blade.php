@extends('layouts.super-admin')

@section('header')
    Abonelikler
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs text-slate-500 mb-1">Durum</label>
                <select name="status" class="w-full border-slate-300 rounded-md">
                    <option value="">Tümü</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="cancelled" @selected(request('status') === 'cancelled')>İptal</option>
                    <option value="expired" @selected(request('status') === 'expired')>Süresi Doldu</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Askıda</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Paket</label>
                <select name="plan_id" class="w-full border-slate-300 rounded-md">
                    <option value="">Tümü</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" @selected(request('plan_id') == $plan->id)>{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">E-posta</label>
                <input type="text" name="email" value="{{ request('email') }}" class="w-full border-slate-300 rounded-md" placeholder="kullanici@email.com">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Başlangıç (min)</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full border-slate-300 rounded-md">
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Başlangıç (max)</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full border-slate-300 rounded-md">
            </div>
            <div class="md:col-span-5 flex items-center gap-3">
                <button type="submit" class="btn btn-solid-accent">Filtrele</button>
                <a href="{{ route('super-admin.subscriptions.index') }}" class="text-slate-500 hover:text-slate-700">Sıfırla</a>
                <a href="{{ route('super-admin.subscriptions.export', request()->query()) }}" class="text-blue-600 hover:text-blue-900 text-sm">
                    CSV indir
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Kullanıcı</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Paket</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Başlangıç</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Bitiş</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ücret</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Durum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($subscriptions as $subscription)
                    <tr>
                        <td class="px-6 py-4 text-sm text-slate-700">
                            <div class="font-medium text-slate-800">{{ $subscription->user?->name }}</div>
                            <div class="text-xs text-slate-500">{{ $subscription->user?->email }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700">{{ $subscription->plan?->name }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $subscription->starts_at?->format('d.m.Y') }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ $subscription->ends_at?->format('d.m.Y') }}</td>
                        <td class="px-6 py-4 text-sm text-slate-600">{{ number_format($subscription->amount, 2) }} ₺</td>
                        <td class="px-6 py-4 text-xs">
                            <span class="px-2 py-1 rounded {{ $subscription->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-slate-500">Abonelik bulunamadı.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $subscriptions->links() }}
    </div>
@endsection

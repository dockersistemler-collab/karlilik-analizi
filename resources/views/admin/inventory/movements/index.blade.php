@extends('layouts.admin')

@section('title', 'Stok - Stok Hareketleri')

@section('content')
    <div class="panel-card p-6">
        <div class="flex items-center justify-between gap-3 mb-5">
            <h1 class="text-lg font-semibold text-slate-900">Stok - Stok Hareketleri</h1>
            <a href="{{ route('portal.inventory.admin.products.index') }}" class="btn btn-outline">Urunlere Don</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="py-2 pr-4">Tarih</th>
                        <th class="py-2 pr-4">Urun</th>
                        <th class="py-2 pr-4">Tip</th>
                        <th class="py-2 pr-4">Degisim</th>
                        <th class="py-2 pr-4">Not</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 pr-4 text-slate-700">{{ $movement->created_at?->format('d.m.Y H:i') }}</td>
                            <td class="py-3 pr-4 font-semibold text-slate-900">{{ $movement->product?->name ?: '-' }}</td>
                            <td class="py-3 pr-4 text-slate-700">{{ $movement->type }}</td>
                            <td class="py-3 pr-4">
                                @php $delta = (int) $movement->quantity_change; @endphp
                                <span class="{{ $delta >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ $delta >= 0 ? '+' : '' }}{{ $delta }}</span>
                            </td>
                            <td class="py-3 pr-4 text-slate-700">{{ data_get($movement->meta_json, 'note', '-') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-slate-500">Kayit yok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    </div>
@endsection

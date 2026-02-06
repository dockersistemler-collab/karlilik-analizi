@extends('layouts.admin')



@section('header')

    E-Fatura Detayı

@endsection



@section('content')

    <div class="panel-card p-6">

        <div class="max-w-5xl mx-auto space-y-6">

            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <div class="flex items-center justify-between gap-4">

                    <div>

                        <div class="text-sm text-slate-500">Fatura No</div>

                        <div class="mt-1 text-xl font-semibold text-slate-900 font-mono">{{ $einvoice->invoice_no ?? '-' }}</div>

                        <div class="mt-2 text-sm text-slate-600">

                            Sipariş: <span class="font-mono">{{ $einvoice->marketplace_order_no   $einvoice->source_id }}</span>

                            <span class="mx-2 text-slate-300">•</span>

                            Durum: <span class="font-semibold">{{ $einvoice->status }}</span>

                        </div>

                    </div>

                    <div class="flex flex-wrap gap-2 justify-end">

                        <a href="{{ route('portal.einvoices.pdf', $einvoice) }}" class="btn btn-outline">PDF</a>

                        @if($einvoice->status === 'draft')

                            <form method="POST" action="{{ route('portal.einvoices.issue', $einvoice) }}">

                                @csrf

                                <button class="btn btn-solid-accent" type="submit">Düzenle</button>

                            </form>

                        @endif

                        @if($einvoice->type === 'sale')

                            <form method="POST" action="{{ route('portal.einvoices.return', $einvoice) }}">

                                @csrf

                                <button class="btn btn-outline" type="submit">Tam İade Faturası Oluştur</button>

                            </form>



                            <details class="border border-slate-200 rounded-lg p-2">

                                <summary class="cursor-pointer text-sm text-slate-700">Kısmi İade (Credit Note)</summary>

                                <form method="POST" action="{{ route('portal.einvoices.credit-note', $einvoice) }}" class="mt-3 space-y-2">

                                    @csrf

                                    <div class="space-y-2">

                                        @foreach($einvoice->items as $item)

                                            <div class="flex flex-wrap items-center gap-2 text-sm">

                                                <input type="hidden" name="items[{{ $loop->index }}][item_id]" value="{{ $item->id }}">

                                                <div class="min-w-0 flex-1">

                                                    <div class="font-semibold text-slate-800 truncate">{{ $item->name }}</div>

                                                    <div class="text-xs text-slate-500">Mevcut: {{ $item->quantity }} × {{ number_format((float) $item->unit_price, 2) }}</div>

                                                </div>

                                                <input name="items[{{ $loop->index }}][qty]" type="number" step="0.001" min="0" max="{{ $item->quantity }}" value="0" class="w-28 rounded-lg border border-slate-200 bg-white px-2 py-1 text-sm">

                                            </div>

                                        @endforeach

                                    </div>

                                    <div>

                                        <label class="block text-xs text-slate-500 mb-1">Sebep (opsiyonel)</label>

                                        <input name="reason" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" />

                                    </div>

                                    <button class="btn btn-outline" type="submit">Credit Note Oluştur</button>

                                </form>

                            </details>

                        @endif



                        @if($einvoice->status === 'issued')

                            <form method="POST" action="{{ route('portal.einvoices.cancel', $einvoice) }}" class="flex items-center gap-2">

                                @csrf

                                <input name="reason" required class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm" placeholder="İptal sebebi" />

                                <button class="btn btn-outline" type="submit">İptal</button>

                            </form>

                        @endif

                        <a href="{{ route('portal.einvoices.index') }}" class="btn btn-outline">Liste</a>

                    </div>

                </div>

            </div>



            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <div class="text-lg font-semibold text-slate-900">Alıcı</div>

                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-700">

                    <div>

                        <div class="text-xs text-slate-500">Ad</div>

                        <div class="mt-1">{{ $einvoice->buyer_name ?? '-' }}</div>

                    </div>

                    <div>

                        <div class="text-xs text-slate-500">E-posta</div>

                        <div class="mt-1">{{ $einvoice->buyer_email ?? '-' }}</div>

                    </div>

                    <div>

                        <div class="text-xs text-slate-500">Telefon</div>

                        <div class="mt-1">{{ $einvoice->buyer_phone ?? '-' }}</div>

                    </div>

                </div>

            </div>



            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <div class="text-lg font-semibold text-slate-900">Kalemler</div>

                <div class="mt-4 overflow-x-auto">

                    <table class="min-w-full text-sm">

                        <thead class="text-left text-slate-500">

                            <tr>

                                <th class="py-2 pr-4">SKU</th>

                                <th class="py-2 pr-4">Ürün</th>

                                <th class="py-2 pr-4">Adet</th>

                                <th class="py-2 pr-4">Birim</th>

                                <th class="py-2 pr-4">KDV</th>

                                <th class="py-2 pr-4">Toplam</th>

                            </tr>

                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @foreach($einvoice->items as $item)

                                <tr>

                                    <td class="py-3 pr-4 font-mono text-xs">{{ $item->sku ?? '-' }}</td>

                                    <td class="py-3 pr-4">{{ $item->name }}</td>

                                    <td class="py-3 pr-4">{{ $item->quantity }}</td>

                                    <td class="py-3 pr-4">{{ number_format((float) $item->unit_price, 2) }}</td>

                                    <td class="py-3 pr-4">%{{ $item->vat_rate }}</td>

                                    <td class="py-3 pr-4">{{ number_format((float) $item->total, 2) }} {{ $einvoice->currency }}</td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>



                <div class="mt-6 flex justify-end text-sm">

                    <div class="w-full md:w-80 space-y-2">

                        <div class="flex justify-between"><span class="text-slate-500">Ara Toplam</span><span>{{ number_format((float) $einvoice->subtotal, 2) }} {{ $einvoice->currency }}</span></div>

                        <div class="flex justify-between"><span class="text-slate-500">KDV</span><span>{{ number_format((float) $einvoice->tax_total, 2) }} {{ $einvoice->currency }}</span></div>

                        <div class="flex justify-between font-semibold"><span class="text-slate-800">Genel Toplam</span><span>{{ number_format((float) $einvoice->grand_total, 2) }} {{ $einvoice->currency }}</span></div>

                    </div>

                </div>

            </div>



            <div class="bg-white rounded-xl border border-slate-100 p-6">

                <div class="text-lg font-semibold text-slate-900">Olaylar</div>

                <div class="mt-4 space-y-2 text-sm">

                    @forelse($einvoice->events as $ev)

                        <div class="flex items-center justify-between border border-slate-100 rounded-lg px-3 py-2">

                            <div>

                                <div class="font-semibold text-slate-800">{{ $ev->type }}</div>

                                <div class="text-xs text-slate-500">{{ $ev->created_at?->format('d.m.Y H:i') }}</div>

                            </div>

                            <div class="text-xs text-slate-500 font-mono">{{ $ev->id }}</div>

                        </div>

                    @empty

                        <div class="text-slate-500">Olay yok.</div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

@endsection





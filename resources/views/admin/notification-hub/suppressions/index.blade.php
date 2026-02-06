@extends('layouts.admin')



@section('header', 'Email Suppression')



@section('content')

    <div class="panel-card p-4 mb-4">

        <form method="GET" action="{{ route('portal.notification-hub.suppressions.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Email</label>

                <input type="text" name="search" value="{{ request('search') }}" placeholder="email ara">

            </div>

            <div class="flex flex-col gap-2">

                <label class="text-xs font-semibold text-slate-500">Sebep</label>

                <select name="reason">

                    <option value="">Tumu</option>

                    @foreach($reasons as $reason)

                        <option value="{{ $reason }}" {{ request('reason') === $reason ? 'selected' : '' }}>{{ $reason }}</option>

                    @endforeach

                </select>

            </div>

            <div class="flex items-end gap-2">

                <button type="submit" class="btn btn-solid-accent">Uygula</button>

                <a href="{{ route('portal.notification-hub.suppressions.index') }}" class="btn btn-outline-accent">Sifirla</a>

            </div>

        </form>

    </div>



    <div class="panel-card p-4 mb-4">

        @include('admin.notification-hub.suppressions.create', ['canGlobal' => $canGlobal ?? false])

    </div>



    <div class="panel-card p-4">

        <div class="overflow-x-auto">

            <table class="min-w-full text-sm">

                <thead>

                    <tr>

                        <th class="text-left">Email</th>

                        <th class="text-left">Kapsam</th>

                        <th class="text-left">Sebep</th>

                        <th class="text-left">Kaynak</th>

                        <th class="text-left">Tarih</th>

                        <th class="text-left"></th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($suppressions as $suppression)

                        <tr class="border-t border-slate-100">

                            <td class="py-2">{{ $suppression->email }}</td>

                            <td class="py-2">{{ $suppression->tenant_id ? 'tenant' : 'global' }}</td>

                            <td class="py-2">{{ $suppression->reason }}</td>

                            <td class="py-2">{{ $suppression->source ?? '-' }}</td>

                            <td class="py-2">{{ optional($suppression->created_at)->format('Y-m-d H:i') }}</td>

                            <td class="py-2">

                                <form method="POST" action="{{ route('portal.notification-hub.suppressions.destroy', $suppression) }}">

                                    @csrf

                                    @method('DELETE')

                                    <button type="submit" class="btn btn-outline-accent">Kaldir</button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="6" class="py-6 text-center text-slate-500">Kayit yok.</td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>



        <div class="mt-4">

            {{ $suppressions->links() }}

        </div>

    </div>

@endsection





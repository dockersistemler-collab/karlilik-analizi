<form method="POST" action="{{ route('portal.notification-hub.suppressions.store') }}" class="space-y-4">

    @csrf

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

        <div class="flex flex-col gap-2 md:col-span-2">

            <label class="text-xs font-semibold text-slate-500">Email</label>

            <input type="email" name="email" value="{{ old('email') }}" required>

        </div>

        <div class="flex flex-col gap-2">

            <label class="text-xs font-semibold text-slate-500">Kapsam</label>

            <select name="scope">

                <option value="tenant" {{ old('scope') === 'tenant' ? 'selected' : '' }}>Bu tenant</option>

                @if(($canGlobal ?? false))

                    <option value="global" {{ old('scope') === 'global' ? 'selected' : '' }}>Global</option>

                @endif

            </select>

        </div>

        <div class="flex flex-col gap-2">

            <label class="text-xs font-semibold text-slate-500">Sebep</label>

            <select name="reason">

                <option value="manual" {{ old('reason') === 'manual' ? 'selected' : '' }}>manual</option>

                <option value="invalid" {{ old('reason') === 'invalid' ? 'selected' : '' }}>invalid</option>

            </select>

        </div>

        <div class="flex flex-col gap-2 md:col-span-4">

            <label class="text-xs font-semibold text-slate-500">Not</label>

            <input type="text" name="note" value="{{ old('note') }}">

        </div>

    </div>

    <div>

        <button type="submit" class="btn btn-solid-accent">Ekle</button>

    </div>

</form>





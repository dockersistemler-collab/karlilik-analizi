<form method="GET" action="{{ route('portal.notification-hub.notifications.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">

    <div class="flex flex-col gap-2">

        <label class="text-xs font-semibold text-slate-500">Tür</label>

        <select name="type">

            <option value="">Tümü</option>

            <option value="critical" {{ request('type') === 'critical' ? 'selected' : '' }}>critical</option>

            <option value="operational" {{ request('type') === 'operational' ? 'selected' : '' }}>operational</option>

            <option value="info" {{ request('type') === 'info' ? 'selected' : '' }}>info</option>

        </select>

    </div>

    <div class="flex flex-col gap-2">

        <label class="text-xs font-semibold text-slate-500">Pazaryeri</label>

        <select name="marketplace">

            <option value="">Tümü</option>

            @foreach($marketplaces as $code => $name)

                <option value="{{ $code }}" {{ request('marketplace') === $code ? 'selected' : '' }}>{{ $name }}</option>

            @endforeach

        </select>

    </div>

    <div class="flex flex-col gap-2">

        <label class="text-xs font-semibold text-slate-500">Durum</label>

        <select name="read">

            <option value="">Tümü</option>

            <option value="unread" {{ request('read') === 'unread' ? 'selected' : '' }}>Okunmayan</option>

            <option value="read" {{ request('read') === 'read' ? 'selected' : '' }}>Okunan</option>

        </select>

    </div>

    <div class="flex flex-col gap-2">

        <label class="text-xs font-semibold text-slate-500">Başlangıç</label>

        <input type="date" name="from" value="{{ request('from', $defaultFrom ?? '') }}">

    </div>

    <div class="flex flex-col gap-2">

        <label class="text-xs font-semibold text-slate-500">Bitiş</label>

        <input type="date" name="to" value="{{ request('to', $defaultTo ?? '') }}">

    </div>

    <div class="flex items-end gap-2">

        <button type="submit" class="btn btn-solid-accent">Uygula</button>

        <a href="{{ route('portal.notification-hub.notifications.index') }}" class="btn btn-outline-accent">Sıfırla</a>

    </div>

</form>





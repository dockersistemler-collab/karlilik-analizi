@php
    $badgeClass = match ($notification->type) {
        'critical' => 'badge badge-danger',
        'operational' => 'badge badge-warning',
        'info' => 'badge badge-muted',
        default => 'badge badge-muted',
    };
    $isRead = !is_null($notification->read_at);
@endphp

<div class="border border-slate-200/70 rounded-xl p-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
    <div class="flex-1">
        <div class="flex items-center gap-2 mb-2">
            <span class="{{ $badgeClass }}">{{ $notification->type }}</span>
            @if($notification->marketplace)
                <span class="badge badge-muted">{{ $notification->marketplace }}</span>
            @endif
            @if($isRead)
                <span class="badge badge-success">Okundu</span>
            @endif
        </div>
        <div class="text-sm font-semibold text-slate-800">{{ $notification->title }}</div>
        <div class="text-sm text-slate-600 mt-1">
            {{ \Illuminate\Support\Str::limit($notification->body, 180) }}
        </div>
        <div class="text-xs text-slate-500 mt-2">
            {{ optional($notification->created_at)->diffForHumans() }}
        </div>
    </div>
    <div class="flex items-center gap-2">
        @if($notification->action_url)
            <a href="{{ $notification->action_url }}" class="btn btn-outline-accent">Detaya Git</a>
        @endif
        @if(!$isRead)
            <form method="POST" action="{{ route('portal.notification-hub.notifications.read', $notification) }}">
                @csrf
                <button type="submit" class="btn btn-solid-accent">Okundu</button>
            </form>
        @endif
    </div>
</div>


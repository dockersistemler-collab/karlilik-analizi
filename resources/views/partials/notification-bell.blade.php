@php

    $unreadCount = $notificationUnreadCount ?? 0;

    $routeName = $notificationHubRoute ?? null;

@endphp



@if($routeName)

    <a href="{{ route($routeName) }}" class="topbar-icon relative" title="Bildirimler">

        <i class="fa-regular fa-bell text-sm"></i>

        @if($unreadCount > 0)

            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] rounded-full bg-rose-500 text-white text-[10px] font-semibold flex items-center justify-center px-1">

                {{ $unreadCount > 99 ? '99+' : $unreadCount }}

            </span>

        @endif

    </a>

@else

    <span class="topbar-icon relative opacity-60" title="Bildirimler">

        <i class="fa-regular fa-bell text-sm"></i>

    </span>

@endif

